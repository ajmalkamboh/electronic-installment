<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CollectionAssignment;
use App\Models\CollectionLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $manager;
    protected User $officer;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $activeAgreement;

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

        $managerRole = Role::where('name', 'branch_manager')->first() ?? Role::first();
        $this->manager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $managerRole->id,
            'role' => 'branch_manager',
            'name' => 'Manager Tariq',
            'email' => 'tariq@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $officerRole = Role::where('name', 'collection_officer')->first() ?? Role::first();
        $this->officer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $officerRole->id,
            'role' => 'collection_officer',
            'name' => 'Recovery Officer Imran',
            'email' => 'imran@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-9988776-1',
            'full_name' => 'Sohail Akhtar',
            'father_or_husband_name' => 'Akhtar Ali',
            'gender' => 'male',
            'mobile_primary' => '03011234567',
            'present_address' => 'Gulberg III, Lahore',
            'permanent_address' => 'Gulberg III, Lahore',
            'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Home Appliances',
            'slug' => 'home-appliances',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Waves Singer',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Waves',
            'model_name' => 'Deep Freezer 350L',
            'sku' => 'WAV-DF-350',
            'base_cash_price' => 120000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '6 Months Standard',
            'slug' => '6-months-standard',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->activeAgreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->manager->id,
            'account_number' => 'AGR-COL-001',
            'cash_price' => 120000,
            'down_payment_amount' => 24000,
            'down_payment_paid' => 24000,
            'down_payment_satisfied' => true,
            'financed_principal' => 96000,
            'markup_rate_pct' => 20.00,
            'markup_amount' => 9600,
            'total_financed' => 105600,
            'total_payable' => 129600,
            'remaining_balance' => 105600,
            'tenure_months' => 6,
            'total_installments' => 6,
            'paid_installments' => 0,
            'installment_amount' => 17600,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);

        (new ScheduleGenerator())->generateSchedule($this->activeAgreement);
    }

    public function test_user_can_view_collection_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('collections.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Field Recovery &amp; Collections', false);
        $response->assertSee('Daily Run Sheet');
        $response->assertSee('Drawer Handovers');
    }

    public function test_manager_can_view_assignments_registry_and_allocate_account(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('collections.assignments'));

        $response->assertStatus(200);
        $response->assertSee('Collection Account Assignments');
        $response->assertSee('Assign New Account');

        // Post new assignment
        $postResponse = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('collections.assignments.store'), [
                'installment_agreement_id' => $this->activeAgreement->id,
                'collection_officer_id' => $this->officer->id,
                'priority' => 'high',
                'notes' => 'Customer residence visit required this week.',
            ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('collection_assignments', [
            'installment_agreement_id' => $this->activeAgreement->id,
            'collection_officer_id' => $this->officer->id,
            'priority' => 'high',
            'status' => 'active',
        ]);
    }

    public function test_officer_can_view_daily_run_sheet(): void
    {
        // Assign agreement to officer
        CollectionAssignment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->activeAgreement->id,
            'collection_officer_id' => $this->officer->id,
            'assigned_by_id' => $this->manager->id,
            'assigned_date' => now()->toDateString(),
            'priority' => 'urgent',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->officer)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('collections.run-sheet'));

        $response->assertStatus(200);
        $response->assertSee('Daily Collection Run Sheet');
        $response->assertSee($this->customer->full_name);
        $response->assertSee($this->customer->mobile_primary);
        $response->assertSee($this->activeAgreement->account_number);
        $response->assertSee('Log Visit / PTP');
        $response->assertSee('Collect Cash');
    }

    public function test_officer_can_log_field_visit_and_ptp(): void
    {
        $ptpDate = now()->addDays(4)->toDateString();

        $response = $this->actingAs($this->officer)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('collections.logs.store'), [
                'installment_agreement_id' => $this->activeAgreement->id,
                'interaction_type' => 'field_visit',
                'interaction_status' => 'promise_to_pay',
                'promise_to_pay_date' => $ptpDate,
                'promised_amount' => 17600,
                'location_notes' => 'House # 44, checked electricity meter',
                'notes' => 'Customer promised full payment on 16th.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('collection_logs', [
            'installment_agreement_id' => $this->activeAgreement->id,
            'collection_officer_id' => $this->officer->id,
            'interaction_status' => 'promise_to_pay',
            'ptp_status' => 'pending',
            'promised_amount' => 17600,
        ]);
    }

    public function test_officer_can_record_field_cash_collection_in_submitted_status(): void
    {
        $response = $this->actingAs($this->officer)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('collections.payments.store'), [
                'installment_agreement_id' => $this->activeAgreement->id,
                'amount' => 17600,
                'reference_number' => 'FIELD-BK-1102',
                'notes' => 'Collected in cash from customer residence',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment = Payment::where('installment_agreement_id', $this->activeAgreement->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('submitted', $payment->status);
        $this->assertEquals($this->officer->id, $payment->collector_id);
        $this->assertNull($payment->cashier_id);
        $this->assertEquals(17600, (float) $payment->amount);
    }

    public function test_cashier_can_acknowledge_field_handover_into_drawer(): void
    {
        // 1. Record submitted field collection
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->activeAgreement->id,
            'customer_id' => $this->customer->id,
            'collector_id' => $this->officer->id,
            'cashier_id' => null,
            'payment_number' => 'PAY-FIELD-9901',
            'payment_date' => now(),
            'amount' => 17600,
            'payment_method' => 'cash',
            'status' => 'submitted',
        ]);

        // 2. View handovers screen
        $viewResponse = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('collections.handovers'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Field Cash Drawer Handover');
        $viewResponse->assertSee('PAY-FIELD-9901');
        $viewResponse->assertSee(number_format(17600));

        // 3. Acknowledge handover
        $ackResponse = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('collections.handovers.acknowledge'), [
                'payment_id' => $payment->id,
                'notes' => 'Verified notes and accepted into drawer',
            ]);

        $ackResponse->assertRedirect();
        $ackResponse->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('acknowledged', $payment->status);
        $this->assertEquals($this->manager->id, $payment->cashier_id);
        $this->assertStringContainsString('Verified notes and accepted into drawer', $payment->notes);
    }

    public function test_printable_run_sheet_renders(): void
    {
        CollectionAssignment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->activeAgreement->id,
            'collection_officer_id' => $this->officer->id,
            'assigned_by_id' => $this->manager->id,
            'assigned_date' => now()->toDateString(),
            'priority' => 'normal',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->officer)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('collections.run-sheet.print', ['officer_id' => $this->officer->id]));

        $response->assertStatus(200);
        $response->assertSee('Daily Field Collection Itinerary');
        $response->assertSee($this->officer->name);
        $response->assertSee('Cash Verification');
    }

    public function test_tenant_isolation_prevents_acknowledging_foreign_handover(): void
    {
        $foreignCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Showroom Karachi',
            'slug' => 'foreign-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $foreignBranch = Branch::create([
            'company_id' => $foreignCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Karachi Saddar Showroom',
            'code' => 'KHI-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $foreignCustomer = Customer::create([
            'company_id' => $foreignCompany->id,
            'cnic' => '42101-1122334-5',
            'full_name' => 'Kamran Khan',
            'father_or_husband_name' => 'Tariq Khan',
            'gender' => 'male',
            'mobile_primary' => '03219988776',
            'present_address' => 'Karachi',
            'status' => 'active',
        ]);

        $foreignAgreement = InstallmentAgreement::create([
            'company_id' => $foreignCompany->id,
            'branch_id' => $foreignBranch->id,
            'customer_id' => $foreignCustomer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->manager->id,
            'account_number' => 'AGR-KHI-999',
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
            'tenure_months' => 6,
            'total_installments' => 6,
            'paid_installments' => 0,
            'installment_amount' => 16000,
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'maturity_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);

        $foreignPayment = Payment::create([
            'company_id' => $foreignCompany->id,
            'branch_id' => $foreignBranch->id,
            'installment_agreement_id' => $foreignAgreement->id,
            'customer_id' => $foreignCustomer->id,
            'collector_id' => $this->officer->id,
            'cashier_id' => null,
            'payment_number' => 'PAY-KHI-001',
            'payment_date' => now(),
            'amount' => 16000,
            'payment_method' => 'cash',
            'status' => 'submitted',
        ]);

        // Attempt to acknowledge foreign payment using Lahore manager session
        $response = $this->actingAs($this->manager)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('collections.handovers.acknowledge'), [
                'payment_id' => $foreignPayment->id,
            ]);

        $response->assertStatus(404);
        $foreignPayment->refresh();
        $this->assertEquals('submitted', $foreignPayment->status);
    }
}
