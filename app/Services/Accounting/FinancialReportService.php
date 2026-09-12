<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Generate Trial Balance.
     * Evaluates debit and credit balances across all active accounts ensuring complete ledger equality.
     */
    public function getTrialBalance(int $companyId, ?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate)->toDateString() : Carbon::today()->toDateString();

        $accounts = Account::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where(fn($bq) => $bq->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $itemsQuery = JournalEntryItem::where('account_id', $account->id)
                ->whereHas('entry', function ($q) use ($companyId, $branchId, $asOfDate) {
                    $q->where('company_id', $companyId)
                        ->where('status', 'posted')
                        ->whereDate('entry_date', '<=', $asOfDate)
                        ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
                });

            $sumDebit = (float) $itemsQuery->sum('debit');
            $sumCredit = (float) $itemsQuery->sum('credit');

            $netBalance = $sumDebit - $sumCredit;
            $rowDebit = 0.0;
            $rowCredit = 0.0;

            if ($account->normal_balance === 'debit') {
                if ($netBalance >= 0) {
                    $rowDebit = $netBalance;
                } else {
                    $rowCredit = abs($netBalance);
                }
            } else {
                $creditNet = $sumCredit - $sumDebit;
                if ($creditNet >= 0) {
                    $rowCredit = $creditNet;
                } else {
                    $rowDebit = abs($creditNet);
                }
            }

            if ($sumDebit > 0 || $sumCredit > 0 || $rowDebit > 0 || $rowCredit > 0) {
                $rows[] = [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'category' => $account->category,
                    'normal_balance' => $account->normal_balance,
                    'total_debit_movement' => $sumDebit,
                    'total_credit_movement' => $sumCredit,
                    'debit' => $rowDebit,
                    'credit' => $rowCredit,
                ];

                $totalDebit += $rowDebit;
                $totalCredit += $rowCredit;
            }
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);
        $isBalanced = abs($totalDebit - $totalCredit) < 0.01;

        return [
            'as_of_date' => $asOfDate,
            'branch_id' => $branchId,
            'rows' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => round(abs($totalDebit - $totalCredit), 2),
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Generate Profit & Loss Statement (Income Statement).
     */
    public function getProfitAndLoss(int $companyId, ?int $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::today()->startOfMonth()->toDateString();
        $endDate = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::today()->toDateString();

        $company = Company::findOrFail($companyId);

        $fetchBalances = function (string $type) use ($companyId, $branchId, $startDate, $endDate) {
            $accounts = Account::where('company_id', $companyId)
                ->where('type', $type)
                ->when($branchId, fn($q) => $q->where(fn($bq) => $bq->where('branch_id', $branchId)->orWhereNull('branch_id')))
                ->orderBy('code')
                ->get();

            $list = [];
            $total = 0.0;

            foreach ($accounts as $acc) {
                $items = JournalEntryItem::where('account_id', $acc->id)
                    ->whereHas('entry', function ($q) use ($companyId, $branchId, $startDate, $endDate) {
                        $q->where('company_id', $companyId)
                            ->where('status', 'posted')
                            ->whereDate('entry_date', '>=', $startDate)
                            ->whereDate('entry_date', '<=', $endDate)
                            ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
                    });

                $debits = (float) $items->sum('debit');
                $credits = (float) $items->sum('credit');

                // Revenue is credit-normal; Expense is debit-normal
                $amount = $type === 'revenue' ? ($credits - $debits) : ($debits - $credits);
                $amount = round($amount, 2);

                if (abs($amount) > 0.001 || $debits > 0 || $credits > 0) {
                    $list[] = [
                        'account_id' => $acc->id,
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'amount' => $amount,
                    ];
                    $total += $amount;
                }
            }

            return ['items' => $list, 'total' => round($total, 2)];
        };

        $revenue = $fetchBalances('revenue');
        $expense = $fetchBalances('expense');

        // Separate COGS if present
        $cogsAccount = Account::where('company_id', $companyId)->where('code', '5010')->first();
        $cogsAmount = 0.0;
        if ($cogsAccount) {
            foreach ($expense['items'] as $item) {
                if ($item['code'] === '5010') {
                    $cogsAmount = $item['amount'];
                    break;
                }
            }
        }

        $grossProfit = round($revenue['total'] - $cogsAmount, 2);
        $operatingExpenses = round($expense['total'] - $cogsAmount, 2);
        $netProfit = round($revenue['total'] - $expense['total'], 2);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $branchId,
            'revenue_items' => $revenue['items'],
            'total_revenue' => $revenue['total'],
            'cogs' => $cogsAmount,
            'gross_profit' => $grossProfit,
            'expense_items' => $expense['items'],
            'total_expenses' => $expense['total'],
            'operating_expenses' => $operatingExpenses,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate Balance Sheet.
     * Evaluates Assets, Liabilities, and Equity as of a given date.
     * Ensures: Assets == Liabilities + Equity.
     */
    public function getBalanceSheet(int $companyId, ?int $branchId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate)->toDateString() : Carbon::today()->toDateString();

        $fetchSection = function (string $type) use ($companyId, $branchId, $asOfDate) {
            $accounts = Account::where('company_id', $companyId)
                ->where('type', $type)
                ->when($branchId, fn($q) => $q->where(fn($bq) => $bq->where('branch_id', $branchId)->orWhereNull('branch_id')))
                ->orderBy('code')
                ->get();

            $list = [];
            $total = 0.0;

            foreach ($accounts as $acc) {
                $items = JournalEntryItem::where('account_id', $acc->id)
                    ->whereHas('entry', function ($q) use ($companyId, $branchId, $asOfDate) {
                        $q->where('company_id', $companyId)
                            ->where('status', 'posted')
                            ->whereDate('entry_date', '<=', $asOfDate)
                            ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
                    });

                $debits = (float) $items->sum('debit');
                $credits = (float) $items->sum('credit');

                $amount = $acc->normal_balance === 'debit' ? ($debits - $credits) : ($credits - $debits);
                $amount = round($amount, 2);

                if (abs($amount) > 0.001 || $debits > 0 || $credits > 0) {
                    $list[] = [
                        'account_id' => $acc->id,
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'category' => $acc->category,
                        'amount' => $amount,
                    ];
                    $total += $amount;
                }
            }

            return ['items' => $list, 'total' => round($total, 2)];
        };

        $assets = $fetchSection('asset');
        $liabilities = $fetchSection('liability');
        $equity = $fetchSection('equity');

        // Net income from inception up to as_of_date is transferred to Equity
        $allRevenue = (float) JournalEntryItem::whereHas('account', fn($q) => $q->where('company_id', $companyId)->where('type', 'revenue'))
            ->whereHas('entry', function ($q) use ($companyId, $branchId, $asOfDate) {
                $q->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $asOfDate)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })
            ->selectRaw('SUM(credit) - SUM(debit) as net_rev')
            ->value('net_rev') ?? 0.0;

        $allExpense = (float) JournalEntryItem::whereHas('account', fn($q) => $q->where('company_id', $companyId)->where('type', 'expense'))
            ->whereHas('entry', function ($q) use ($companyId, $branchId, $asOfDate) {
                $q->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('entry_date', '<=', $asOfDate)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })
            ->selectRaw('SUM(debit) - SUM(credit) as net_exp')
            ->value('net_exp') ?? 0.0;

        $netIncomeToDate = round($allRevenue - $allExpense, 2);

        $totalEquityWithIncome = round($equity['total'] + $netIncomeToDate, 2);
        $totalLiabilitiesAndEquity = round($liabilities['total'] + $totalEquityWithIncome, 2);

        $difference = round(abs($assets['total'] - $totalLiabilitiesAndEquity), 2);
        $isBalanced = $difference < 0.01;

        return [
            'as_of_date' => $asOfDate,
            'branch_id' => $branchId,
            'assets' => $assets['items'],
            'total_assets' => $assets['total'],
            'liabilities' => $liabilities['items'],
            'total_liabilities' => $liabilities['total'],
            'equity_items' => $equity['items'],
            'equity_base_total' => $equity['total'],
            'net_income_to_date' => $netIncomeToDate,
            'total_equity' => $totalEquityWithIncome,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'difference' => $difference,
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Showroom Daily Cash Book & Cashier Drawer Reconciliation.
     */
    public function getCashBook(int $companyId, ?int $branchId = null, ?string $date = null): array
    {
        $date = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $cashAccount = Account::where('company_id', $companyId)
            ->where('code', '1010')
            ->first();

        if (! $cashAccount) {
            return [
                'date' => $date,
                'opening_balance' => 0.00,
                'inflows' => [],
                'total_inflow' => 0.00,
                'outflows' => [],
                'total_outflow' => 0.00,
                'closing_balance' => 0.00,
            ];
        }

        // Opening cash: all posted debit - credit strictly prior to this date
        $openingDebit = (float) JournalEntryItem::where('account_id', $cashAccount->id)
            ->whereHas('entry', function ($q) use ($companyId, $branchId, $date) {
                $q->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('entry_date', '<', $date)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })->sum('debit');

        $openingCredit = (float) JournalEntryItem::where('account_id', $cashAccount->id)
            ->whereHas('entry', function ($q) use ($companyId, $branchId, $date) {
                $q->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('entry_date', '<', $date)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })->sum('credit');

        $openingBalance = round($openingDebit - $openingCredit, 2);

        // Daily transactions on date
        $dailyItems = JournalEntryItem::with(['entry.branch', 'entry.postedBy'])
            ->where('account_id', $cashAccount->id)
            ->whereHas('entry', function ($q) use ($companyId, $branchId, $date) {
                $q->where('company_id', $companyId)
                    ->where('status', 'posted')
                    ->whereDate('entry_date', $date)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })
            ->get();

        $inflows = [];
        $outflows = [];
        $totalInflow = 0.0;
        $totalOutflow = 0.0;

        foreach ($dailyItems as $item) {
            $entry = $item->entry;
            $row = [
                'entry_number' => $entry->entry_number,
                'time' => $entry->created_at->format('H:i'),
                'description' => $entry->description,
                'reference_type' => $entry->reference_type,
                'posted_by' => $entry->postedBy?->name ?? 'System',
                'memo' => $item->memo,
                'debit' => (float) $item->debit,
                'credit' => (float) $item->credit,
            ];

            if ($item->debit > 0) {
                $inflows[] = $row;
                $totalInflow += (float) $item->debit;
            }
            if ($item->credit > 0) {
                $outflows[] = $row;
                $totalOutflow += (float) $item->credit;
            }
        }

        $closingBalance = round($openingBalance + $totalInflow - $totalOutflow, 2);

        return [
            'date' => $date,
            'branch_id' => $branchId,
            'account' => $cashAccount,
            'opening_balance' => $openingBalance,
            'inflows' => $inflows,
            'total_inflow' => round($totalInflow, 2),
            'outflows' => $outflows,
            'total_outflow' => round($totalOutflow, 2),
            'closing_balance' => $closingBalance,
        ];
    }

    /**
     * Account Running Statement / General Ledger.
     */
    public function getAccountLedger(Account $account, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::today()->startOfMonth()->toDateString();
        $endDate = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::today()->toDateString();

        // Opening balance
        $openingDebits = (float) JournalEntryItem::where('account_id', $account->id)
            ->whereHas('entry', fn($q) => $q->where('status', 'posted')->whereDate('entry_date', '<', $startDate))
            ->sum('debit');

        $openingCredits = (float) JournalEntryItem::where('account_id', $account->id)
            ->whereHas('entry', fn($q) => $q->where('status', 'posted')->whereDate('entry_date', '<', $startDate))
            ->sum('credit');

        $openingBalance = $account->normal_balance === 'debit'
            ? round($openingDebits - $openingCredits, 2)
            : round($openingCredits - $openingDebits, 2);

        // Period transactions
        $items = JournalEntryItem::with(['entry.branch'])
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
            })
            ->join('journal_entries', 'journal_entry_items.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_items.*')
            ->get();

        $running = $openingBalance;
        $ledgerRows = [];
        $periodDebit = 0.0;
        $periodCredit = 0.0;

        foreach ($items as $item) {
            $debit = (float) $item->debit;
            $credit = (float) $item->credit;
            $periodDebit += $debit;
            $periodCredit += $credit;

            if ($account->normal_balance === 'debit') {
                $running += ($debit - $credit);
            } else {
                $running += ($credit - $debit);
            }

            $ledgerRows[] = [
                'date' => $item->entry->entry_date->format('d-M-Y'),
                'entry_number' => $item->entry->entry_number,
                'reference_type' => $item->entry->reference_type,
                'description' => $item->entry->description,
                'memo' => $item->memo,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($running, 2),
            ];
        }

        return [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'period_debit' => round($periodDebit, 2),
            'period_credit' => round($periodCredit, 2),
            'closing_balance' => round($running, 2),
            'rows' => $ledgerRows,
        ];
    }
}
