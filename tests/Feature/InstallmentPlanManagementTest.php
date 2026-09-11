<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentPlan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallmentPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Al-Madina Electronics',
            'slug' => 'al-madina',
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

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Tariq Mehmood',
            'email' => 'tariq@almadina.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_installment_plans_index(): void
    {
        InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard Super Plan',
            'slug' => '12-months-standard-super-plan',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertSee('12 Months Standard Super Plan');
        $response->assertSee('25.00%');
    }

    public function test_admin_can_create_new_installment_plan(): void
    {
        $planData = [
            'name' => '6 Months Ramadan Easy Installment',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 12.50,
            'min_down_payment_pct' => 15.00,
            'installment_frequency' => 'monthly',
            'description' => 'Special promotion for Ramadan season',
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('plans.store'), $planData);

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseHas('installment_plans', [
            'company_id' => $this->company->id,
            'name' => '6 Months Ramadan Easy Installment',
            'tenure_months' => 6,
            'default_markup_rate_pct' => 12.50,
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_update_installment_plan(): void
    {
        $plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => 'Old Plan Name',
            'slug' => 'old-plan-name',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->put(route('plans.update', $plan->id), [
                'name' => 'Updated 12M Premium Plan',
                'tenure_months' => 12,
                'markup_calculation_model' => 'flat_percentage',
                'default_markup_rate_pct' => 24.00,
                'min_down_payment_pct' => 25.00,
                'installment_frequency' => 'monthly',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('plans.index'));
        $this->assertDatabaseHas('installment_plans', [
            'id' => $plan->id,
            'name' => 'Updated 12M Premium Plan',
            'default_markup_rate_pct' => 24.00,
        ]);
    }

    public function test_admin_can_toggle_plan_status(): void
    {
        $plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => 'Toggle Test Plan',
            'slug' => 'toggle-test-plan',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 15.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->post(route('plans.toggle', $plan->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('installment_plans', [
            'id' => $plan->id,
            'is_active' => 0,
        ]);
    }

    public function test_tenant_isolation_prevents_cross_tenant_plan_access(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor Electronics',
            'slug' => 'competitor-elec',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $otherPlan = InstallmentPlan::create([
            'company_id' => $otherCompany->id,
            'name' => 'Competitor Secret Plan',
            'slug' => 'competitor-secret-plan',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 10.00,
            'min_down_payment_pct' => 10.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // When requesting plans index, other company's plan should not appear
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('plans.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Competitor Secret Plan');

        // Cannot edit other company's plan (CompanyScope restricts query)
        $editResponse = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('plans.edit', $otherPlan->id));

        $editResponse->assertStatus(404);
    }
}
