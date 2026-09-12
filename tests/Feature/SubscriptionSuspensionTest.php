<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Branch $branch;

    protected User $admin;

    protected User $cashier;

    protected SaaSPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'name' => 'Delinquent Store',
            'email' => 'delinquent@test.com',
            'city' => 'Gujranwala',
            'currency' => 'PKR',
            'status' => 'suspended', // Suspended company
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'GRW Branch',
            'code' => 'GRW-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin@delinquent.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        $this->cashier = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Cashier User',
            'email' => 'cashier@delinquent.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $this->plan = SaaSPlan::create([
            'name' => 'Growth Tier',
            'slug' => 'growth-tier',
            'max_users' => 10,
            'max_branches' => 3,
            'max_active_agreements' => 100,
            'is_active' => true,
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'saas_plan_id' => $this->plan->id,
            'status' => 'suspended',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(15), // Expired past grace period
        ]);
    }

    public function test_non_admin_staff_cannot_access_system_when_company_is_suspended(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_company_admin_is_redirected_to_subscription_portal_from_operational_routes(): void
    {
        // When admin attempts to access customers, they are redirected to subscription
        $response = $this->actingAs($this->admin)->get(route('customers.index'));

        $response->assertRedirect(route('subscription.index'));
        $response->assertSessionHas('error');
    }

    public function test_company_admin_can_access_subscription_portal_to_view_and_upgrade(): void
    {
        // Admin CAN access subscription portal while suspended
        $response = $this->actingAs($this->admin)->get(route('subscription.index'));

        $response->assertStatus(200);
        $response->assertSee('Your Subscription is Currently Suspended');
    }
}
