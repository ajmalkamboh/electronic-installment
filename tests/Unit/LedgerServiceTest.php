<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\InstallmentSchedule;
use App\Models\JournalEntry;
use App\Models\LateFeeWaiver;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RecoveryCase;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\LedgerService;
use App\Services\Payment\ScheduleGenerator;
use Database\Seeders\RoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected SerializedItem $serializedItem;
    protected InstallmentPlan $plan;
    protected InstallmentAgreement $agreement;
    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Lahore',
            'slug' => 'kamboh-electronics',
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
            'address' => 'Shop 12-14, Commercial Center, Ferozepur Road',
            'city' => 'Lahore',
            'phone' => '042-35800000',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'ulid' => (string) Str::ulid(),
            'customer_number' => 'CUST-001',
            'full_name' => 'Tariq Mehmood',
            'cnic' => '35202-1234567-1',
            'mobile_primary' => '03001234567',
            'present_address' => 'House 45, Model Town, Lahore',
            'status' => 'verified',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'LED Televisions',
            'slug' => 'led-televisions',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Samsung Pakistan',
            'contact_person' => 'Distributor',
            'phone' => '042-111111111',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'name' => 'Samsung 55 Inch Crystal UHD TV',
            'slug' => 'samsung-55-crystal',
            'brand' => 'Samsung',
            'model_name' => 'CU7000',
            'sku' => 'SAM-55-CU7000',
            'base_cash_price' => 100000,
            'status' => 'active',
        ]);

        $this->serializedItem = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $supplier->id,
            'serial_number' => 'SAM-SN-55443322',
            'status' => 'disbursed',
            'purchase_price' => 80000,
        ]);

        $this->plan = InstallmentPlan::create([
            'company_id' => $this->company->id,
            'name' => '12 Months Plan',
            'tenure_months' => 12,
            'markup_rate_pct' => 20.0,
            'down_payment_pct' => 20.0,
            'advance_installments_count' => 0,
            'status' => 'active',
        ]);

        $this->agreement = InstallmentAgreement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'serialized_item_id' => $this->serializedItem->id,
            'installment_plan_id' => $this->plan->id,
            'creator_id' => $this->user->id,
            'account_number' => 'AGR-2026-0001',
            'status' => 'active',
            'cash_price' => 100000,
            'down_payment_amount' => 20000,
            'down_payment_paid' => 20000,
            'financed_principal' => 80000,
            'markup_rate_pct' => 20.0,
            'markup_amount' => 16000,
            'total_financed' => 96000,
            'total_payable' => 116000,
            'installment_amount' => 8000,
            'tenure_months' => 12,
            'installment_frequency' => 'monthly',
            'total_installments' => 12,
            'paid_installments' => 0,
            'remaining_balance' => 96000,
            'start_date' => Carbon::today(),
            'first_due_date' => Carbon::today()->addMonth(),
            'maturity_date' => Carbon::today()->addMonths(12),
        ]);

        $generator = new ScheduleGenerator();
        $generator->generateSchedule($this->agreement);

        $this->ledgerService = new LedgerService();
    }

    public function test_it_provisions_default_chart_of_accounts(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $count = Account::where('company_id', $this->company->id)->count();
        $this->assertEquals(count(LedgerService::$defaultAccounts), $count);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '1010',
            'name' => 'Cash in Hand (Cashier Drawer)',
            'type' => 'asset',
        ]);

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '4010',
            'name' => 'Installment Financing Markup Revenue',
            'type' => 'revenue',
        ]);
    }

    public function test_it_rejects_unbalanced_journal_entry(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unbalanced journal entry');

        $this->ledgerService->postEntry(
            $this->company,
            $this->branch,
            Carbon::today(),
            'manual_journal',
            null,
            'Unbalanced test',
            [
                ['account_code' => '1010', 'debit' => 5000, 'credit' => 0],
                ['account_code' => '4010', 'debit' => 0, 'credit' => 4500], // 500 discrepancy
            ]
        );
    }

    public function test_it_posts_balanced_journal_entry_and_updates_account_balances(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $entry = $this->ledgerService->postEntry(
            $this->company,
            $this->branch,
            Carbon::today(),
            'manual_journal',
            null,
            'Owner capital injection',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '3010', 'debit' => 0, 'credit' => 50000],
            ],
            $this->user
        );

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(50000.0, $entry->total_debit);
        $this->assertEquals(50000.0, $entry->total_credit);

        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->first();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->first();

        $this->assertEquals(50000.0, $cashAcc->current_balance);
        $this->assertEquals(50000.0, $capitalAcc->current_balance);
    }

    public function test_it_posts_down_payment(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'payment_number' => 'PAY-2026-DP01',
            'amount' => 20000,
            'payment_method' => 'cash',
            'payment_date' => Carbon::today(),
            'status' => 'posted',
        ]);

        $entry = $this->ledgerService->postDownPayment($this->agreement, $payment);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(20000.0, $entry->total_debit);
        $this->assertEquals('down_payment', $entry->reference_type);

        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->first();
        $this->assertEquals(20000.0, $cashAcc->current_balance);
    }

    public function test_it_posts_merchandise_disbursement(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $entry = $this->ledgerService->postMerchandiseDisbursement($this->agreement);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals('disbursement', $entry->reference_type);
        $this->assertEquals($this->agreement->id, $entry->reference_id);

        // Verify AR principal was debited
        $arPrincipal = Account::where('company_id', $this->company->id)->where('code', '1030')->first();
        $this->assertEquals(96000.0, $arPrincipal->current_balance);
    }

    public function test_it_posts_installment_collection(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $payment = Payment::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'cashier_id' => $this->user->id,
            'payment_number' => 'PAY-2026-INST01',
            'amount' => 8500,
            'principal_paid' => 6666.67,
            'markup_paid' => 1333.33,
            'late_fee_paid' => 500.0,
            'payment_method' => 'cash',
            'payment_date' => Carbon::today(),
            'status' => 'posted',
        ]);

        $entry = $this->ledgerService->postInstallmentPayment($payment);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals('installment_payment', $entry->reference_type);

        $revMarkup = Account::where('company_id', $this->company->id)->where('code', '4010')->first();
        $revLate = Account::where('company_id', $this->company->id)->where('code', '4030')->first();

        $this->assertEquals(1333.33, $revMarkup->current_balance);
        $this->assertEquals(500.0, $revLate->current_balance);
    }

    public function test_it_posts_late_fee_accrual_and_waiver(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $schedule = $this->agreement->schedules()->first();

        // 1. Accrue late fee
        $accrualEntry = $this->ledgerService->postLateFeeAccrual($schedule, 500.0);
        $this->assertTrue($accrualEntry->isBalanced());

        $arLateFee = Account::where('company_id', $this->company->id)->where('code', '1038')->first();
        $this->assertEquals(500.0, $arLateFee->current_balance);

        // 2. Waive fee
        $waiver = LateFeeWaiver::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'installment_schedule_id' => $schedule->id,
            'waived_by_id' => $this->user->id,
            'original_late_fee' => 500.0,
            'waived_amount' => 500.0,
            'remaining_late_fee' => 0.0,
            'reason' => 'Customer bereavement waiver',
        ]);

        $waiverEntry = $this->ledgerService->postLateFeeWaiver($waiver);
        $this->assertTrue($waiverEntry->isBalanced());

        $arLateFee->refresh();
        $this->assertEquals(0.0, $arLateFee->current_balance);
    }

    public function test_it_posts_bad_debt_write_off(): void
    {
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);

        $case = RecoveryCase::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'installment_agreement_id' => $this->agreement->id,
            'customer_id' => $this->customer->id,
            'case_number' => 'RC-FZR-202609-0001',
            'stage' => 'written_off',
            'days_overdue' => 120,
            'overdue_amount' => 45000,
            'total_late_fees' => 2500,
            'remaining_balance' => 45000,
        ]);

        $entry = $this->ledgerService->postWriteOff($case, 45000.0, $this->user);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(45000.0, $entry->total_debit);

        $badDebtExpense = Account::where('company_id', $this->company->id)->where('code', '5020')->first();
        $this->assertEquals(45000.0, $badDebtExpense->current_balance);
    }
}
