<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected ScheduleGenerator $generator;

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
            'name' => 'Main Branch',
            'code' => 'MAIN-01',
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

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35202-1234567-1',
            'full_name' => 'Rashid Ali',
            'father_or_husband_name' => 'Ali Ahmed',
            'gender' => 'male',
            'mobile_primary' => '03001234567',
            'present_address' => 'Lahore',
            'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Refrigerators',
            'slug' => 'refrigerators',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Dawlance Pakistan',
            'phone' => '0421234567',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Dawlance',
            'model_name' => '9170 WB Chrome',
            'base_cash_price' => 100000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => false,
            'is_active' => true,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Plan',
            'slug' => '12-months-plan',
            'tenure_months' => 12,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 25.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // Agreement: 100k cash - 20k down = 80k principal. 20k markup. Total financed = 100k.
        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->admin->id,
            'account_number' => 'AGR-MAIN-202609-0001',
            'status' => 'active',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 20000,
            'financed_principal' => 80000,
            'markup_rate_pct' => 25.00,
            'markup_amount' => 20000,
            'total_financed' => 100000,
            'total_payable' => 120000,
            'installment_amount' => 8333.33,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 100000,
            'start_date' => '2026-09-12',
            'first_due_date' => '2026-10-12',
            'maturity_date' => '2027-09-12',
        ]);

        $this->generator = new ScheduleGenerator();
    }

    public function test_generates_exact_number_of_schedules(): void
    {
        $schedules = $this->generator->generateSchedule($this->agreement);

        $this->assertCount(12, $schedules);
        $this->assertDatabaseCount('installment_schedules', 12);
    }

    public function test_sum_of_principal_and_markup_reconciles_exactly(): void
    {
        $schedules = $this->generator->generateSchedule($this->agreement);

        $totalPrincipal = $schedules->sum('principal_amount');
        $totalMarkup = $schedules->sum('markup_amount');
        $totalFinanced = $schedules->sum('total_amount');

        $this->assertEquals(80000.00, round($totalPrincipal, 2));
        $this->assertEquals(20000.00, round($totalMarkup, 2));
        $this->assertEquals(100000.00, round($totalFinanced, 2));
    }

    public function test_due_dates_are_chronologically_spaced(): void
    {
        $schedules = $this->generator->generateSchedule($this->agreement);

        $first = $schedules->first();
        $this->assertEquals('2026-10-12', $first->due_date->toDateString());
        $this->assertEquals(1, $first->installment_number);

        $last = $schedules->last();
        $this->assertEquals('2027-09-12', $last->due_date->toDateString());
        $this->assertEquals(12, $last->installment_number);
    }
}
