<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantSubscriptionQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Branch $branch;

    protected User $admin;

    protected Role $staffRole;

    protected SaaSPlan $starterPlan;

    protected SaaSPlan $growthPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'name' => 'Quota Test Store',
            'email' => 'quota@test.com',
            'city' => 'Sialkot',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Sialkot Main',
            'code' => 'SKT-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->staffRole = Role::where('name', 'viewer')->first() ?? Role::create([
            'name' => 'viewer',
            'display_name' => 'Viewer',
            'guard_name' => 'web',
            'is_system' => true,
        ]);

        // Starter: max 2 users, max 1 branch
        $this->starterPlan = SaaSPlan::create([
            'name' => 'Starter Limited',
            'slug' => 'starter-limited',
            'max_users' => 2,
            'max_branches' => 1,
            'max_active_agreements' => 5,
            'max_monthly_transactions' => 10,
            'is_active' => true,
        ]);

        $this->growthPlan = SaaSPlan::create([
            'name' => 'Growth Expanded',
            'slug' => 'growth-expanded',
            'max_users' => 10,
            'max_branches' => 5,
            'max_active_agreements' => 100,
            'max_monthly_transactions' => 500,
            'is_active' => true,
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'saas_plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // 1st User (Admin)
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Admin User',
            'email' => 'admin@quota.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);
    }

    public function test_tenant_admin_can_view_subscription_portal(): void
    {
        $response = $this->actingAs($this->admin)->get(route('subscription.index'));
        $response->assertStatus(200);
        $response->assertSee('Starter Limited');
        $response->assertSee('Resource Quota Utilization');
    }

    public function test_branch_creation_is_blocked_when_limit_reached(): void
    {
        // Currently has 1 branch, limit is 1.
        $response = $this->actingAs($this->admin)->post(route('branches.store'), [
            'name' => 'Second Branch',
            'code' => 'SKT-02',
            'city' => 'Sialkot',
            'phone' => '+92 52 1111111',
            'address' => 'Cantt Road',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('branches', ['code' => 'SKT-02']);
    }

    public function test_user_creation_is_blocked_when_seat_limit_reached(): void
    {
        // Currently has 1 user (admin). Limit is 2.
        // Adding 2nd user should succeed:
        $res1 = $this->actingAs($this->admin)->post(route('staff.store'), [
            'name' => 'Staff Member Two',
            'email' => 'staff2@quota.test',
            'employee_code' => 'EMP-002',
            'branch_id' => $this->branch->id,
            'role_id' => $this->staffRole->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
        ]);

        $res1->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'staff2@quota.test']);

        // Adding 3rd user should be blocked by middleware (limit is 2):
        $res2 = $this->actingAs($this->admin)->post(route('staff.store'), [
            'name' => 'Staff Member Three',
            'email' => 'staff3@quota.test',
            'employee_code' => 'EMP-003',
            'branch_id' => $this->branch->id,
            'role_id' => $this->staffRole->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'status' => 'active',
        ]);

        $res2->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'staff3@quota.test']);
    }

    public function test_tenant_admin_can_upgrade_plan_to_expand_quotas(): void
    {
        $response = $this->actingAs($this->admin)->post(route('subscription.upgrade'), [
            'plan_id' => $this->growthPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $response->assertRedirect(route('subscription.index'));
        $this->assertSame($this->growthPlan->id, $this->company->fresh()->currentPlan()->id);

        // Now branch creation should succeed because limit expanded to 5!
        $branchRes = $this->actingAs($this->admin)->post(route('branches.store'), [
            'name' => 'Second Branch',
            'code' => 'SKT-02',
            'city' => 'Sialkot',
            'phone' => '+92 52 1111111',
            'address' => 'Cantt Road',
        ]);

        $branchRes->assertRedirect();
        $this->assertDatabaseHas('branches', ['code' => 'SKT-02']);
    }
}
