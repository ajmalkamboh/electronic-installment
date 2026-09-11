<?php

namespace Tests\Feature;

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
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallmentAgreementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $serializedItem;
    protected InstallmentPlan $plan;
    protected Guarantor $guarantor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Lahore',
            'slug' => 'kamboh-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Ajmal Kamboh',
            'email' => 'ajmal@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35202-1234567-3',
            'full_name' => 'Usman Ghani',
            'father_or_husband_name' => 'Abdul Ghani',
            'gender' => 'male',
            'mobile_primary' => '03211234567',
            'present_address' => 'Model Town, Lahore',
            'status' => 'active',
        ]);

        $this->guarantor = Guarantor::create([
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'full_name' => 'Zubair Ghani',
            'cnic' => '35202-7654321-1',
            'relationship' => 'Brother',
            'mobile' => '03217654321',
            'address' => 'Model Town, Lahore',
            'is_verified' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'LED Televisions',
            'slug' => 'led-televisions',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'TCL Electronics Pakistan',
            'phone' => '04235889900',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'TCL',
            'model_name' => '55 Inch 4K QLED TV',
            'sku' => 'TCL-55QLED-01',
            'base_cash_price' => 120000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'serial_number' => 'TCL55QLED99281',
            'status' => 'in_stock',
            'purchase_cost' => 95000,
            'received_at' => now(),
        ]);

        BranchInventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
            'quantity_available' => 10,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard Super Plan',
            'slug' => '12-months-standard',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_user_can_view_agreements_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('agreements.index'));

        $response->assertStatus(200);
        $response->assertSee('Installment Contracts & Agreements');
    }

    public function test_user_can_view_create_agreement_wizard(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('agreements.create'));

        $response->assertStatus(200);
        $response->assertSee('Draft Installment Sales Agreement');
        $response->assertSee('Usman Ghani');
        $response->assertSee('TCL 55 Inch 4K QLED TV');
    }

    public function test_user_can_store_agreement_draft(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.store'), [
                'branch_id' => $this->branch->id,
                'customer_id' => $this->customer->id,
                'product_id' => $this->product->id,
                'serialized_item_id' => $this->serializedItem->id,
                'installment_plan_id' => $this->plan->id,
                'down_payment' => 24000,
                'guarantor_ids' => [$this->guarantor->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('installment_agreements', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'status' => 'draft',
            'down_payment_amount' => 24000,
            'cash_price' => 120000,
        ]);

        $agreement = InstallmentAgreement::where('customer_id', $this->customer->id)->first();
        $response->assertRedirect(route('agreements.show', $agreement->id));

        // Verify hardware reserved
        $this->serializedItem->refresh();
        $this->assertEquals('reserved', $this->serializedItem->status);
    }

    public function test_user_can_view_agreement_dossier(): void
    {
        $agreement = app(\App\Services\Agreement\AgreementService::class)->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 24000,
            'guarantor_ids' => [$this->guarantor->id],
        ], $this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('agreements.show', $agreement->id));

        $response->assertStatus(200);
        $response->assertSee($agreement->account_number);
        $response->assertSee('Usman Ghani');
        $response->assertSee('TCL 55 Inch 4K QLED TV');
        $response->assertSee('Zubair Ghani');
    }

    public function test_state_machine_flow_draft_to_review_to_approve_to_downpayment_to_disburse(): void
    {
        $service = app(\App\Services\Agreement\AgreementService::class);

        $agreement = $service->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 24000,
        ], $this->admin);

        // 1. Submit for review
        $submitResponse = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.submit', $agreement->id));

        $submitResponse->assertRedirect();
        $agreement->refresh();
        $this->assertEquals('under_review', $agreement->status);

        // 2. Approve
        $approveResponse = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.approve', $agreement->id), [
                'approval_notes' => 'Customer credit score 75 verified. Approved.',
            ]);

        $approveResponse->assertRedirect();
        $agreement->refresh();
        $this->assertEquals('approved', $agreement->status);

        // 3. Record Down Payment
        $dpResponse = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.down-payment', $agreement->id), [
                'amount' => 24000,
                'payment_method' => 'cash',
                'receipt_ref' => 'POS-FZR-9081',
                'notes' => 'Full down payment received in showroom cashier drawer',
            ]);

        $dpResponse->assertRedirect();
        $agreement->refresh();
        $this->assertTrue($agreement->isDownPaymentSatisfied());

        // 4. Disburse merchandise & activate
        $disburseResponse = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.disburse', $agreement->id), [
                'handover_notes' => 'Customer inspected box. All accessories and remote included.',
            ]);

        $disburseResponse->assertRedirect();
        $agreement->refresh();
        $this->assertEquals('active', $agreement->status);
        $this->assertNotNull($agreement->activated_at);

        // Serialized hardware must be disbursed
        $this->serializedItem->refresh();
        $this->assertEquals('disbursed', $this->serializedItem->status);
    }

    public function test_cannot_disburse_merchandise_when_down_payment_is_unpaid(): void
    {
        $service = app(\App\Services\Agreement\AgreementService::class);

        $agreement = $service->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 24000,
        ], $this->admin);

        $service->submitForReview($agreement, $this->admin);
        $service->approve($agreement, $this->admin, 'Approved');

        // Attempt disburse without recording down payment
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.disburse', $agreement->id), [
                'handover_notes' => 'Test attempt',
            ]);

        $response->assertSessionHasErrors(['error']);
        $agreement->refresh();
        $this->assertEquals('approved', $agreement->status); // Remains approved, not activated
    }

    public function test_user_can_cancel_agreement(): void
    {
        $agreement = app(\App\Services\Agreement\AgreementService::class)->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 24000,
        ], $this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('agreements.cancel', $agreement->id), [
                'cancellation_reason' => 'Customer could not arrange down payment.',
            ]);

        $response->assertRedirect();
        $agreement->refresh();
        $this->assertEquals('cancelled', $agreement->status);
        $this->assertEquals('Customer could not arrange down payment.', $agreement->cancellation_reason);

        // Hardware unit released back to in_stock
        $this->serializedItem->refresh();
        $this->assertEquals('in_stock', $this->serializedItem->status);
    }

    public function test_tenant_isolation_prevents_viewing_foreign_agreements(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Showroom Co',
            'slug' => 'foreign-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Rawalpindi Showroom',
            'code' => 'RWP-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $otherCustomer = Customer::create([
            'company_id' => $otherCompany->id,
            'cnic' => '37405-1234567-9',
            'full_name' => 'Foreign Customer',
            'mobile_primary' => '03331234567',
            'present_address' => 'Murree Road, Rawalpindi',
            'status' => 'active',
        ]);

        $otherCategory = ProductCategory::create([
            'company_id' => $otherCompany->id,
            'name' => 'Air Conditioners',
            'slug' => 'air-conditioners',
            'is_active' => true,
        ]);

        $otherSupplier = Supplier::create([
            'company_id' => $otherCompany->id,
            'name' => 'Haier Pakistan',
            'phone' => '0421112233',
            'is_active' => true,
        ]);

        $otherProduct = Product::create([
            'company_id' => $otherCompany->id,
            'category_id' => $otherCategory->id,
            'supplier_id' => $otherSupplier->id,
            'brand' => 'Haier',
            'model_name' => 'Inverter AC 1.5T',
            'base_cash_price' => 140000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => false,
            'is_active' => true,
        ]);

        $otherPlan = InstallmentPlan::create([
            'company_id' => $otherCompany->id,
            'name' => '12 Months Plan',
            'slug' => 'foreign-12m',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $otherUser = User::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'role_id' => Role::where('name', 'company_admin')->first()->id,
            'role' => 'company_admin',
            'name' => 'Foreign Admin',
            'email' => 'foreign@showroom.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $foreignAgreement = app(\App\Services\Agreement\AgreementService::class)->createDraft([
            'branch_id' => $otherBranch->id,
            'customer_id' => $otherCustomer->id,
            'product_id' => $otherProduct->id,
            'installment_plan_id' => $otherPlan->id,
            'down_payment' => 28000,
        ], $otherUser);

        // Authenticated admin from Kamboh Electronics attempts to access foreign agreement
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('agreements.show', $foreignAgreement->id));

        $response->assertStatus(404);
    }

    public function test_printable_contract_page_renders_successfully(): void
    {
        $agreement = app(\App\Services\Agreement\AgreementService::class)->createDraft([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'down_payment' => 24000,
            'guarantor_ids' => [$this->guarantor->id],
        ], $this->admin);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('agreements.print', $agreement->id));

        $response->assertStatus(200);
        $response->assertSee('Electronic Installment Sales', false);
        $response->assertSee($agreement->account_number);
        $response->assertSee('Usman Ghani');
        $response->assertSee('Zubair Ghani');
        $response->assertSee('TCL 55 Inch 4K QLED TV');
    }
}
