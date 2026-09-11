<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected InstallmentPlan $plan12M;
    protected InstallmentPlan $plan6M;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics',
            'slug' => 'kamboh-elec',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Bazaar Showroom',
            'code' => 'MBZ-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Ajmal Kamboh',
            'email' => 'ajmal@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->plan12M = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard',
            'slug' => '12-months-standard',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $this->plan6M = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '6 Months Quick Plan',
            'slug' => '6-months-quick-plan',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 15.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Inverter AC',
            'slug' => 'inverter-ac',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Dawlance Pakistan',
            'phone' => '03001234567',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Dawlance',
            'model_name' => 'Enercon 1.5 Ton AC',
            'base_cash_price' => 140000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => false,
            'is_active' => true,
        ]);
    }

    public function test_pricing_calculator_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->get(route('pricing.calculator'));

        $response->assertStatus(200);
        $response->assertSee('Showroom Installment Quotation Simulator');
        $response->assertSee('12 Months Standard');
        $response->assertSee('Dawlance Enercon 1.5 Ton AC');
    }

    public function test_pricing_calculation_endpoint_returns_json_quotation(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->postJson(route('pricing.calculate'), [
                'cash_price' => 100000,
                'plan_id' => $this->plan12M->id,
                'down_payment' => 20000,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'result' => [
                'cash_price' => 100000,
                'down_payment' => 20000,
                'financed_principal' => 80000,
                'markup_rate_pct' => 25.0,
                'markup_amount' => 20000,
                'total_payable' => 120000,
                'installment_amount' => 8333.33,
                'tenure_months' => 12,
            ],
        ]);
    }

    public function test_pricing_calculation_with_product_returns_comparisons(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->postJson(route('pricing.calculate'), [
                'product_id' => $this->product->id,
                'plan_id' => $this->plan12M->id,
                'down_payment' => 28000,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('comparisons', $data);
        $this->assertNotEmpty($data['comparisons']);
    }

    public function test_pricing_calculation_flags_low_down_payment_for_manager_approval(): void
    {
        // Minimum down payment for plan12M is 20% of 100,000 = 20,000
        $response = $this->actingAs($this->admin)
            ->withSession(['current_branch_id' => $this->branch->id])
            ->postJson(route('pricing.calculate'), [
                'cash_price' => 100000,
                'plan_id' => $this->plan12M->id,
                'down_payment' => 10000, // Only 10%, violates 20% threshold
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'result' => [
                'requires_manager_approval' => true,
            ],
        ]);
    }
}
