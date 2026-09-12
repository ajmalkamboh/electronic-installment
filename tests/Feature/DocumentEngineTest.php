<?php

namespace Tests\Feature;

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
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentEngineTest extends TestCase
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
            'name' => 'LED Televisions',
            'slug' => 'led-televisions',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Pakistan',
            'contact_person' => 'Distributor',
            'phone' => '042-111111111',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'Samsung 55 Inch Crystal 4K UHD TV',
            'slug' => 'samsung-55-crystal',
            'brand' => 'Samsung',
            'model_name' => 'CU7000',
            'sku' => 'SAM-55-CU7000',
            'base_cash_price' => 150000,
            'status' => 'active',
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'serial_number' => 'SAM-SN-55443322',
            'status' => 'disbursed',
            'purchase_price' => 130000,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Electronics Plan',
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
            'account_number' => 'AGR-2026-0099',
            'status' => 'active',
            'cash_price' => 150000,
            'down_payment_amount' => 30000,
            'down_payment_paid' => 30000,
            'financed_principal' => 120000,
            'markup_rate_pct' => 20.0,
            'markup_amount' => 24000,
            'total_financed' => 144000,
            'total_payable' => 174000,
            'installment_amount' => 12000,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 144000,
            'start_date' => Carbon::today(),
            'first_due_date' => Carbon::today()->addMonth(),
            'maturity_date' => Carbon::today()->addMonths(12),
        ]);

        $generator = new ScheduleGenerator();
        $generator->generateSchedule($this->agreement);

        $this->guarantor = Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'relationship' => 'Uncle',
            'full_name' => 'Muhammad Asif',
            'cnic' => '35202-9988776-5',
            'mobile' => '03009876543',
            'address' => 'Johar Town, Lahore',
            'occupation' => 'Businessman',
            'is_verified' => true,
        ]);

        $this->agreement->guarantors()->attach($this->guarantor->id, [
            'relationship' => 'Uncle',
            'is_primary' => true,
        ]);
    }

    public function test_document_hub_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('documents.hub'));

        $response->assertStatus(200);
        $response->assertSee('Legal Documents');
        $response->assertSee('AGR-2026-0099');
    }

    public function test_agreement_print_center_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.agreement', $this->agreement->id));

        $response->assertStatus(200);
        $response->assertSee('Customer Application Dossier');
        $response->assertSee('Legal Installment Financing Contract');
        $response->assertSee('Guarantor Promissory Affidavit');
        $response->assertSee('Field Verification Report');
        $response->assertSee('Customer Statement of Account');
        $response->assertSee('Early Settlement Payoff Offer');
        $response->assertSee('Clearance Certificate');
    }

    public function test_it_prints_customer_application_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'application-form', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('APPLICATION FORM');
        $response->assertSee('Tariq Mehmood');
        $response->assertSee('35202-1234567-1');

        $this->assertDatabaseHas('generated_documents', [
            'installment_agreement_id' => $this->agreement->id,
            'document_type' => 'application_form',
        ]);
    }

    public function test_it_prints_verification_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'verification-report', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('VERIFICATION REPORT');
        $response->assertSee('Physical Residence Verification');
        $response->assertSee('Tariq Mehmood');
    }

    public function test_it_prints_credit_assessment_sheet(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'credit-assessment', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('CREDIT ASSESSMENT');
        $response->assertSee('Financial Affordability');
    }

    public function test_it_prints_installment_contract_standard_and_stamp_paper(): void
    {
        // Standard A4
        $responseStandard = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'installment-contract', 'agreement' => $this->agreement->id]));

        $responseStandard->assertStatus(200);
        $responseStandard->assertSee('Hire-Purchase / Electronic Installment Financing Agreement');
        $responseStandard->assertSee('Standard A4');

        // With 85mm Stamp Paper Margin
        $responseStamp = $this->actingAs($this->user)
            ->get(route('documents.print', [
                'type' => 'installment-contract',
                'agreement' => $this->agreement->id,
                'stamp_paper_margin' => '1',
            ]));

        $responseStamp->assertStatus(200);
        $responseStamp->assertSee('ENABLED (85-90mm Top Offset)');
    }

    public function test_it_prints_guarantor_affidavit(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'guarantor-affidavit', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('Guarantor Undertaking &amp; Promissory Affidavit', false);
        $response->assertSee('Muhammad Asif');
        $response->assertSee('35202-9988776-5');
    }

    public function test_it_prints_delivery_note_and_gate_pass(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'delivery-note', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('DELIVERY &amp; HANDOVER NOTE', false);
        $response->assertSee('GATE PASS / SECURITY CLEARANCE STUB');
        $response->assertSee('SAM-SN-55443322');
    }

    public function test_it_prints_statement_of_account(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'account-statement', 'agreement' => $this->agreement->id]));

        $response->assertStatus(200);
        $response->assertSee('STATEMENT OF ACCOUNT');
        $response->assertSee('PKR 144,000.00');
    }

    public function test_it_prints_early_settlement_letter(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('documents.print', [
                'type' => 'settlement-letter',
                'agreement' => $this->agreement->id,
                'rebate_pct' => 50,
            ]));

        $response->assertStatus(200);
        $response->assertSee('EARLY SETTLEMENT QUOTE');
        $response->assertSee('Early Settlement Calculation');
        $response->assertSee('Unearned Markup Rebate Discount (50.0%)');
    }

    public function test_it_prints_thermal_receipt_formats(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'payment_number' => 'PAY-2026-9001',
            'payment_date' => Carbon::now(),
            'amount' => 12000,
            'principal_paid' => 10000,
            'markup_paid' => 2000,
            'late_fee_paid' => 0,
            'payment_method' => 'cash',
            'status' => 'posted',
        ]);

        // 80mm
        $res80 = $this->actingAs($this->user)
            ->get(route('documents.receipt', ['payment' => $payment->id, 'format' => '80mm']));
        $res80->assertStatus(200);
        $res80->assertSee('PAY-2026-9001');
        $res80->assertSee('80MM');

        // 58mm
        $res58 = $this->actingAs($this->user)
            ->get(route('documents.receipt', ['payment' => $payment->id, 'format' => '58mm']));
        $res58->assertStatus(200);
        $res58->assertSee('58MM');

        // A4
        $resA4 = $this->actingAs($this->user)
            ->get(route('documents.receipt', ['payment' => $payment->id, 'format' => 'a4']));
        $resA4->assertStatus(200);
        $resA4->assertSee('A4');
    }

    public function test_it_handles_clearance_noc_issuance_and_printing(): void
    {
        // 1. Should fail when balance remains
        $responseFail = $this->actingAs($this->user)
            ->post(route('documents.issue-noc', $this->agreement->id));
        $responseFail->assertRedirect();
        $responseFail->assertSessionHas('error');

        // 2. Mark agreement completed with zero balance
        $this->agreement->update([
            'remaining_balance' => 0,
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);

        $responseSuccess = $this->actingAs($this->user)
            ->post(route('documents.issue-noc', $this->agreement->id));
        $responseSuccess->assertRedirect(route('documents.print', [
            'type' => 'clearance-noc',
            'agreement' => $this->agreement->id,
        ]));

        // 3. Print Clearance Certificate
        $printResponse = $this->actingAs($this->user)
            ->get(route('documents.print', ['type' => 'clearance-noc', 'agreement' => $this->agreement->id]));

        $printResponse->assertStatus(200);
        $printResponse->assertSee('CERTIFICATE OF FULL CONTRACT CLEARANCE');
        $printResponse->assertSee('ZERO (0.00) OUTSTANDING BALANCE');
    }

    public function test_cross_tenant_access_is_forbidden(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Rival Electronics',
            'slug' => 'rival-elec',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Other Branch',
            'code' => 'OTH-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $otherRole = Role::where('name', 'company_admin')->firstOrFail();
        $otherUser = User::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Other Admin',
            'email' => 'other@rival.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $otherRole->id,
            'status' => 'active',
        ]);

        // Attempt to print agreement of company 1 with user of company 2
        $response = $this->actingAs($otherUser)
            ->get(route('documents.print', ['type' => 'installment-contract', 'agreement' => $this->agreement->id]));

        $response->assertStatus(403);
    }
}
