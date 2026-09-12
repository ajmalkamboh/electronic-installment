<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\Product;
use App\Models\RecoveryCase;
use App\Models\SerializedItem;
use App\Models\User;
use App\Services\Recovery\RecoveryEscalationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected User $manager;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $serializedItem;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected RecoveryEscalationService $escalationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Kamboh Tech Emporium',
            'email' => 'recovery@kamboh.pk',
            'city' => 'Lahore',
            'grace_period_days' => 5,
            'late_fee_type' => 'fixed',
            'late_fee_amount' => 500.00,
            'max_penalty_cap' => 2500.00,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Gulberg Showroom',
            'code' => 'GLB',
            'city' => 'Lahore',
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role' => 'company_admin',
        ]);

        $this->manager = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role' => 'branch_manager',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Bilal Hassan',
            'cnic' => '35201-1234567-9',
            'mobile_primary' => '03009988776',
            'present_address' => 'Plot 44, Block C, Faisal Town, Lahore',
        ]);

        $category = \App\Models\ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Televisions',
            'slug' => 'televisions',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand' => 'Samsung',
            'model_name' => '55 Inch Crystal 4K UHD',
            'base_cash_price' => 150000.00,
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'serial_number' => 'SAM-55-998877',
            'status' => 'disbursed',
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Plan',
            'tenure_months' => 12,
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->admin->id,
            'account_number' => 'AGR-GLB-2026-009',
            'status' => 'active',
            'cash_price' => 150000.00,
            'down_payment_amount' => 30000.00,
            'down_payment_paid' => 30000.00,
            'financed_principal' => 120000.00,
            'markup_rate_pct' => 25.00,
            'markup_amount' => 30000.00,
            'total_financed' => 150000.00,
            'total_payable' => 180000.00,
            'installment_amount' => 12500.00,
            'tenure_months' => 12,
            'total_installments' => 12,
            'remaining_balance' => 150000.00,
            'start_date' => '2026-01-01',
            'first_due_date' => '2026-02-01',
            'maturity_date' => '2027-01-01',
        ]);

        $this->escalationService = app(RecoveryEscalationService::class);
    }

    public function test_escalation_stage_determination(): void
    {
        $this->assertEquals('grace_period', $this->escalationService->determineEscalationStage(3));
        $this->assertEquals('overdue_reminder', $this->escalationService->determineEscalationStage(10));
        $this->assertEquals('tele_collection', $this->escalationService->determineEscalationStage(20));
        $this->assertEquals('field_recovery', $this->escalationService->determineEscalationStage(45));
        $this->assertEquals('legal_notice', $this->escalationService->determineEscalationStage(70));
        $this->assertEquals('repossession_pending', $this->escalationService->determineEscalationStage(95));
    }

    public function test_sync_recovery_case_for_overdue_agreement(): void
    {
        // Add overdue installment (due 45 days ago => field_recovery)
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subDays(45)->toDateString(),
            'principal_amount' => 10000.00,
            'markup_amount' => 2500.00,
            'total_amount' => 12500.00,
            'remaining_balance' => 12500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 500.00,
            'status' => 'overdue',
        ]);

        $case = $this->escalationService->syncAgreementRecoveryCase($this->agreement);

        $this->assertInstanceOf(RecoveryCase::class, $case);
        $this->assertEquals('field_recovery', $case->stage);
        $this->assertEquals(45, $case->days_past_due);
        $this->assertEquals(12500.00, (float) $case->total_overdue_amount);
        $this->assertEquals(500.00, (float) $case->total_late_fees);
    }

    public function test_issue_notice_and_view_printable_a4_letter(): void
    {
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subDays(65)->toDateString(),
            'principal_amount' => 10000.00,
            'markup_amount' => 2500.00,
            'total_amount' => 12500.00,
            'remaining_balance' => 12500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 500.00,
            'status' => 'overdue',
        ]);

        $case = $this->escalationService->syncAgreementRecoveryCase($this->agreement);

        $notice = $this->escalationService->issueNotice(
            $case,
            'legal_notice',
            'customer',
            $this->admin,
            ['deadline_days' => 7, 'delivery_channel' => 'hand_delivery']
        );

        $this->assertDatabaseHas('recovery_notices', [
            'id' => $notice->id,
            'recovery_case_id' => $case->id,
            'notice_type' => 'legal_notice',
        ]);

        $case->refresh();
        $this->assertNotNull($case->legal_notice_at);
        $this->assertEquals($notice->notice_number, $case->legal_notice_ref);

        // Test printable view endpoint
        $response = $this->actingAs($this->admin)->get(route('recovery.notices.print', $notice));
        $response->assertStatus(200);
        $response->assertSee('LEGAL NOTICE');
        $response->assertSee($notice->notice_number);
    }

    public function test_repossession_authorization_and_execution_workflow(): void
    {
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subDays(92)->toDateString(),
            'principal_amount' => 10000.00,
            'markup_amount' => 2500.00,
            'total_amount' => 12500.00,
            'remaining_balance' => 12500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 500.00,
            'status' => 'overdue',
        ]);

        $case = $this->escalationService->syncAgreementRecoveryCase($this->agreement);

        // 1. Authorize Repossession
        $this->escalationService->authorizeRepossession(
            $case,
            $this->manager,
            'Customer unresponsive for 90+ days; repossession warrant issued.'
        );

        $case->refresh();
        $this->assertEquals('repossession_pending', $case->stage);
        $this->assertEquals('escalated', $case->status);
        $this->assertNotNull($case->repossession_authorized_at);

        $this->agreement->refresh();
        $this->assertEquals('defaulted', $this->agreement->status);

        // 2. Execute Repossession
        $this->escalationService->executeRepossession(
            $case,
            $this->admin,
            'good',
            'Retrieved from customer home with remote and power cables.'
        );

        $case->refresh();
        $this->assertEquals('repossessed', $case->stage);
        $this->assertEquals('repossessed', $case->status);
        $this->assertEquals('good', $case->repossessed_condition);

        // Item returned to inventory
        $this->serializedItem->refresh();
        $this->assertEquals('repossessed', $this->serializedItem->status);
    }

    public function test_bad_debt_write_off(): void
    {
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subDays(120)->toDateString(),
            'principal_amount' => 10000.00,
            'markup_amount' => 2500.00,
            'total_amount' => 12500.00,
            'remaining_balance' => 12500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 500.00,
            'status' => 'overdue',
        ]);

        $case = $this->escalationService->syncAgreementRecoveryCase($this->agreement);

        $this->escalationService->writeOffCase(
            $case,
            $this->admin,
            12500.00,
            'Debtor relocated overseas permanently; verified unrecoverable by field team.'
        );

        $case->refresh();
        $this->assertEquals('written_off', $case->stage);
        $this->assertEquals('written_off', $case->status);
        $this->assertEquals(12500.00, (float) $case->written_off_amount);
    }

    public function test_recovery_dashboard_and_cases_http_endpoints(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recovery.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Late Fees &amp; Recovery Command Center', false);

        $responseCases = $this->actingAs($this->admin)->get(route('recovery.cases.index'));
        $responseCases->assertStatus(200);
        $responseCases->assertSee('Delinquency Recovery Cases');

        $responseLateFees = $this->actingAs($this->admin)->get(route('recovery.late-fees'));
        $responseLateFees->assertStatus(200);
        $responseLateFees->assertSee('Late Fee Ledger &amp; Supervisory Waivers', false);
    }

    public function test_artisan_delinquency_assessment_command(): void
    {
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => Carbon::today()->subDays(10)->toDateString(),
            'principal_amount' => 10000.00,
            'markup_amount' => 2500.00,
            'total_amount' => 12500.00,
            'remaining_balance' => 12500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $this->artisan('recovery:assess-delinquency', [
            '--company' => $this->company->id,
        ])->assertSuccessful();

        $this->agreement->refresh();
        $this->assertEquals('active', $this->agreement->status);

        $this->assertDatabaseHas('recovery_cases', [
            'installment_agreement_id' => $this->agreement->id,
            'stage' => 'overdue_reminder',
        ]);
    }
}
