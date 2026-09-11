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
use App\Services\Payment\PaymentEngine;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $cashier;
    protected Customer $customer;
    protected Product $product;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected PaymentEngine $paymentEngine;

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
        $this->cashier = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Cashier Ajmal',
            'email' => 'cashier@kamboh.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35202-9999999-1',
            'full_name' => 'Amjad Ali',
            'father_or_husband_name' => 'Ali',
            'gender' => 'male',
            'mobile_primary' => '03009999999',
            'present_address' => 'Lahore',
            'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'LEDs',
            'slug' => 'leds',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'TCL Direct',
            'phone' => '042999999',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'TCL',
            'model_name' => '43 Inch Smart LED',
            'base_cash_price' => 60000,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => false,
            'is_active' => true,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '6 Months Plan',
            'slug' => '6-months-plan',
            'tenure_months' => 6,
            'markup_calculation_model' => 'flat_percentage',
            'default_markup_rate_pct' => 20.00,
            'min_down_payment_pct' => 20.00,
            'installment_frequency' => 'monthly',
            'is_active' => true,
        ]);

        // 60k cash - 12k down (20%) = 48k principal. 20% annual for 6m = 48k * 0.20 * (6/12) = 4,800 markup.
        // Total financed = 52,800. Monthly installment = 52,800 / 6 = 8,800.
        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->cashier->id,
            'account_number' => 'AGR-MAIN-202609-0099',
            'status' => 'active',
            'cash_price' => 60000,
            'down_payment_amount' => 12000,
            'down_payment_paid' => 12000,
            'financed_principal' => 48000,
            'markup_rate_pct' => 20.00,
            'markup_amount' => 4800,
            'total_financed' => 52800,
            'total_payable' => 64800,
            'installment_amount' => 8800,
            'tenure_months' => 6,
            'installment_frequency' => 'monthly',
            'total_installments' => 6,
            'paid_installments' => 0,
            'remaining_balance' => 52800,
            'start_date' => '2026-09-12',
            'first_due_date' => '2026-10-12',
            'maturity_date' => '2027-03-12',
        ]);

        $generator = new ScheduleGenerator();
        $generator->generateSchedule($this->agreement);

        $this->paymentEngine = new PaymentEngine($generator);
    }

    public function test_exact_installment_payment_marks_schedule_paid_and_updates_agreement(): void
    {
        $payment = $this->paymentEngine->recordPayment(
            $this->agreement,
            8800,
            'cash',
            'RCPT-001',
            $this->cashier,
            'First installment paid in full'
        );

        $this->assertEquals(8800, (float) $payment->amount);
        $this->assertStringStartsWith('PAY-MAIN01-', $payment->payment_number);

        // First schedule must be paid
        $firstSchedule = $this->agreement->schedules()->where('installment_number', 1)->first();
        $this->assertEquals('paid', $firstSchedule->status);
        $this->assertEquals(8800, (float) $firstSchedule->paid_amount);
        $this->assertEquals(0.00, (float) $firstSchedule->remaining_balance);

        // Agreement remaining balance must decrease by 8,800: 52,800 - 8,800 = 44,000
        $this->agreement->refresh();
        $this->assertEquals(44000, (float) $this->agreement->remaining_balance);
        $this->assertEquals(1, $this->agreement->paid_installments);
        $this->assertEquals('active', $this->agreement->status);
    }

    public function test_partial_payment_marks_schedule_partially_paid_with_remaining_balance(): void
    {
        $payment = $this->paymentEngine->recordPayment(
            $this->agreement,
            5000,
            'cash',
            'RCPT-002',
            $this->cashier,
            'Partial payment of 5000 against 8800 installment'
        );

        $firstSchedule = $this->agreement->schedules()->where('installment_number', 1)->first();
        $this->assertEquals('partially_paid', $firstSchedule->status);
        $this->assertEquals(5000, (float) $firstSchedule->paid_amount);
        $this->assertEquals(3800, (float) $firstSchedule->remaining_balance);

        $this->agreement->refresh();
        $this->assertEquals(47800, (float) $this->agreement->remaining_balance);
        $this->assertEquals(0, $this->agreement->paid_installments);
    }

    public function test_surplus_payment_cascades_in_waterfall_sequence(): void
    {
        // Customer pays 20,000 (enough for 2 installments of 8,800 = 17,600, plus 2,400 advance toward installment 3)
        $payment = $this->paymentEngine->recordPayment(
            $this->agreement,
            20000,
            'bank_transfer',
            'TXN-998811',
            $this->cashier,
            'Double payment plus advance'
        );

        $schedules = $this->agreement->schedules()->orderBy('installment_number')->get();

        // Installment 1: paid in full
        $this->assertEquals('paid', $schedules[0]->status);
        $this->assertEquals(8800, (float) $schedules[0]->paid_amount);

        // Installment 2: paid in full
        $this->assertEquals('paid', $schedules[1]->status);
        $this->assertEquals(8800, (float) $schedules[1]->paid_amount);

        // Installment 3: partially paid (2,400 paid, 6,400 remaining)
        $this->assertEquals('partially_paid', $schedules[2]->status);
        $this->assertEquals(2400, (float) $schedules[2]->paid_amount);
        $this->assertEquals(6400, (float) $schedules[2]->remaining_balance);

        $this->agreement->refresh();
        $this->assertEquals(32800, (float) $this->agreement->remaining_balance);
        $this->assertEquals(2, $this->agreement->paid_installments);
    }

    public function test_full_settlement_transitions_agreement_to_completed(): void
    {
        // Pay the entire remaining balance: 52,800
        $payment = $this->paymentEngine->recordPayment(
            $this->agreement,
            52800,
            'cash',
            'RCPT-SETTLE',
            $this->cashier,
            'Complete buyout & early settlement'
        );

        $this->agreement->refresh();
        $this->assertEquals(0.00, (float) $this->agreement->remaining_balance);
        $this->assertEquals(6, $this->agreement->paid_installments);
        $this->assertEquals('completed', $this->agreement->status);
        $this->assertNotNull($this->agreement->completed_at);

        // All schedules must be paid
        $this->assertEquals(0, $this->agreement->schedules()->where('status', '!=', 'paid')->count());
    }
}
