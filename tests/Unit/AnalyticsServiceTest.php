<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Report\AnalyticsService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;
    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $user;
    protected ProductCategory $category;
    protected Product $product;
    protected Customer $customer;
    protected \App\Models\InstallmentPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->service = app(AnalyticsService::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Showroom',
            'slug' => 'kamboh-electronics-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->plan = \App\Models\InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Standard Plan',
            'slug' => '12-months-standard',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20,
            'min_down_payment_pct' => 20,
            'is_active' => true,
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Lahore',
            'city' => 'Lahore',
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Gulberg Showroom',
            'code' => 'GLB-02',
            'is_main' => false,
            'status' => 'active',
            'address' => 'Gulberg, Lahore',
            'city' => 'Lahore',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Air Conditioners',
            'code' => 'AC-01',
            'slug' => 'air-conditioners',
            'status' => 'active',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Haier Pakistan',
            'phone' => '0423111222',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Haier',
            'model_name' => 'Haier 1.5 Ton Inverter AC',
            'sku' => 'HAC-15T-INV',
            'base_cash_price' => 120000,
            'min_down_payment_pct' => 20,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'full_name' => 'Muhammad Usman',
            'cnic' => '35202-1234567-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'Lahore, Pakistan',
            'status' => 'active',
        ]);
    }

    protected function createAgreement(
        Branch $branch,
        float $remainingBalance = 100000,
        string $status = 'active',
        ?int $overdueDays = null
    ): InstallmentAgreement {
        $agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'account_number' => 'AGR-' . strtoupper(Str::random(6)),
            'cash_price' => 120000,
            'down_payment_amount' => 30000,
            'down_payment_paid' => 30000,
            'financed_principal' => 90000,
            'markup_rate_pct' => 20,
            'markup_amount' => 18000,
            'total_financed' => 108000,
            'total_payable' => 138000,
            'installment_amount' => 9000,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 2,
            'remaining_balance' => $remainingBalance,
            'total_paid' => 30000 + (108000 - $remainingBalance),
            'status' => $status,
            'start_date' => Carbon::today()->subMonths(6),
            'first_due_date' => Carbon::today()->subMonths(5),
            'maturity_date' => Carbon::today()->addMonths(6),
            'creator_id' => $this->user->id,
        ]);

        // Create overdue schedule installment if specified
        if ($overdueDays !== null && $overdueDays > 0) {
            InstallmentSchedule::create([
                'company_id' => $this->company->id,
                'branch_id' => $branch->id,
                'installment_agreement_id' => $agreement->id,
                'installment_number' => 3,
                'due_date' => Carbon::today()->subDays($overdueDays),
                'principal_amount' => 7500,
                'markup_amount' => 1500,
                'total_amount' => 9000,
                'paid_amount' => 0,
                'remaining_balance' => 9000,
                'status' => 'overdue',
            ]);
        } else {
            InstallmentSchedule::create([
                'company_id' => $this->company->id,
                'branch_id' => $branch->id,
                'installment_agreement_id' => $agreement->id,
                'installment_number' => 3,
                'due_date' => Carbon::today()->addDays(15),
                'principal_amount' => 7500,
                'markup_amount' => 1500,
                'total_amount' => 9000,
                'paid_amount' => 0,
                'remaining_balance' => 9000,
                'status' => 'pending',
            ]);
        }

        return $agreement;
    }

    public function test_get_executive_kpis_calculates_portfolio_and_par_correctly(): void
    {
        // 1 healthy contract (balance 50,000)
        $this->createAgreement($this->branch1, 50000, 'active', null);

        // 1 PAR 30 contract (45 days overdue, balance 40,000)
        $this->createAgreement($this->branch1, 40000, 'active', 45);

        // 1 PAR 90 contract (100 days overdue, balance 60,000)
        $this->createAgreement($this->branch2, 60000, 'active', 100);

        $kpis = $this->service->getExecutiveKpis($this->company->id);

        $this->assertEquals(150000, $kpis['total_portfolio']);
        $this->assertEquals(3, $kpis['active_accounts']);
        
        // PAR 30 includes contracts overdue > 30d (both 45d and 100d => 40,000 + 60,000 = 100,000)
        $this->assertEquals(100000, $kpis['par_30_amount']);
        $this->assertEquals(66.67, $kpis['par_30_pct']);

        // PAR 90 includes contracts overdue > 90d (100d => 60,000)
        $this->assertEquals(60000, $kpis['par_90_amount']);
        $this->assertEquals(40.0, $kpis['par_90_pct']);
    }

    public function test_get_aging_report_groups_contracts_into_5_buckets(): void
    {
        // Current: 0-30 days
        $this->createAgreement($this->branch1, 50000, 'active', 10);
        // PAR 30: 31-60 days
        $this->createAgreement($this->branch1, 30000, 'active', 45);
        // PAR 60: 61-90 days
        $this->createAgreement($this->branch1, 20000, 'active', 75);
        // PAR 90: 91-180 days
        $this->createAgreement($this->branch1, 15000, 'active', 120);
        // Loss: 180+ days
        $this->createAgreement($this->branch1, 10000, 'active', 200);

        $report = $this->service->getAgingReport($this->company->id);

        $this->assertEquals(125000, $report['total_portfolio']);
        $this->assertEquals(1, $report['summary']['current']['count']);
        $this->assertEquals(50000, $report['summary']['current']['amount']);

        $this->assertEquals(1, $report['summary']['par_30']['count']);
        $this->assertEquals(30000, $report['summary']['par_30']['amount']);

        $this->assertEquals(1, $report['summary']['par_60']['count']);
        $this->assertEquals(20000, $report['summary']['par_60']['amount']);

        $this->assertEquals(1, $report['summary']['par_90']['count']);
        $this->assertEquals(15000, $report['summary']['par_90']['amount']);

        $this->assertEquals(1, $report['summary']['loss']['count']);
        $this->assertEquals(10000, $report['summary']['loss']['amount']);

        $this->assertCount(5, $report['records']);
    }

    public function test_get_collection_efficiency_calculates_billed_vs_collected(): void
    {
        $agreement = $this->createAgreement($this->branch1, 50000);

        // Due installment in current month
        InstallmentSchedule::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'installment_agreement_id' => $agreement->id,
            'installment_number' => 4,
            'due_date' => Carbon::today()->startOfMonth()->addDays(5),
            'principal_amount' => 8000,
            'markup_amount' => 2000,
            'total_amount' => 10000,
            'paid_amount' => 8000,
            'remaining_balance' => 2000,
            'status' => 'partially_paid',
        ]);

        // Cash collection in current month
        Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'installment_agreement_id' => $agreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'payment_number' => 'PAY-TEST-001',
            'amount' => 8000,
            'principal_paid' => 6400,
            'markup_paid' => 1600,
            'late_fee_paid' => 0,
            'payment_date' => Carbon::today(),
            'payment_method' => 'cash',
            'status' => 'posted',
        ]);

        $report = $this->service->getCollectionEfficiency($this->company->id, null, 3);

        $this->assertGreaterThan(0, $report['period_target']);
        $this->assertEquals(8000, $report['period_collected']);
        $this->assertNotEmpty($report['payment_methods']);
        $this->assertNotEmpty($report['cashier_performance']);
        $this->assertEquals('Ajmal Kamboh', $report['cashier_performance'][0]['name']);
        $this->assertEquals(8000, $report['cashier_performance'][0]['total_collected']);
    }

    public function test_get_branch_performance_ranks_showrooms(): void
    {
        $this->createAgreement($this->branch1, 80000, 'active', 10);
        $this->createAgreement($this->branch2, 40000, 'active', 50);

        $performance = $this->service->getBranchPerformance($this->company->id);

        $this->assertCount(2, $performance);
        $this->assertEquals('Ferozepur Road Showroom', $performance[0]['branch_name']);
        $this->assertEquals(80000, $performance[0]['total_portfolio']);
        $this->assertEquals(0, $performance[0]['par_30_pct']);

        $this->assertEquals('Gulberg Showroom', $performance[1]['branch_name']);
        $this->assertEquals(40000, $performance[1]['total_portfolio']);
        $this->assertEquals(100.0, $performance[1]['par_30_pct']);
    }

    public function test_get_product_category_analytics_summarizes_appliance_stats(): void
    {
        $this->createAgreement($this->branch1, 70000, 'active');
        $this->createAgreement($this->branch1, 0, 'defaulted');

        $analytics = $this->service->getProductCategoryAnalytics($this->company->id);

        $this->assertCount(1, $analytics);
        $this->assertEquals('Air Conditioners', $analytics[0]['category_name']);
        $this->assertEquals(2, $analytics[0]['units_sold']);
        $this->assertEquals(180000, $analytics[0]['total_financed_volume']);
        $this->assertEquals(1, $analytics[0]['active_contracts']);
        $this->assertEquals(1, $analytics[0]['defaulted_contracts']);
        $this->assertEquals(50.0, $analytics[0]['default_rate_pct']);
    }

    public function test_export_csv_streams_proper_csv_with_utf8_bom(): void
    {
        $this->createAgreement($this->branch1, 50000, 'active', 40);

        $response = $this->service->exportCsv('aging', $this->company->id);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="aging-report-', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Check BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Agreement #', $content);
        $this->assertStringContainsString('Muhammad Usman', $content);
        $this->assertStringContainsString('35202-1234567-1', $content);
        $this->assertStringContainsString('PAR 30', $content);
    }
}
