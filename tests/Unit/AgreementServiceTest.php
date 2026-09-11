<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Agreement\AgreementService;
use App\Services\Pricing\PricingEngine;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgreementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $officer;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $serializedItem;
    protected InstallmentPlan $plan;
    protected Guarantor $guarantor;
    protected AgreementService $agreementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Royal Electronics Multan',
            'slug' => 'royal-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Cantonment Showroom',
            'code' => 'CAN-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->officer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Saleh Muhammad',
            'email' => 'saleh@royal.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '36302-1234567-1',
            'full_name' => 'Muhammad Bilal',
            'father_or_husband_name' => 'Bilal Senior',
            'gender' => 'male',
            'mobile_primary' => '03001234567',
            'present_address' => 'Bosan Road, Multan',
            'status' => 'active',
        ]);

        $this->guarantor = Guarantor::create([
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'full_name' => 'Kashif Mehmood',
            'cnic' => '36302-9876543-1',
            'relationship' => 'Brother',
            'mobile' => '03017654321',
            'address' => 'Gulgasht Colony, Multan',
            'is_verified' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Direct',
            'phone' => '0421234567',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Samsung',
            'model_name' => 'Galaxy A55',
            'sku' => 'SAM-A55-001',
            'base_cash_price' => 100000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'imei_1' => '867543210987654',
            'imei_2' => '867543210987655',
            'serial_number' => 'RF8N123456XYZ',
            'status' => 'in_stock',
            'purchase_cost' => 85000,
            'received_at' => now(),
        ]);

        BranchInventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'quantity_available' => 5,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard',
            'slug' => '12-months-standard',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->agreementService = new AgreementService(new PricingEngine());
    }

    public function test_creates_draft_agreement_with_calculated_financials_and_reserves_hardware(): void
    {
        $agreement = $this->agreementService->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 20000,
            'guarantor_ids' => [$this->guarantor->id],
        ], $this->officer);

        $this->assertInstanceOf(InstallmentAgreement::class, $agreement);
        $this->assertEquals('draft', $agreement->status);
        $this->assertStringStartsWith('AGR-CAN01-', $agreement->account_number);

        // Financials: 100,000 cash - 20,000 down = 80,000 principal. 25% flat markup = 20,000.
        // Total financed = 100,000. Installment = 8,333.33/mo. Total payable = 120,000.
        $this->assertEquals(100000, (float) $agreement->cash_price);
        $this->assertEquals(20000, (float) $agreement->down_payment_amount);
        $this->assertEquals(0.00, (float) $agreement->down_payment_paid);
        $this->assertEquals(80000, (float) $agreement->financed_principal);
        $this->assertEquals(20000, (float) $agreement->markup_amount);
        $this->assertEquals(100000, (float) $agreement->total_financed);
        $this->assertEquals(120000, (float) $agreement->total_payable);
        $this->assertEquals(8333.33, (float) $agreement->installment_amount);

        // Hardware must be reserved
        $this->serializedItem->refresh();
        $this->assertEquals('reserved', $this->serializedItem->status);

        // Showroom inventory counter must be updated
        $inventory = BranchInventory::where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(1, $inventory->quantity_reserved);
        $this->assertEquals(4, $inventory->quantity_available);

        // Guarantor must be attached
        $this->assertCount(1, $agreement->guarantors);
        $this->assertEquals($this->guarantor->id, $agreement->guarantors->first()->id);
    }

    public function test_cannot_create_agreement_for_blacklisted_customer(): void
    {
        $this->customer->update(['status' => 'blacklisted']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('blacklisted');

        $this->agreementService->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
        ], $this->officer);
    }

    public function test_cannot_disburse_merchandise_if_down_payment_is_unpaid(): void
    {
        $agreement = $this->agreementService->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 20000,
        ], $this->officer);

        $this->agreementService->submitForReview($agreement, $this->officer);
        $this->agreementService->approve($agreement, $this->officer, 'Credit approved');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Down payment deficit of Rs. 20,000 remains unpaid');

        $this->agreementService->disburseAndActivate($agreement, $this->officer, 'Attempted early handover');
    }

    public function test_disburse_succeeds_when_down_payment_is_paid_and_updates_stock_counters(): void
    {
        $agreement = $this->agreementService->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 20000,
        ], $this->officer);

        $this->agreementService->submitForReview($agreement, $this->officer);
        $this->agreementService->approve($agreement, $this->officer, 'Approved by manager');

        // Customer pays down payment at cash counter
        $this->agreementService->recordDownPayment(
            $agreement,
            20000,
            'cash',
            'RCPT-00991',
            $this->officer,
            'Down payment received in cash'
        );

        $this->assertTrue($agreement->isDownPaymentSatisfied());
        $this->assertEquals(0.00, $agreement->down_payment_deficit);

        // Disburse merchandise to customer
        $activatedAgreement = $this->agreementService->disburseAndActivate(
            $agreement,
            $this->officer,
            'Handed over box with warranty card and charger'
        );

        $this->assertEquals('active', $activatedAgreement->status);
        $this->assertNotNull($activatedAgreement->activated_at);

        // Hardware state is disbursed
        $this->serializedItem->refresh();
        $this->assertEquals('disbursed', $this->serializedItem->status);

        // Showroom inventory: On hand decremented from 5 to 4, reserved returned to 0, available = 4
        $inventory = BranchInventory::where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(4, $inventory->quantity_on_hand);
        $this->assertEquals(0, $inventory->quantity_reserved);
        $this->assertEquals(4, $inventory->quantity_available);

        // Stock movement audit recorded
        $this->assertDatabaseHas('stock_movements', [
            'serialized_item_id' => $this->serializedItem->id,
            'movement_type' => 'sale_disbursement',
            'reference_number' => $activatedAgreement->account_number,
        ]);
    }

    public function test_cancellation_releases_reserved_hardware_back_to_stock(): void
    {
        $agreement = $this->agreementService->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 20000,
        ], $this->officer);

        $this->serializedItem->refresh();
        $this->assertEquals('reserved', $this->serializedItem->status);

        $this->agreementService->cancel($agreement, $this->officer, 'Customer changed mind');

        $agreement->refresh();
        $this->assertEquals('cancelled', $agreement->status);
        $this->assertEquals('Customer changed mind', $agreement->cancellation_reason);

        // Hardware released back to in_stock
        $this->serializedItem->refresh();
        $this->assertEquals('in_stock', $this->serializedItem->status);

        // Reserved counter decremented
        $inventory = BranchInventory::where('branch_id', $this->branch->id)->where('product_id', $this->product->id)->first();
        $this->assertEquals(0, $inventory->quantity_reserved);
        $this->assertEquals(5, $inventory->quantity_available);
    }
}
