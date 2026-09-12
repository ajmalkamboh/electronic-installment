<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Services\Tenant\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionService $service;

    protected Company $company;

    protected SaaSPlan $starterPlan;

    protected SaaSPlan $growthPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubscriptionService::class);

        $this->company = Company::create([
            'name' => 'Prime Retail',
            'email' => 'prime@example.com',
            'city' => 'Lahore',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->starterPlan = SaaSPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 5000,
            'price_yearly' => 50000,
            'max_users' => 3,
            'max_branches' => 1,
            'max_active_agreements' => 50,
            'trial_days' => 14,
        ]);

        $this->growthPlan = SaaSPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'price_monthly' => 15000,
            'price_yearly' => 150000,
            'max_users' => 10,
            'max_branches' => 3,
            'max_active_agreements' => 300,
            'trial_days' => 14,
        ]);
    }

    public function test_subscribe_creates_active_monthly_subscription(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan, [
            'is_trial' => false,
            'billing_cycle' => 'monthly',
        ]);

        $this->assertInstanceOf(Subscription::class, $sub);
        $this->assertSame('active', $sub->status);
        $this->assertSame('monthly', $sub->billing_cycle);
        $this->assertTrue($sub->isActive());
        $this->assertFalse($sub->isTrial());
        $this->assertSame('active', $this->company->fresh()->status);
        $this->assertEquals(5000, (float) $sub->amount_paid);
    }

    public function test_subscribe_creates_trial_subscription_with_trial_ends_at(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan, [
            'is_trial' => true,
            'trial_days' => 14,
        ]);

        $this->assertSame('trial', $sub->status);
        $this->assertTrue($sub->isTrial());
        $this->assertTrue($sub->isActive());
        $this->assertNotNull($sub->trial_ends_at);
        $this->assertSame('trial', $this->company->fresh()->status);
    }

    public function test_change_plan_cancels_old_and_activates_new_plan(): void
    {
        $oldSub = $this->service->subscribe($this->company, $this->starterPlan);

        $newSub = $this->service->changePlan($this->company, $this->growthPlan, 'yearly');

        $this->assertSame('cancelled', $oldSub->fresh()->status);
        $this->assertSame('active', $newSub->status);
        $this->assertSame($this->growthPlan->id, $newSub->saas_plan_id);
        $this->assertSame('yearly', $newSub->billing_cycle);
        $this->assertEquals(150000, (float) $newSub->amount_paid);
    }

    public function test_renew_subscription_extends_period(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan);
        $initialEnd = $sub->ends_at;

        $renewed = $this->service->renewSubscription($sub, 30, 5000.00, 'bank_transfer');

        $this->assertTrue($renewed->ends_at->greaterThan($initialEnd));
        $this->assertSame('active', $renewed->status);
    }

    public function test_suspend_and_reactivate_company(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan);

        $this->service->suspendCompany($this->company, 'Non-payment after grace period');

        $this->assertSame('suspended', $this->company->fresh()->status);
        $this->assertSame('suspended', $sub->fresh()->status);
        $this->assertTrue($sub->fresh()->isSuspended());

        $this->service->reactivateCompany($this->company);

        $this->assertSame('active', $this->company->fresh()->status);
        $this->assertSame('active', $sub->fresh()->status);
    }

    public function test_evaluate_company_status_with_grace_period_and_auto_suspension(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan);

        // 1. Normal active date
        $this->assertSame('active', $this->service->evaluateCompanyStatus($this->company));

        // 2. Expired 2 days ago (within 7-day grace period) -> past_due
        $sub->ends_at = Carbon::now()->subDays(2);
        $sub->save();

        $this->assertSame('past_due', $this->service->evaluateCompanyStatus($this->company));

        // 3. Expired 10 days ago (past 7-day grace period) -> suspended
        $sub->ends_at = Carbon::now()->subDays(10);
        $sub->save();

        $this->assertSame('suspended', $this->service->evaluateCompanyStatus($this->company));
    }

    public function test_process_scheduled_lifecycle_checks(): void
    {
        $sub = $this->service->subscribe($this->company, $this->starterPlan);
        $sub->ends_at = Carbon::now()->subDays(10); // Expired beyond 7-day grace
        $sub->save();

        $report = $this->service->processScheduledLifecycleChecks();

        $this->assertSame(1, $report['checked']);
        $this->assertSame(1, $report['updated']);
        $this->assertSame(1, $report['suspended']);
        $this->assertSame('suspended', $this->company->fresh()->status);
    }
}
