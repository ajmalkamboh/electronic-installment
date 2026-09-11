<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $cashier;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $activeAgreement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // 1. Tenant & Branch
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

        // 2. Cashier User
        $cashierRole = Role::where('name', 'cashier')->first() ?? Role::first();
        $this->cashier = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $cashierRole->id,
            'role' => 'cashier',
            'name' => 'Cashier Ahmad',
            'email' => 'ahmad@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        // 3. Customer
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35202-1234567-1',
            'full_name' => 'Muhammad Bilal',
            'father_or_husband_name' => 'Abdul Bilal',
            'gender' => 'male',
            'mobile_primary' => '03001234567',
            'present_address' => 'Model Town, Lahore',
            'permanent_address' => 'Model Town, Lahore',
            'status' => 'active',
        ]);

        // 4. Category & Product
        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Tech Supply Co',
            'contact_person' => 'Hamza',
            'phone' => '03009998877',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Samsung',
            'model_name' => 'Galaxy S25 Ultra',
            'sku' => 'SAM-S25U-512',
            'base_cash_price' => 300000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        // 5. Installment Plan
        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard',
            'slug' => '12-months-standard',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 24.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // 6. Active Agreement (Financed: 240,000 + 24% Markup = 297,600 / 12 = 24,800/mo)
        $this->activeAgreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->cashier->id,
            'account_number' => 'AGR-2026-0001',
            'cash_price' => 300000,
            'down_payment_amount' => 60000,
            'down_payment_paid' => 60000,
            'down_payment_satisfied' => true,
            'financed_principal' => 240000,
            'markup_rate_pct' => 24.00,
            'markup_amount' => 57600,
            'total_financed' => 297600,
            'total_payable' => 357600,
            'remaining_balance' => 297600,
            'tenure_months' => 12,
            'total_installments' => 12,
            'paid_installments' => 0,
            'installment_amount' => 24800,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Generate the 12 schedule rows
        (new ScheduleGenerator())->generateSchedule($this->activeAgreement);
    }

    public function test_user_can_view_payments_index(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('payments.index'));

        $response->assertStatus(200);
        $response->assertSee('Payments');
        $response->assertSee('Record Customer Payment');
    }

    public function test_user_can_view_create_payment_screen(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('payments.create', ['agreement_id' => $this->activeAgreement->id]));

        $response->assertStatus(200);
        $response->assertSee('Record Installment Payment');
        $response->assertSee($this->activeAgreement->account_number);
        $response->assertSee($this->customer->full_name);
    }

    public function test_cashier_can_record_payment_and_waterfall_allocates(): void
    {
        // 1 monthly installment = 24,800
        $paymentData = [
            'installment_agreement_id' => $this->activeAgreement->id,
            'amount' => 24800,
            'payment_method' => 'cash',
            'reference_number' => 'CASH-TEST-101',
            'notes' => '1st monthly installment collected in cash',
        ];

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('payments.store'), $paymentData);

        $payment = Payment::where('installment_agreement_id', $this->activeAgreement->id)->first();
        $this->assertNotNull($payment);

        $response->assertRedirect(route('payments.show', $payment->id));
        $response->assertSessionHas('success');

        // Check payment fields
        $this->assertEquals(24800, (float) $payment->amount);
        $this->assertEquals('cash', $payment->payment_method);
        $this->assertEquals($this->cashier->id, $payment->cashier_id);
        $this->assertEquals('acknowledged', $payment->status);

        // Check waterfall allocation
        $this->assertCount(1, $payment->allocations);
        $firstAllocation = $payment->allocations->first();
        $this->assertEquals(24800, (float) $firstAllocation->amount_allocated);

        // First schedule must now be marked paid
        $firstSchedule = $this->activeAgreement->schedules()->where('installment_number', 1)->first();
        $this->assertEquals('paid', $firstSchedule->status);
        $this->assertEquals(24800, (float) $firstSchedule->paid_amount);
        $this->assertEquals(0, (float) $firstSchedule->remaining_balance);

        // Agreement remaining balance & paid installments must be updated
        $this->activeAgreement->refresh();
        $this->assertEquals(297600 - 24800, (float) $this->activeAgreement->remaining_balance);
        $this->assertEquals(1, $this->activeAgreement->paid_installments);
    }

    public function test_partial_payment_marks_schedule_as_partially_paid(): void
    {
        // Pay half an installment: 12,400
        $paymentData = [
            'installment_agreement_id' => $this->activeAgreement->id,
            'amount' => 12400,
            'payment_method' => 'raast',
            'reference_number' => 'RAAST-88221',
            'notes' => 'Half installment payment',
        ];

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('payments.store'), $paymentData);

        $payment = Payment::where('installment_agreement_id', $this->activeAgreement->id)->first();
        $this->assertNotNull($payment);
        $response->assertRedirect(route('payments.show', $payment->id));

        $firstSchedule = $this->activeAgreement->schedules()->where('installment_number', 1)->first();
        $this->assertEquals('partially_paid', $firstSchedule->status);
        $this->assertEquals(12400, (float) $firstSchedule->paid_amount);
        $this->assertEquals(12400, (float) $firstSchedule->remaining_balance);
    }

    public function test_full_settlement_payment_auto_completes_agreement(): void
    {
        // Pay the entire remaining balance: 297,600
        $paymentData = [
            'installment_agreement_id' => $this->activeAgreement->id,
            'amount' => 297600,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'HBL-SETTLE-001',
            'notes' => 'Early full contract settlement in single bank transfer',
        ];

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('payments.store'), $paymentData);

        $this->activeAgreement->refresh();

        $this->assertEquals(0, (float) $this->activeAgreement->remaining_balance);
        $this->assertEquals(12, $this->activeAgreement->paid_installments);
        $this->assertEquals('completed', $this->activeAgreement->status);
        $this->assertNotNull($this->activeAgreement->completed_at);

        // Every schedule should now be marked paid
        $unpaidCount = $this->activeAgreement->schedules()->where('status', '!=', 'paid')->count();
        $this->assertEquals(0, $unpaidCount);
    }

    public function test_user_can_view_payment_receipt_dossier(): void
    {
        // Create a payment
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->activeAgreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->cashier->id,
            'payment_number' => 'RCPT-2026-9999',
            'payment_date' => now(),
            'amount' => 24800,
            'payment_method' => 'cash',
            'status' => 'acknowledged',
        ]);

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('payments.show', $payment->id));

        $response->assertStatus(200);
        $response->assertSee('RCPT-2026-9999');
        $response->assertSee($this->customer->full_name);
        $response->assertSee(number_format(24800));
        $response->assertSee('Print Official Receipt');
    }

    public function test_printable_receipt_renders_successfully(): void
    {
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->activeAgreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->cashier->id,
            'payment_number' => 'RCPT-2026-9999',
            'payment_date' => now(),
            'amount' => 24800,
            'payment_method' => 'cash',
            'status' => 'acknowledged',
        ]);

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('payments.print', $payment->id));

        $response->assertStatus(200);
        $response->assertSee('Installment Collection Receipt');
        $response->assertSee('RCPT-2026-9999');
        $response->assertSee($this->customer->cnic);
    }

    public function test_tenant_isolation_prevents_viewing_foreign_payment(): void
    {
        // Create another tenant
        $foreignCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Retailers Karachi',
            'slug' => 'foreign-retailers',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $foreignBranch = Branch::create([
            'company_id' => $foreignCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Saddar Branch',
            'code' => 'SDR-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $foreignCustomer = Customer::create([
            'company_id' => $foreignCompany->id,
            'cnic' => '42101-9988776-5',
            'full_name' => 'Kamran Khan',
            'father_or_husband_name' => 'Tariq Khan',
            'gender' => 'male',
            'mobile_primary' => '03219876543',
            'present_address' => 'Karachi',
            'permanent_address' => 'Karachi',
            'status' => 'active',
        ]);

        $foreignAgreement = InstallmentAgreement::create([
            'company_id' => $foreignCompany->id,
            'branch_id' => $foreignBranch->id,
            'customer_id' => $foreignCustomer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->cashier->id,
            'account_number' => 'AGR-KHI-0001',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 20000,
            'down_payment_satisfied' => true,
            'financed_principal' => 80000,
            'markup_rate_pct' => 20.00,
            'markup_amount' => 16000,
            'total_financed' => 96000,
            'total_payable' => 116000,
            'remaining_balance' => 96000,
            'tenure_months' => 12,
            'total_installments' => 12,
            'paid_installments' => 0,
            'installment_amount' => 8000,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'status' => 'active',
        ]);

        $foreignPayment = Payment::create([
            'company_id' => $foreignCompany->id,
            'branch_id' => $foreignBranch->id,
            'installment_agreement_id' => $foreignAgreement->id,
            'customer_id' => $foreignCustomer->id,
            'cashier_id' => $this->cashier->id,
            'payment_number' => 'RCPT-FOREIGN-01',
            'payment_date' => now(),
            'amount' => 8000,
            'payment_method' => 'cash',
            'status' => 'acknowledged',
        ]);

        // Attempt to access foreign payment from Lahore cashier session
        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('payments.show', $foreignPayment->id));

        $response->assertStatus(404);
    }

    public function test_payment_validation_fails_on_zero_amount(): void
    {
        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('payments.store'), [
                'installment_agreement_id' => $this->activeAgreement->id,
                'amount' => 0,
                'payment_method' => 'cash',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_cannot_record_payment_for_draft_agreement(): void
    {
        $draftAgreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->cashier->id,
            'account_number' => 'AGR-DRAFT-0001',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 0,
            'down_payment_satisfied' => false,
            'financed_principal' => 80000,
            'markup_rate_pct' => 20.00,
            'markup_amount' => 16000,
            'total_financed' => 96000,
            'total_payable' => 116000,
            'remaining_balance' => 96000,
            'tenure_months' => 12,
            'total_installments' => 12,
            'paid_installments' => 0,
            'installment_amount' => 8000,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->cashier)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('payments.store'), [
                'installment_agreement_id' => $draftAgreement->id,
                'amount' => 8000,
                'payment_method' => 'cash',
            ]);

        $response->assertSessionHasErrors('error');
    }
}
