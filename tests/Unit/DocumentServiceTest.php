<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\GeneratedDocument;
use App\Models\Guarantor;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Document\DocumentService;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $serializedItem;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected DocumentService $documentService;

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
            'address' => 'Shop 12-14, Commercial Center, Ferozepur Road',
            'city' => 'Lahore',
            'phone' => '042-35800000',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'ulid' => (string) Str::ulid(),
            'customer_number' => 'CUST-001',
            'full_name' => 'Tariq Mehmood',
            'father_or_husband_name' => 'Mehmood Akhtar',
            'cnic' => '35202-1234567-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'House 45, Model Town, Lahore',
            'status' => 'verified',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Refrigerators',
            'slug' => 'refrigerators',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Dawlance Pakistan',
            'contact_person' => 'Supplier Rep',
            'phone' => '042-111111111',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'Dawlance 91996 Chrome Refrigerator',
            'slug' => 'dawlance-91996',
            'brand' => 'Dawlance',
            'model_name' => '91996 Chrome',
            'sku' => 'DWL-91996',
            'base_cash_price' => 100000,
            'status' => 'active',
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'serial_number' => 'DWL-SN-99887711',
            'status' => 'disbursed',
            'purchase_price' => 85000,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard Plan',
            'tenure_months' => 12,
            'markup_rate_pct' => 20.0,
            'down_payment_pct' => 20.0,
            'advance_installments_count' => 0,
            'status' => 'active',
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->user->id,
            'account_number' => 'AGR-2026-0001',
            'status' => 'active',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 20000,
            'financed_principal' => 80000,
            'markup_rate_pct' => 20.0,
            'markup_amount' => 16000,
            'total_financed' => 96000,
            'total_payable' => 116000,
            'installment_amount' => 8000,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 96000,
            'start_date' => Carbon::today(),
            'first_due_date' => Carbon::today()->addMonth(),
            'maturity_date' => Carbon::today()->addMonths(12),
        ]);

        // Generate schedules
        $generator = new ScheduleGenerator();
        $generator->generateSchedule($this->agreement);

        $this->documentService = new DocumentService();
    }

    public function test_it_records_document_audit_with_correct_numbering(): void
    {
        $doc = $this->documentService->recordDocumentAudit(
            $this->agreement,
            'installment_contract',
            'Legal Installment Financing Agreement',
            $this->user,
            ['notes' => 'Printed for showroom execution']
        );

        $this->assertInstanceOf(GeneratedDocument::class, $doc);
        $this->assertEquals($this->agreement->id, $doc->installment_agreement_id);
        $this->assertEquals($this->customer->id, $doc->customer_id);
        $this->assertEquals($this->company->id, $doc->company_id);
        $this->assertEquals($this->branch->id, $doc->branch_id);
        $this->assertEquals('installment_contract', $doc->document_type);

        // Sequence number structure check: CTR-{branch_code}-{YYYYMM}-{seq}
        $yearMonth = Carbon::now()->format('Ym');
        $this->assertStringStartsWith("CTR-FZR-01-{$yearMonth}-", $doc->document_number);
    }

    public function test_it_prepares_application_form_payload(): void
    {
        $payload = $this->documentService->prepareApplicationForm($this->agreement);

        $this->assertEquals('application_form', $payload['type']);
        $this->assertEquals($this->customer->id, $payload['customer']->id);
        $this->assertEquals($this->product->id, $payload['product']->id);
        $this->assertEquals($this->plan->id, $payload['plan']->id);
    }

    public function test_it_prepares_installment_contract_payload_with_stamp_paper_flag(): void
    {
        $payload = $this->documentService->prepareInstallmentContract($this->agreement, [
            'stamp_paper_margin' => true,
        ]);

        $this->assertEquals('installment_contract', $payload['type']);
        $this->assertTrue($payload['stampPaperMargin']);
        $this->assertCount(12, $payload['schedules']);
    }

    public function test_it_prepares_guarantor_affidavit_payload(): void
    {
        $guarantor = Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'relationship' => 'Brother',
            'full_name' => 'Muhammad Asif',
            'cnic' => '35202-9988776-5',
            'mobile' => '03009876543',
            'address' => 'Johar Town, Lahore',
            'occupation' => 'Businessman',
            'is_verified' => true,
        ]);

        $this->agreement->guarantors()->attach($guarantor->id, [
            'relationship' => 'Brother',
            'is_primary' => true,
        ]);

        $payload = $this->documentService->prepareGuarantorAffidavit($this->agreement);

        $this->assertEquals('guarantor_affidavit', $payload['type']);
        $this->assertEquals($guarantor->id, $payload['guarantor']->id);
    }

    public function test_it_prepares_delivery_note_payload(): void
    {
        $payload = $this->documentService->prepareDeliveryNote($this->agreement);

        $this->assertEquals('delivery_note', $payload['type']);
        $this->assertEquals($this->serializedItem->serial_number, $payload['serializedItem']->serial_number);
    }

    public function test_it_prepares_thermal_receipt_payload(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'payment_number' => 'PAY-2026-0001',
            'payment_date' => Carbon::now(),
            'amount' => 8000,
            'principal_paid' => 6666.67,
            'markup_paid' => 1333.33,
            'late_fee_paid' => 0,
            'payment_method' => 'cash',
            'status' => 'posted',
        ]);

        $payload = $this->documentService->preparePaymentReceipt($payment, '58mm');

        $this->assertEquals('payment_receipt', $payload['type']);
        $this->assertEquals('58mm', $payload['format']);
        $this->assertEquals($payment->id, $payload['payment']->id);
    }

    public function test_it_calculates_early_settlement_with_rebate(): void
    {
        // Total financed: 80000, Total markup: 16000, Total payable: 96000
        // Calculate early settlement with 50% rebate on unearned markup
        $settlement = $this->agreement->calculateEarlySettlement(50.0);

        $this->assertIsArray($settlement);
        $this->assertArrayHasKey('remaining_principal', $settlement);
        $this->assertArrayHasKey('unearned_markup', $settlement);
        $this->assertArrayHasKey('markup_rebate', $settlement);
        $this->assertArrayHasKey('net_settlement_amount', $settlement);

        $this->assertEquals(80000.0, $settlement['remaining_principal']);
        $this->assertEquals(16000.0, $settlement['unearned_markup']);
        // 50% of 16000 = 8000 rebate
        $this->assertEquals(8000.0, $settlement['markup_rebate']);
        $this->assertEquals(8000.0, $settlement['retained_markup']);
        // Net payable = 80000 + 8000 = 88000
        $this->assertEquals(88000.0, $settlement['net_settlement_amount']);
    }

    public function test_clearance_noc_throws_exception_when_balance_is_outstanding(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot issue Clearance Certificate & NOC');

        // Remaining balance is 96000 and status is active
        $this->documentService->prepareClearanceNoc($this->agreement);
    }

    public function test_clearance_noc_succeeds_when_agreement_is_completed_and_zero_balance(): void
    {
        $this->agreement->update([
            'remaining_balance' => 0,
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

        $payload = $this->documentService->prepareClearanceNoc($this->agreement);

        $this->assertEquals('clearance_noc', $payload['type']);
        $this->assertEquals($this->customer->id, $payload['customer']->id);
        $this->assertNotNull($payload['clearedAt']);
    }
}
