<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\LedgerService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected LedgerService $ledgerService;

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

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Mall Road Showroom',
            'code' => 'MLR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Mall Road, Lahore',
            'city' => 'Lahore',
            'phone' => '042-37350000',
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

        $this->ledgerService = app(LedgerService::class);
        $this->ledgerService->provisionDefaultChartOfAccounts($this->company);
    }

    public function test_guest_is_redirected_from_accounting_routes(): void
    {
        $response = $this->get(route('accounting.coa'));
        $response->assertRedirect(route('login'));

        $response2 = $this->get(route('accounting.journal'));
        $response2->assertRedirect(route('login'));
    }

    public function test_chart_of_accounts_index_loads_with_default_accounts(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.coa'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.chart_of_accounts');
        $response->assertSee('Chart of Accounts');
        $response->assertSee('Cash in Hand');
        $response->assertSee('1010');
        $response->assertSee('Accounts Receivable - Financed Principal');
        $response->assertSee('1030');
        $response->assertSee('Owner\'s Capital');
        $response->assertSee('3010');
        $response->assertSee('Installment Financing Markup Revenue');
        $response->assertSee('4010');
    }

    public function test_custom_account_can_be_created(): void
    {
        $payload = [
            'code' => '1025',
            'name' => 'Meezan Bank Islamic Current Account',
            'type' => 'asset',
            'category' => 'cash_and_bank',
            'normal_balance' => 'debit',
            'description' => 'Showroom dedicated Islamic business account',
        ];

        $response = $this->actingAs($this->user)->post(route('accounting.accounts.store'), $payload);

        $response->assertRedirect(route('accounting.coa'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '1025',
            'name' => 'Meezan Bank Islamic Current Account',
            'type' => 'asset',
            'is_system' => false,
        ]);
    }

    public function test_duplicate_account_code_within_same_company_is_rejected(): void
    {
        // 1010 already exists in default COA
        $payload = [
            'code' => '1010',
            'name' => 'Duplicate Cash Account',
            'type' => 'asset',
            'category' => 'cash_and_bank',
            'normal_balance' => 'debit',
        ];

        $response = $this->actingAs($this->user)->post(route('accounting.accounts.store'), $payload);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_journal_index_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.journal'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.journal');
        $response->assertSee('General Journal');
    }

    public function test_create_journal_form_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.journal.create'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.create_journal');
        $response->assertSee('New Manual Journal Entry');
        $response->assertSee('Debits &amp; Credits', false);
    }

    public function test_manual_journal_posting_rejects_unbalanced_entry(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $markupAcc = Account::where('company_id', $this->company->id)->where('code', '4010')->firstOrFail();

        $payload = [
            'entry_date' => Carbon::today()->toDateString(),
            'description' => 'Unbalanced test entry',
            'branch_id' => $this->branch->id,
            'items' => [
                ['account_id' => $cashAcc->id, 'debit' => 10000, 'credit' => 0, 'memo' => 'Debit Cash'],
                ['account_id' => $markupAcc->id, 'debit' => 0, 'credit' => 8000, 'memo' => 'Credit Markup'], // 2000 discrepancy
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('accounting.journal.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, JournalEntry::where('company_id', $this->company->id)->count());
    }

    public function test_manual_journal_posting_creates_balanced_entry(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();

        $payload = [
            'entry_date' => Carbon::today()->toDateString(),
            'description' => 'Owner Initial Capital Deposit',
            'branch_id' => $this->branch->id,
            'items' => [
                ['account_id' => $cashAcc->id, 'debit' => 300000.00, 'credit' => 0, 'memo' => 'Cash received'],
                ['account_id' => $capitalAcc->id, 'debit' => 0, 'credit' => 300000.00, 'memo' => 'Owner equity recognized'],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('accounting.journal.store'), $payload);

        $response->assertRedirect(route('accounting.journal'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'reference_type' => 'manual_journal',
            'total_debit' => 300000.00,
            'total_credit' => 300000.00,
            'status' => 'posted',
        ]);

        $cashAcc->refresh();
        $this->assertEquals(300000.00, (float) $cashAcc->current_balance);
    }

    public function test_trial_balance_page_loads_and_shows_balanced(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();
        $capitalAcc = Account::where('company_id', $this->company->id)->where('code', '3010')->firstOrFail();

        $this->ledgerService->postEntry(
            $this->company,
            $this->branch,
            Carbon::today(),
            'manual',
            null,
            'Capital Investment',
            [
                ['account_id' => $cashAcc->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $capitalAcc->id, 'debit' => 0, 'credit' => 100000],
            ],
            $this->user
        );

        $response = $this->actingAs($this->user)->get(route('accounting.trial-balance'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.trial_balance');
        $response->assertSee('Trial Balance');
        $response->assertSee('Balanced');
    }

    public function test_profit_and_loss_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.profit-loss'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.profit_loss');
        $response->assertSee('Profit &amp; Loss', false);
    }

    public function test_balance_sheet_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.balance-sheet'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.balance_sheet');
        $response->assertSee('Balance Sheet');
    }

    public function test_cash_book_page_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.cash-book'));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.cash_book');
        $response->assertSee('Showroom Daily Cash Book');
    }

    public function test_account_ledger_page_loads_for_tenant_account(): void
    {
        $cashAcc = Account::where('company_id', $this->company->id)->where('code', '1010')->firstOrFail();

        $response = $this->actingAs($this->user)->get(route('accounting.account-ledger', $cashAcc));

        $response->assertOk();
        $response->assertViewIs('tenant.accounting.account_ledger');
        $response->assertSee('Running Statement of Account');
        $response->assertSee('1010');
    }

    public function test_account_ledger_denies_cross_tenant_access(): void
    {
        // Company B
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Rival Electronics',
            'slug' => 'rival-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->ledgerService->provisionDefaultChartOfAccounts($otherCompany);
        $otherCashAcc = Account::where('company_id', $otherCompany->id)->where('code', '1010')->firstOrFail();

        // User from Company A attempts to view Company B's ledger
        $response = $this->actingAs($this->user)->get(route('accounting.account-ledger', $otherCashAcc));

        $response->assertForbidden();
    }
}
