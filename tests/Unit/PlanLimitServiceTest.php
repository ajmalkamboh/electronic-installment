<?php

namespace Tests\Unit;

use App\Exceptions\FeatureDisabledException;
use App\Exceptions\QuotaExceededException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Tenant\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PlanLimitService $limitService;

    protected Company $company;

    protected SaaSPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->limitService = app(PlanLimitService::class);

        $this->company = Company::create([
            'name' => 'Limit Test Electronics',
            'email' => 'limit@example.com',
            'city' => 'Multan',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->plan = SaaSPlan::create([
            'name' => 'Starter Tier',
            'slug' => 'starter-tier',
            'max_users' => 2,
            'max_branches' => 1,
            'max_active_agreements' => 5,
            'max_monthly_transactions' => 10,
            'features' => ['documents_pdf', 'basic_reporting'],
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'saas_plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function test_user_quota_check_and_assertion(): void
    {
        // Initial state: 0 users
        $check = $this->limitService->checkUserQuota($this->company);
        $this->assertTrue($check['allowed']);
        $this->assertSame(0, $check['current']);
        $this->assertSame(2, $check['limit']);

        // Add 1 user
        User::create([
            'company_id' => $this->company->id,
            'name' => 'User 1',
            'email' => 'u1@test.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->assertTrue($this->limitService->checkUserQuota($this->company)['allowed']);

        // Add 2nd user (quota reached)
        User::create([
            'company_id' => $this->company->id,
            'name' => 'User 2',
            'email' => 'u2@test.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
        ]);

        $quota = $this->limitService->checkUserQuota($this->company);
        $this->assertFalse($quota['allowed']);
        $this->assertSame(2, $quota['current']);

        // Assert throws exception
        $this->expectException(QuotaExceededException::class);
        $this->limitService->assertCanCreateUser($this->company);
    }

    public function test_branch_quota_check_and_assertion(): void
    {
        // Limit is 1 branch
        Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Outlet',
            'code' => 'MUL-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $check = $this->limitService->checkBranchQuota($this->company);
        $this->assertFalse($check['allowed']);
        $this->assertSame(1, $check['current']);

        $this->expectException(QuotaExceededException::class);
        $this->limitService->assertCanCreateBranch($this->company);
    }

    public function test_feature_entitlement_and_assertion(): void
    {
        $this->assertTrue($this->limitService->canAccessFeature($this->company, 'documents_pdf'));
        $this->assertFalse($this->limitService->canAccessFeature($this->company, 'whatsapp_notifications'));

        $this->expectException(FeatureDisabledException::class);
        $this->limitService->assertFeatureEnabled($this->company, 'whatsapp_notifications');
    }
}
