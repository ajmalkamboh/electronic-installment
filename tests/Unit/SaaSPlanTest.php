<?php

namespace Tests\Unit;

use App\Models\SaaSPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaaSPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_saas_plan_with_casted_attributes(): void
    {
        $plan = SaaSPlan::create([
            'name' => 'Starter Pro',
            'slug' => 'starter-pro',
            'description' => 'Test Starter Plan',
            'price_monthly' => 4999.50,
            'price_yearly' => 49990.00,
            'max_users' => 5,
            'max_branches' => 2,
            'max_active_agreements' => 150,
            'max_monthly_transactions' => 750,
            'features' => ['documents_pdf', 'basic_reporting'],
            'is_active' => true,
            'trial_days' => 14,
        ]);

        $this->assertDatabaseHas('saas_plans', ['slug' => 'starter-pro']);
        $this->assertNotEmpty($plan->ulid);
        $this->assertSame(5, $plan->max_users);
        $this->assertSame(2, $plan->max_branches);
        $this->assertSame(150, $plan->max_active_agreements);
        $this->assertSame(750, $plan->max_monthly_transactions);
        $this->assertTrue($plan->is_active);
    }

    public function test_has_feature_evaluation(): void
    {
        $plan = SaaSPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'max_users' => 10,
            'max_branches' => 3,
            'max_active_agreements' => 300,
            'features' => ['documents_pdf', 'sms_notifications'],
        ]);

        $this->assertTrue($plan->hasFeature('documents_pdf'));
        $this->assertTrue($plan->hasFeature('sms_notifications'));
        $this->assertFalse($plan->hasFeature('general_ledger'));

        // Wildcard features
        $enterprise = SaaSPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'features' => ['*'],
        ]);

        $this->assertTrue($enterprise->hasFeature('general_ledger'));
        $this->assertTrue($enterprise->hasFeature('whatsapp_notifications'));
        $this->assertTrue($enterprise->hasFeature('any_custom_feature'));
    }

    public function test_capacity_check_methods(): void
    {
        $plan = SaaSPlan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'max_users' => 3,
            'max_branches' => 1,
            'max_active_agreements' => 50,
            'max_monthly_transactions' => 200,
        ]);

        $this->assertTrue($plan->canHaveUsers(2));
        $this->assertFalse($plan->canHaveUsers(3));
        $this->assertFalse($plan->canHaveUsers(4));

        $this->assertTrue($plan->canHaveBranches(0));
        $this->assertFalse($plan->canHaveBranches(1));

        $this->assertTrue($plan->canHaveAgreements(49));
        $this->assertFalse($plan->canHaveAgreements(50));

        $this->assertTrue($plan->canHaveTransactions(199));
        $this->assertFalse($plan->canHaveTransactions(200));
    }

    public function test_active_scope_filters_out_inactive_plans(): void
    {
        SaaSPlan::create(['name' => 'Active 1', 'slug' => 'active-1', 'is_active' => true]);
        SaaSPlan::create(['name' => 'Disabled 1', 'slug' => 'disabled-1', 'is_active' => false]);

        $activePlans = SaaSPlan::active()->get();

        $this->assertTrue($activePlans->contains('slug', 'active-1'));
        $this->assertFalse($activePlans->contains('slug', 'disabled-1'));
    }
}
