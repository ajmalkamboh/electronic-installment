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

class SuperAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $tenantAdmin;

    protected Company $company;

    protected SaaSPlan $starterPlan;

    protected SaaSPlan $growthPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Platform Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'super@installment.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        // Standard Tenant Company
        $this->company = Company::create([
            'name' => 'National Electronics',
            'legal_name' => 'National Electronics Pvt Ltd',
            'email' => 'contact@nationalelectronics.pk',
            'city' => 'Faisalabad',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'FSD Main Branch',
            'code' => 'FSD-01',
            'city' => 'Faisalabad',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->tenantAdmin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'name' => 'Faisalabad Manager',
            'email' => 'admin@nationalelectronics.pk',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        $this->starterPlan = SaaSPlan::create([
            'name' => 'Starter Tier',
            'slug' => 'starter-tier',
            'price_monthly' => 5000,
            'price_yearly' => 50000,
            'max_users' => 3,
            'max_branches' => 1,
            'max_active_agreements' => 50,
            'max_monthly_transactions' => 300,
            'is_active' => true,
            'features' => ['documents_pdf'],
        ]);

        $this->growthPlan = SaaSPlan::create([
            'name' => 'Growth Tier',
            'slug' => 'growth-tier',
            'price_monthly' => 15000,
            'price_yearly' => 150000,
            'max_users' => 10,
            'max_branches' => 3,
            'max_active_agreements' => 300,
            'max_monthly_transactions' => 1500,
            'is_active' => true,
            'features' => ['documents_pdf', 'sms_notifications'],
        ]);

        Subscription::create([
            'company_id' => $this->company->id,
            'saas_plan_id' => $this->starterPlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function test_non_super_admin_cannot_access_admin_command_center(): void
    {
        $response = $this->actingAs($this->tenantAdmin)->get(route('admin.dashboard'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_dashboard_and_companies(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Platform Super Admin Command Center');
        $response->assertSee('National Electronics');

        $dirResponse = $this->actingAs($this->superAdmin)->get(route('admin.companies.index'));
        $dirResponse->assertStatus(200);
        $dirResponse->assertSee('National Electronics');
    }

    public function test_super_admin_can_onboard_new_tenant(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.companies.store'), [
            'company_name' => 'Islamabad Appliances',
            'legal_name' => 'Islamabad Appliances SMC Ltd',
            'email' => 'sales@isbappliances.pk',
            'phone' => '+92 51 1112233',
            'city' => 'Islamabad',
            'address' => 'Blue Area Islamabad',
            'plan_id' => $this->growthPlan->id,
            'is_trial' => 1,
            'admin_name' => 'Kamran Khan',
            'admin_email' => 'kamran@isbappliances.pk',
            'admin_password' => 'SecurePass123!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', ['email' => 'sales@isbappliances.pk', 'status' => 'trial']);
        $this->assertDatabaseHas('users', ['email' => 'kamran@isbappliances.pk', 'role' => 'company_admin']);

        $newCompany = Company::where('email', 'sales@isbappliances.pk')->first();
        $this->assertNotNull($newCompany->currentSubscription());
        $this->assertSame($this->growthPlan->id, $newCompany->currentSubscription()->saas_plan_id);
    }

    public function test_super_admin_can_change_tenant_plan(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.companies.change-plan', $this->company), [
            'plan_id' => $this->growthPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $response->assertRedirect();
        $this->assertSame($this->growthPlan->id, $this->company->fresh()->currentPlan()->id);
        $this->assertSame('yearly', $this->company->fresh()->currentSubscription()->billing_cycle);
    }

    public function test_super_admin_can_toggle_tenant_suspension(): void
    {
        // Suspend
        $response = $this->actingAs($this->superAdmin)->post(route('admin.companies.toggle-status', $this->company), [
            'reason' => 'Administrative investigation',
        ]);

        $response->assertRedirect();
        $this->assertSame('suspended', $this->company->fresh()->status);
        $this->assertSame('suspended', $this->company->fresh()->currentSubscription()->status);

        // Reactivate
        $response2 = $this->actingAs($this->superAdmin)->post(route('admin.companies.toggle-status', $this->company));
        $response2->assertRedirect();
        $this->assertSame('active', $this->company->fresh()->status);
        $this->assertSame('active', $this->company->fresh()->currentSubscription()->status);
    }

    public function test_super_admin_can_manage_saas_plans(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.plans.store'), [
            'name' => 'Enterprise Custom',
            'description' => 'Custom enterprise plan',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_users' => 200,
            'max_branches' => 50,
            'max_active_agreements' => 10000,
            'max_monthly_transactions' => 50000,
            'trial_days' => 30,
            'is_active' => 1,
            'features' => ['documents_pdf', 'whatsapp_notifications', 'general_ledger'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('saas_plans', ['name' => 'Enterprise Custom']);
    }

    public function test_super_admin_can_record_manual_payment(): void
    {
        $sub = $this->company->currentSubscription();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.subscriptions.record-payment', $sub), [
            'amount_paid' => 5000,
            'payment_method' => 'bank_transfer',
            'months' => 2,
            'notes' => 'Invoice #10920 paid via Allied Bank',
        ]);

        $response->assertRedirect();
        $this->assertSame('active', $sub->fresh()->status);
        $this->assertEquals(5000, (float) $sub->fresh()->amount_paid);
    }
}
