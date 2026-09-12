<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\FinancialReportService;
use App\Services\Accounting\LedgerService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $user;
    protected LedgerService $ledgerService;
    protected FinancialReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Showroom',
            'slug' => 'kamboh-electronics-showroom',
            'status' => 'active',
            'currency' => 'PKR',
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

        $this->ledgerService = app(LedgerService::class);
        $this->reportService = app(FinancialReportService::class);

        // Provision COA
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);
    }

    public function test_trial_balance_computes_equal_debits_and_credits(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();
        $inventoryAcc = Account::where('company_id', $this->company->id)->where('code', '1040')->firstOrFail();

        // Entry 1: Capital injection 500,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            Carbon::today()->subDays(5)->toDateString(),
            'manual',
            null,
            'Owner Capital Injection',
            [
                ['account_id' => $cashAcc->id, 'debit' => 500000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 500000.00],
            ],
            $this->user
        );

        // Entry 2: Inventory purchase with cash 150,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            Carbon::today()->subDays(3)->toDateString(),
            'inventory',
            null,
            'Cash Inventory Purchase',
            [
                ['account_id' => $inventoryAcc->id, 'debit' => 150000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 150000.00],
            ],
            $this->user
        );

        $tb = $this->reportService->getTrialBalance($this->company->id);

        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals(500000.00, $tb['total_debit']);
        $this->assertEquals(500000.00, $tb['total_credit']);
        $this->assertEquals(0.00, $tb['difference']);
        $this->assertCount(3, $tb['rows']);
    }

    public function test_profit_and_loss_calculates_revenue_cogs_and_net_profit(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $markupRevAcc = Account::where('company_id', $this->company->id)->where('code', '4010')->firstOrFail();
        $lateFeeRevAcc = Account::where('company_id', $this->company->id)->where('code', '4030')->firstOrFail();
        $cogsAcc = Account::where('company_id', $this->company->id)->where('code', '5010')->firstOrFail();
        $inventoryAcc = Account::where('company_id', $this->company->id)->where('code', '1040')->firstOrFail();
        $salaryExpAcc = Account::where('company_id', $this->company->id)->where('code', '5060')->firstOrFail();

        $today = Carbon::today()->toDateString();

        // 1. Revenue earned: Markup revenue 25,000 PKR + Late fee 2,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $today,
            'manual',
            null,
            'Revenues Received in Cash',
            [
                ['account_id' => $cashAcc->id, 'debit' => 27000.00, 'credit' => 0.00],
                ['account_id' => $markupRevAcc->id, 'debit' => 0.00, 'credit' => 25000.00],
                ['account_id' => $lateFeeRevAcc->id, 'debit' => 0.00, 'credit' => 2000.00],
            ],
            $this->user
        );

        // 2. COGS recognition: 10,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $today,
            'manual',
            null,
            'COGS Recognition',
            [
                ['account_id' => $cogsAcc->id, 'debit' => 10000.00, 'credit' => 0.00],
                ['account_id' => $inventoryAcc->id, 'debit' => 0.00, 'credit' => 10000.00],
            ],
            $this->user
        );

        // 3. Operating expense: Salaries 5,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $today,
            'manual',
            null,
            'Staff Salaries Paid',
            [
                ['account_id' => $salaryExpAcc->id, 'debit' => 5000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 5000.00],
            ],
            $this->user
        );

        $pl = $this->reportService->getProfitAndLoss(
            $this->company->id,
            null,
            Carbon::today()->startOfMonth()->toDateString(),
            Carbon::today()->toDateString()
        );

        // Revenue: 25,000 + 2,000 = 27,000
        $this->assertEquals(27000.00, $pl['total_revenue']);
        // COGS: 10,000
        $this->assertEquals(10000.00, $pl['cogs']);
        // Gross Profit: 27,000 - 10,000 = 17,000
        $this->assertEquals(17000.00, $pl['gross_profit']);
        // Operating expenses: 5,000 (Total expenses 15,000 minus COGS 10,000)
        $this->assertEquals(5000.00, $pl['operating_expenses']);
        // Net Profit: 27,000 - 15,000 = 12,000
        $this->assertEquals(12000.00, $pl['net_profit']);
    }

    public function test_balance_sheet_satisfies_accounting_equation(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();
        $apAcc = Account::where('company_id', $this->company->id)->where('code', '2010')->firstOrFail();
        $inventoryAcc = Account::where('company_id', $this->company->id)->where('code', '1040')->firstOrFail();
        $markupRevAcc = Account::where('company_id', $this->company->id)->where('code', '4010')->firstOrFail();

        // 1. Initial Capital: 200,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            Carbon::today()->toDateString(),
            'manual',
            null,
            'Initial Capital',
            [
                ['account_id' => $cashAcc->id, 'debit' => 200000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 200000.00],
            ],
            $this->user
        );

        // 2. Inventory purchase on credit: 50,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            Carbon::today()->toDateString(),
            'manual',
            null,
            'Inventory Purchase on Credit',
            [
                ['account_id' => $inventoryAcc->id, 'debit' => 50000.00, 'credit' => 0.00],
                ['account_id' => $apAcc->id, 'debit' => 0.00, 'credit' => 50000.00],
            ],
            $this->user
        );

        // 3. Cash revenue earned: 15,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            Carbon::today()->toDateString(),
            'manual',
            null,
            'Installment Profit Received',
            [
                ['account_id' => $cashAcc->id, 'debit' => 15000.00, 'credit' => 0.00],
                ['account_id' => $markupRevAcc->id, 'debit' => 0.00, 'credit' => 15000.00],
            ],
            $this->user
        );

        // Assets = Cash (215,000) + Inventory (50,000) = 265,000
        // Liabilities = AP (50,000)
        // Base Equity = Capital (200,000)
        // Net Income to date = Revenue (15,000)
        // Total Liabilities & Equity = 50,000 + (200,000 + 15,000) = 265,000

        $bs = $this->reportService->getBalanceSheet($this->company->id);

        $this->assertTrue($bs['is_balanced']);
        $this->assertEquals(265000.00, $bs['total_assets']);
        $this->assertEquals(50000.00, $bs['total_liabilities']);
        $this->assertEquals(200000.00, $bs['equity_base_total']);
        $this->assertEquals(15000.00, $bs['net_income_to_date']);
        $this->assertEquals(215000.00, $bs['total_equity']);
        $this->assertEquals(265000.00, $bs['total_liabilities_and_equity']);
        $this->assertEquals(0.00, $bs['difference']);
    }

    public function test_cash_book_tracks_daily_inflows_and_outflows(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();
        $markupRevAcc = Account::where('company_id', $this->company->id)->where('code', '4010')->firstOrFail();
        $salaryExpAcc = Account::where('company_id', $this->company->id)->where('code', '5060')->firstOrFail();

        $yesterday = Carbon::yesterday()->toDateString();
        $today = Carbon::today()->toDateString();

        // 1. Prior Day: Capital Injection 100,000 PKR (Becomes Opening Balance for today)
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $yesterday,
            'manual',
            null,
            'Prior Day Cash Deposit',
            [
                ['account_id' => $cashAcc->id, 'debit' => 100000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 100000.00],
            ],
            $this->user
        );

        // 2. Today Inflow: Installment cash receipt 20,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $today,
            'installment_payment',
            null,
            'Customer Cash Installment',
            [
                ['account_id' => $cashAcc->id, 'debit' => 20000.00, 'credit' => 0.00, 'memo' => 'Receipt #8812'],
                ['account_id' => $markupRevAcc->id, 'debit' => 0.00, 'credit' => 20000.00],
            ],
            $this->user
        );

        // 3. Today Outflow: Tea / Office Petty Cash 3,000 PKR
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $today,
            'expense',
            null,
            'Office Refreshment Expenses',
            [
                ['account_id' => $salaryExpAcc->id, 'debit' => 3000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 3000.00, 'memo' => 'Voucher #012'],
            ],
            $this->user
        );

        $cashBook = $this->reportService->getCashBook($this->company->id, null, $today);

        $this->assertEquals(100000.00, $cashBook['opening_balance']);
        $this->assertEquals(20000.00, $cashBook['total_inflow']);
        $this->assertEquals(3000.00, $cashBook['total_outflow']);
        // Closing = 100,000 + 20,000 - 3,000 = 117,000
        $this->assertEquals(117000.00, $cashBook['closing_balance']);
        $this->assertCount(1, $cashBook['inflows']);
        $this->assertCount(1, $cashBook['outflows']);
    }

    public function test_account_ledger_generates_chronological_running_balance(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();
        $bankAcc = Account::where('company_id', $this->company->id)->where('code', '1020')->firstOrFail();

        $startDate = Carbon::today()->subDays(10)->toDateString();
        $midDate = Carbon::today()->subDays(5)->toDateString();
        $endDate = Carbon::today()->toDateString();

        // 1. Initial Deposit 50,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $startDate,
            'manual',
            null,
            'Deposit 1',
            [
                ['account_id' => $cashAcc->id, 'debit' => 50000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 50000.00],
            ],
            $this->user
        );

        // 2. Transfer from Cash to Bank 20,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $midDate,
            'manual',
            null,
            'Bank Deposit',
            [
                ['account_id' => $bankAcc->id, 'debit' => 20000.00, 'credit' => 0.00],
                ['account_id' => $cashAcc->id, 'debit' => 0.00, 'credit' => 20000.00],
            ],
            $this->user
        );

        // 3. Further cash inflow 10,000
        $this->ledgerService->postEntry(
            $this->company,
            $this->branch1,
            $endDate,
            'manual',
            null,
            'Cash Deposit 2',
            [
                ['account_id' => $cashAcc->id, 'debit' => 10000.00, 'credit' => 0.00],
                ['account_id' => $capitalAcc->id, 'debit' => 0.00, 'credit' => 10000.00],
            ],
            $this->user
        );

        $ledger = $this->reportService->getAccountLedger($cashAcc, $startDate, $endDate);

        $this->assertEquals(0.00, $ledger['opening_balance']);
        $this->assertEquals(60000.00, $ledger['period_debit']);
        $this->assertEquals(20000.00, $ledger['period_credit']);
        $this->assertEquals(40000.00, $ledger['closing_balance']);
        $this->assertCount(3, $ledger['rows']);

        // Check running balances sequentially
        $this->assertEquals(50000.00, $ledger['rows'][0]['balance']);
        $this->assertEquals(30000.00, $ledger['rows'][1]['balance']);
        $this->assertEquals(40000.00, $ledger['rows'][2]['balance']);
    }
}
