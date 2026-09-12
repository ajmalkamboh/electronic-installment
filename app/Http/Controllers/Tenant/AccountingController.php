<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Services\Accounting\FinancialReportService;
use App\Services\Accounting\LedgerService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected FinancialReportService $reportService
    ) {}

    /**
     * 1. Chart of Accounts (COA) Directory.
     */
    public function chartOfAccounts(Request $request)
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $typeFilter = $request->get('type');
        $search = $request->get('search');

        $query = Account::where('company_id', $company->id)->orderBy('code');

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $accounts = $query->get();
        $branches = Branch::where('company_id', $company->id)->get();

        $grouped = $accounts->groupBy('type');

        return view('tenant.accounting.chart_of_accounts', compact('accounts', 'grouped', 'branches', 'typeFilter', 'search'));
    }

    /**
     * Store new custom account.
     */
    public function storeAccount(Request $request)
    {
        $company = Auth::user()->company;

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code,NULL,id,company_id,' . $company->id,
            'name' => 'required|string|max:191',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'required|string|max:60',
            'normal_balance' => 'required|in:debit,credit',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['company_id'] = $company->id;
        $validated['is_system'] = false;
        $validated['is_active'] = true;
        $validated['current_balance'] = 0.00;

        Account::create($validated);

        return redirect()->route('accounting.coa')->with('success', "Account [{$validated['code']}] {$validated['name']} created successfully.");
    }

    /**
     * 2. General Journal Entries Registry.
     */
    public function journal(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $search = $request->get('search');
        $refType = $request->get('reference_type');
        $date = $request->get('date');

        $query = JournalEntry::where('company_id', $companyId)
            ->with(['branch', 'postedBy', 'items.account'])
            ->latest('entry_date')
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($refType) {
            $query->where('reference_type', $refType);
        }

        if ($date) {
            $query->whereDate('entry_date', $date);
        }

        $entries = $query->paginate(15)->withQueryString();

        return view('tenant.accounting.journal', compact('entries', 'search', 'refType', 'date'));
    }

    /**
     * Manual Journal Entry Wizard form.
     */
    public function createJournal()
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $accounts = Account::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $branches = Branch::where('company_id', $company->id)->get();

        return view('tenant.accounting.create_journal', compact('accounts', 'branches'));
    }

    /**
     * Post manual journal entry.
     */
    public function storeJournal(Request $request)
    {
        $company = Auth::user()->company;

        $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
            'items.*.memo' => 'nullable|string|max:255',
        ]);

        $branch = $request->branch_id ? Branch::find($request->branch_id) : Auth::user()->branch;

        try {
            $entry = $this->ledgerService->postEntry(
                $company,
                $branch,
                $request->entry_date,
                'manual_journal',
                null,
                $request->description,
                $request->items,
                Auth::user()
            );

            return redirect()->route('accounting.journal')->with('success', "Journal Entry #{$entry->entry_number} posted successfully.");
        } catch (DomainException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 3. Trial Balance Report.
     */
    public function trialBalance(Request $request)
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $branchId = $request->get('branch_id');
        $asOfDate = $request->get('as_of_date', Carbon::today()->toDateString());

        $report = $this->reportService->getTrialBalance($company->id, $branchId ? (int) $branchId : null, $asOfDate);
        $branches = Branch::where('company_id', $company->id)->get();

        return view('tenant.accounting.trial_balance', compact('report', 'branches', 'branchId', 'asOfDate'));
    }

    /**
     * 4. Profit & Loss Statement (Income Statement).
     */
    public function profitLoss(Request $request)
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $branchId = $request->get('branch_id');
        $startDate = $request->get('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());

        $report = $this->reportService->getProfitAndLoss($company->id, $branchId ? (int) $branchId : null, $startDate, $endDate);
        $branches = Branch::where('company_id', $company->id)->get();

        return view('tenant.accounting.profit_loss', compact('report', 'branches', 'branchId', 'startDate', 'endDate'));
    }

    /**
     * 5. Balance Sheet Report.
     */
    public function balanceSheet(Request $request)
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $branchId = $request->get('branch_id');
        $asOfDate = $request->get('as_of_date', Carbon::today()->toDateString());

        $report = $this->reportService->getBalanceSheet($company->id, $branchId ? (int) $branchId : null, $asOfDate);
        $branches = Branch::where('company_id', $company->id)->get();

        return view('tenant.accounting.balance_sheet', compact('report', 'branches', 'branchId', 'asOfDate'));
    }

    /**
     * 6. Showroom Daily Cash Book & Drawer Reconciliation.
     */
    public function cashBook(Request $request)
    {
        $company = Auth::user()->company;
        $this->ledgerService->provisionDefaultChartOfAccounts($company);

        $branchId = $request->get('branch_id');
        $date = $request->get('date', Carbon::today()->toDateString());

        $report = $this->reportService->getCashBook($company->id, $branchId ? (int) $branchId : null, $date);
        $branches = Branch::where('company_id', $company->id)->get();

        return view('tenant.accounting.cash_book', compact('report', 'branches', 'branchId', 'date'));
    }

    /**
     * 7. Account Running Ledger Statement.
     */
    public function accountLedger(Account $account, Request $request)
    {
        if ($account->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }

        $startDate = $request->get('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());

        $ledger = $this->reportService->getAccountLedger($account, $startDate, $endDate);

        return view('tenant.accounting.account_ledger', compact('account', 'ledger', 'startDate', 'endDate'));
    }
}
