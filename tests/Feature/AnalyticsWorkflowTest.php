<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected ProductCategory $categoryA;
    protected Product $productA;
    protected Customer $customerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Company A
        $this->companyA = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Main',
            'slug' => 'kamboh-main',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->companyA->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Branch',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Lahore',
            'city' => 'Lahore',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->userA = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // Company B
        $this->companyB = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Rival Electronics',
            'slug' => 'rival-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->companyB->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Mall Road Branch',
            'code' => 'MLL-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Mall Road, Lahore',
            'city' => 'Lahore',
        ]);

        $this->userB = User::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB->id,
            'name' => 'Competitor Admin',
            'email' => 'competitor@rival.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->categoryA = ProductCategory::create([
            'company_id' => $this->companyA->id,
            'name' => 'Refrigerators',
            'code' => 'REF-01',
            'slug' => 'refrigerators',
            'status' => 'active',
        ]);

        $supplierA = Supplier::create([
            'company_id' => $this->companyA->id,
            'name' => 'Dawlance Pakistan',
            'status' => 'active',
        ]);

        $this->productA = Product::create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
            'supplier_id' => $supplierA->id,
            'brand' => 'Dawlance',
            'model_name' => 'Dawlance 9199 Inverter Refrigerator',
            'sku' => 'DW-9199-INV',
            'base_cash_price' => 110000,
            'min_down_payment_pct' => 20,
            'is_active' => true,
        ]);

        $planA = \App\Models\InstallmentPlan::create([
            'company_id' => $this->companyA->id,
            'name' => '12 Months Plan',
            'slug' => '12-months-plan',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20,
            'min_down_payment_pct' => 20,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Tariq Mehmood',
            'cnic' => '35201-9988776-3',
            'mobile_primary' => '03217654321',
            'present_address' => 'Mall Road, Lahore',
            'status' => 'active',
        ]);

        // Create sample agreement in Company A
        InstallmentAgreement::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'customer_id' => $this->customerA->id,
            'product_id' => $this->productA->id,
            'installment_plan_id' => $planA->id,
            'account_number' => 'AGR-COMPA-001',
            'cash_price' => 110000,
            'down_payment_amount' => 25000,
            'down_payment_paid' => 25000,
            'financed_principal' => 85000,
            'markup_rate_pct' => 20,
            'markup_amount' => 17000,
            'total_financed' => 102000,
            'total_payable' => 127000,
            'installment_amount' => 8500,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 102000,
            'total_paid' => 25000,
            'status' => 'active',
            'start_date' => Carbon::today(),
            'first_due_date' => Carbon::today()->addMonth(),
            'maturity_date' => Carbon::today()->addMonths(12),
            'creator_id' => $this->userA->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_on_all_analytics_routes(): void
    {
        $this->get(route('analytics.dashboard'))->assertRedirect(route('login'));
        $this->get(route('analytics.aging'))->assertRedirect(route('login'));
        $this->get(route('analytics.collections'))->assertRedirect(route('login'));
        $this->get(route('analytics.branches'))->assertRedirect(route('login'));
        $this->get(route('analytics.products'))->assertRedirect(route('login'));
        $this->get(route('analytics.export', ['type' => 'aging']))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_executive_dashboard(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.dashboard'));

        $response->assertOk();
        $response->assertSee('Portfolio Analytics &amp; Risk Intelligence', false);
        $response->assertSee('PKR 102,000');
        $response->assertSee('Ferozepur Road Branch');
        $response->assertSee('Refrigerators');
    }

    public function test_authenticated_user_can_view_aging_report_with_filters(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.aging', [
            'branch_id' => $this->branchA->id,
            'category_id' => $this->categoryA->id,
            'as_of_date' => Carbon::today()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Portfolio Aging &amp; PAR Buckets', false);
        $response->assertSee('AGR-COMPA-001');
        $response->assertSee('Tariq Mehmood');
        $response->assertSee('PKR 102,000');
        $response->assertSee('Current (0-30d)');
    }

    public function test_authenticated_user_can_view_collections_efficiency_view(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.collections', [
            'months' => 6,
        ]));

        $response->assertOk();
        $response->assertSee('Collection Efficiency &amp; Cashier Recovery', false);
        $response->assertSee('Collections By Payment Channel');
        $response->assertSee('Monthly Billed vs. Collected Performance');
    }

    public function test_authenticated_user_can_view_branch_leaderboard(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.branches'));

        $response->assertOk();
        $response->assertSee('Multi-Branch Showroom Comparative Leaderboard');
        $response->assertSee('Ferozepur Road Branch');
        $response->assertSee('PKR 102,000');
    }

    public function test_authenticated_user_can_view_product_category_analytics(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.products'));

        $response->assertOk();
        $response->assertSee('Appliance Category &amp; Brand Analytics', false);
        $response->assertSee('Refrigerators');
        $response->assertSee('PKR 85,000');
    }

    public function test_csv_export_streams_valid_content(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.export', [
            'type' => 'aging',
        ]));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('aging-report-', $response->headers->get('Content-Disposition'));

        $responseBranches = $this->actingAs($this->userA)->get(route('analytics.export', [
            'type' => 'branches',
        ]));
        $responseBranches->assertOk();

        $responseCollections = $this->actingAs($this->userA)->get(route('analytics.export', [
            'type' => 'collections',
        ]));
        $responseCollections->assertOk();
    }

    public function test_unknown_export_type_returns_404(): void
    {
        $response = $this->actingAs($this->userA)->get(route('analytics.export', [
            'type' => 'invalid-report-type',
        ]));

        $response->assertNotFound();
    }

    public function test_tenant_isolation_prevents_viewing_other_company_records(): void
    {
        // User B from Company B views dashboard
        $response = $this->actingAs($this->userB)->get(route('analytics.dashboard'));

        $response->assertOk();
        // Should not see Company A's branch or agreements
        $response->assertDontSee('AGR-COMPA-001');
        $response->assertDontSee('Tariq Mehmood');
        $response->assertDontSee('Ferozepur Road Branch');
        $response->assertSee('Mall Road Branch');
    }
}
