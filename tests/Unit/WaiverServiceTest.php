<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\LateFeeWaiver;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Recovery\WaiverService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaiverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected User $manager;
    protected User $cashier;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected InstallmentSchedule $schedule;
    protected WaiverService $waiverService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Kamboh Electronics',
            'email' => 'finance@kamboh.pk',
            'city' => 'Lahore',
            'late_fee_type' => 'fixed',
            'late_fee_amount' => 1000.00,
            'grace_period_days' => 5,
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZP',
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

        $this->cashier = User::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role' => 'cashier',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Zahid Ali',
            'cnic' => '35202-7776655-3',
            'mobile_primary' => '03219988776',
            'present_address' => 'Flat 5B, Model Town, Lahore',
        ]);

        $category = \App\Models\ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Air Conditioners',
            'slug' => 'air-conditioners',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'brand' => 'Haier',
            'model_name' => 'DC Inverter 1.5 Ton',
            'base_cash_price' => 135000.00,
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
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->admin->id,
            'account_number' => 'AGR-FZP-2026-002',
            'status' => 'active',
            'cash_price' => 135000.00,
            'down_payment_amount' => 27000.00,
            'down_payment_paid' => 27000.00,
            'financed_principal' => 108000.00,
            'markup_rate_pct' => 25.00,
            'markup_amount' => 27000.00,
            'total_financed' => 135000.00,
            'total_payable' => 162000.00,
            'installment_amount' => 11250.00,
            'tenure_months' => 12,
            'total_installments' => 12,
            'remaining_balance' => 135000.00,
            'start_date' => '2026-01-01',
            'first_due_date' => '2026-02-01',
            'maturity_date' => '2027-01-01',
        ]);

        $this->schedule = InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_number' => 1,
            'due_date' => '2026-02-01',
            'principal_amount' => 9000.00,
            'markup_amount' => 2250.00,
            'total_amount' => 11250.00,
            'remaining_balance' => 11250.00,
            'paid_amount' => 0.00,
            'late_fee_amount' => 1000.00, // PKR 1,000 accrued late fee
            'status' => 'overdue',
        ]);

        $this->waiverService = app(WaiverService::class);
    }

    public function test_branch_manager_can_waive_late_fee_with_audit_trail(): void
    {
        $waiver = $this->waiverService->waiveLateFee(
            $this->schedule,
            600.00,
            $this->manager,
            'Customer family bereavement documented; good payment track record previously.'
        );

        $this->assertInstanceOf(LateFeeWaiver::class, $waiver);
        $this->assertEquals(600.00, (float) $waiver->waived_amount);
        $this->assertEquals(1000.00, (float) $waiver->original_late_fee);
        $this->assertEquals(400.00, (float) $waiver->remaining_late_fee);
        $this->assertEquals($this->manager->id, $waiver->waived_by_id);

        $this->schedule->refresh();
        $this->assertEquals('400.00', (string) $this->schedule->late_fee_amount);

        $this->assertDatabaseHas('late_fee_waivers', [
            'id' => $waiver->id,
            'installment_schedule_id' => $this->schedule->id,
            'waived_by_id' => $this->manager->id,
            'waived_amount' => 600.00,
        ]);
    }

    public function test_unauthorized_user_is_forbidden_from_waiving_fees(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->waiverService->waiveLateFee(
            $this->schedule,
            500.00,
            $this->cashier,
            'Cashier trying to waive fee without manager authorization'
        );
    }

    public function test_waiver_cannot_exceed_accrued_penalty_amount(): void
    {
        $this->expectException(DomainException::class);

        // Schedule has PKR 1,000 accrued; attempting to waive PKR 1,500
        $this->waiverService->waiveLateFee(
            $this->schedule,
            1500.00,
            $this->admin,
            'Attempting waiver larger than actual fee'
        );
    }

    public function test_waiver_requires_mandatory_justification_reason(): void
    {
        $this->expectException(DomainException::class);

        $this->waiverService->waiveLateFee(
            $this->schedule,
            500.00,
            $this->admin,
            '   '
        );
    }

    public function test_full_waiver_reduces_fee_to_zero(): void
    {
        $this->waiverService->waiveLateFee(
            $this->schedule,
            1000.00,
            $this->admin,
            'Full 100% waiver approved on special director concession.'
        );

        $this->schedule->refresh();
        $this->assertEquals('0.00', (string) $this->schedule->late_fee_amount);
    }
}
