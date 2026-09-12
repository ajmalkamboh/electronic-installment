<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\Product;
use App\Models\User;
use App\Services\Recovery\LateFeeEngine;
use App\Services\Recovery\RecoveryEscalationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateFeeEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected LateFeeEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Al-Madina Electronics',
            'email' => 'recovery@almadina.pk',
            'phone' => '04235889900',
            'city' => 'Lahore',
            'grace_period_days' => 5,
            'late_fee_type' => 'fixed',
            'late_fee_amount' => 500.00,
            'max_penalty_cap' => 2000.00,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Showroom Gulberg',
            'code' => 'GLB',
            'city' => 'Lahore',
            'phone' => '04235889901',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role' => 'company_admin',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Tariq Mehmood',
            'cnic' => '35201-9988771-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'House 12, Street 4, Gulberg III, Lahore',
        ]);

        $category = \App\Models\ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Refrigerators',
            'slug' => 'refrigerators',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand' => 'Dawlance',
            'model_name' => 'Chrome 9170',
            'sku' => 'DAW-9170',
            'base_cash_price' => 90000.00,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => 'Standard 12M Plan',
            'tenure_months' => 12,
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->user->id,
            'account_number' => 'AGR-GLB-2026-001',
            'status' => 'active',
            'cash_price' => 90000.00,
            'down_payment_amount' => 18000.00,
            'down_payment_paid' => 18000.00,
            'financed_principal' => 72000.00,
            'markup_rate_pct' => 25.00,
            'markup_amount' => 18000.00,
            'total_financed' => 90000.00,
            'total_payable' => 108000.00,
            'installment_amount' => 7500.00,
            'tenure_months' => 12,
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 90000.00,
            'start_date' => '2026-01-01',
            'first_due_date' => '2026-02-01',
            'maturity_date' => '2027-01-01',
        ]);

        $this->engine = app(LateFeeEngine::class);
    }

    public function test_zero_late_fee_within_grace_period(): void
    {
        // Due on 2026-02-01, evaluating on 2026-02-04 (3 days overdue, <= 5 days grace)
        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-02-04'));

        $this->assertEquals(0.00, $fee);
    }

    public function test_fixed_late_fee_accrues_past_grace_period(): void
    {
        // Due on 2026-02-01, evaluating on 2026-02-08 (7 days overdue, > 5 days grace)
        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-02-08'));

        $this->assertEquals(500.00, $fee);
    }

    public function test_daily_penalty_calculation(): void
    {
        // Configure company for daily penalty: PKR 50/day
        $this->company->update([
            'late_fee_type' => 'daily_penalty',
            'late_fee_amount' => 50.00,
            'grace_period_days' => 5,
        ]);

        // Due on 2026-02-01, evaluating on 2026-02-15 (14 days overdue)
        // Penalty days = 14 - 5 = 9 days => 9 * 50 = PKR 450
        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-02-15'));

        $this->assertEquals(450.00, $fee);
    }

    public function test_percentage_late_fee_calculation(): void
    {
        // 2% late fee on PKR 7500 installment => PKR 150
        $this->company->update([
            'late_fee_type' => 'percentage',
            'late_fee_amount' => 2.00,
            'grace_period_days' => 5,
        ]);

        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-02-10'));

        $this->assertEquals(150.00, $fee);
    }

    public function test_max_penalty_cap_is_strictly_enforced(): void
    {
        // Daily penalty PKR 200/day, cap = PKR 1000
        $this->company->update([
            'late_fee_type' => 'daily_penalty',
            'late_fee_amount' => 200.00,
            'grace_period_days' => 5,
            'max_penalty_cap' => 1000.00,
        ]);

        // 30 days overdue => (30 - 5) * 200 = PKR 5,000, but capped at 1,000
        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-03-03'));

        $this->assertEquals(1000.00, $fee);
    }

    public function test_plan_overrides_company_settings(): void
    {
        // Plan overrides company fixed PKR 500 with fixed PKR 800 and grace 3 days
        $this->plan->update([
            'grace_period_days' => 3,
            'late_fee_type' => 'fixed',
            'late_fee_amount' => 800.00,
            'max_penalty_cap' => 3000.00,
        ]);

        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        // 4 days overdue (> 3 days grace) -> PKR 800
        $fee = $this->engine->calculateFee($schedule, Carbon::parse('2026-02-05'));

        $this->assertEquals(800.00, $fee);
    }

    public function test_accrue_late_fee_updates_schedule_status_to_overdue(): void
    {
        $schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 6000.00,
            'markup_amount' => 1500.00,
            'total_amount' => 7500.00,
            'remaining_balance' => 7500.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 0.00,
            'status' => 'due',
        ]);

        $netFee = $this->engine->accrueLateFee($schedule, Carbon::parse('2026-02-10'));

        $schedule->refresh();
        $this->assertEquals(500.00, $netFee);
        $this->assertEquals('500.00', (string) $schedule->late_fee_amount);
        $this->assertEquals('overdue', $schedule->status);
    }
}
