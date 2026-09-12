<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\LateFeeWaiver;
use App\Models\Payment;
use App\Models\RecoveryCase;
use App\Models\SerializedItem;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Standard Pakistani Electronic Installment Retail Showroom Chart of Accounts.
     */
    public static array $defaultAccounts = [
        // 1000: ASSETS
        ['code' => '1010', 'name' => 'Cash in Hand (Cashier Drawer)', 'type' => 'asset', 'category' => 'cash_and_bank', 'normal_balance' => 'debit', 'description' => 'Daily physical showroom cash held in counter drawers'],
        ['code' => '1020', 'name' => 'Bank Accounts (Showroom Deposits)', 'type' => 'asset', 'category' => 'cash_and_bank', 'normal_balance' => 'debit', 'description' => 'Commercial bank balances (HBL, Meezan, Bank Alfalah, etc.)'],
        ['code' => '1030', 'name' => 'Accounts Receivable - Financed Principal', 'type' => 'asset', 'category' => 'accounts_receivable', 'normal_balance' => 'debit', 'description' => 'Hire-purchase principal receivable from installment customers'],
        ['code' => '1035', 'name' => 'Accounts Receivable - Unearned Markup', 'type' => 'asset', 'category' => 'accounts_receivable', 'normal_balance' => 'debit', 'description' => 'Future financing profit receivable over contract tenure'],
        ['code' => '1038', 'name' => 'Accounts Receivable - Late Fee Surcharges', 'type' => 'asset', 'category' => 'accounts_receivable', 'normal_balance' => 'debit', 'description' => 'Accrued overdue late fee penalties receivable'],
        ['code' => '1040', 'name' => 'Inventory Asset - Showroom Merchandise', 'type' => 'asset', 'category' => 'inventory', 'normal_balance' => 'debit', 'description' => 'Stock value of brand new electronics on display and in warehouse'],
        ['code' => '1050', 'name' => 'Repossessed Merchandise Inventory', 'type' => 'asset', 'category' => 'inventory', 'normal_balance' => 'debit', 'description' => 'Valuation of defaulted appliances repossessed by recovery squads'],

        // 2000: LIABILITIES
        ['code' => '2010', 'name' => 'Accounts Payable - Suppliers & Vendors', 'type' => 'liability', 'category' => 'accounts_payable', 'normal_balance' => 'credit', 'description' => 'Trade payables due to electronics distributors and manufacturers'],
        ['code' => '2020', 'name' => 'Customer Security Deposits & Advances', 'type' => 'liability', 'category' => 'current_liability', 'normal_balance' => 'credit', 'description' => 'Excess advance collections or security deposits held'],
        ['code' => '2030', 'name' => 'Unearned Financing Markup (Deferred Income)', 'type' => 'liability', 'category' => 'deferred_revenue', 'normal_balance' => 'credit', 'description' => 'Unearned installment profit deferred until monthly realization'],

        // 3000: EQUITY
        ['code' => '3010', 'name' => "Owner's Capital / Equity", 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'description' => 'Initial and ongoing invested capital by showroom owners'],
        ['code' => '3020', 'name' => 'Retained Earnings', 'type' => 'equity', 'category' => 'equity', 'normal_balance' => 'credit', 'description' => 'Accumulated historical profits and retained surplus'],

        // 4000: REVENUE
        ['code' => '4010', 'name' => 'Installment Financing Markup Revenue', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'description' => 'Realized financing profit recognized upon monthly installment collections'],
        ['code' => '4020', 'name' => 'Down Payment Sales Margin', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'description' => 'Initial gross profit margin recognized on contract execution'],
        ['code' => '4030', 'name' => 'Late Payment Surcharge Revenue', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'description' => 'Penalties levied on delinquent and overdue installments'],
        ['code' => '4040', 'name' => 'Documentation & File Processing Fees', 'type' => 'revenue', 'category' => 'operating_revenue', 'normal_balance' => 'credit', 'description' => 'Administrative file opening and legal agreement documentation charges'],
        ['code' => '4050', 'name' => 'Gain on Asset Repossession & Resale', 'type' => 'revenue', 'category' => 'other_revenue', 'normal_balance' => 'credit', 'description' => 'Surplus realized when repossessed merchandise is re-auctioned'],

        // 5000: EXPENSES & CONTRA-REVENUE
        ['code' => '5010', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'category' => 'direct_expense', 'normal_balance' => 'debit', 'description' => 'Wholesale purchase cost of electronic merchandise disbursed'],
        ['code' => '5020', 'name' => 'Bad Debt Expense (Default Write-offs)', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'description' => 'Unrecoverable contractual principal written off as loss'],
        ['code' => '5030', 'name' => 'Late Fee Waivers & Concessions', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'description' => 'Supervisory waivers granted reducing late penalty receivables'],
        ['code' => '5040', 'name' => 'Early Settlement Markup Rebate Concessions', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'description' => 'Unearned markup discount awarded to customer for early lump-sum payoff'],
        ['code' => '5050', 'name' => 'Loss on Asset Repossession', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'description' => 'Deficit between outstanding receivable and assessed value of repossessed item'],
        ['code' => '5060', 'name' => 'Showroom Operating & Administrative Expenses', 'type' => 'expense', 'category' => 'operating_expense', 'normal_balance' => 'debit', 'description' => 'Rent, utilities, staff salaries, and showroom upkeep'],
    ];

    /**
     * Provision the default Chart of Accounts for a company if not already existing.
     */
    public function provisionDefaultChartOfAccounts(Company $company): void
    {
        foreach (self::$defaultAccounts as $acc) {
            Account::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $acc['code'],
                ],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'category' => $acc['category'],
                    'normal_balance' => $acc['normal_balance'],
                    'is_system' => true,
                    'is_active' => true,
                    'description' => $acc['description'],
                    'current_balance' => 0.00,
                ]
            );
        }
    }

    /**
     * Find an account by its unique code for a company.
     */
    public function getAccount(Company|int $company, string $code): Account
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        $account = Account::where('company_id', $companyId)->where('code', $code)->first();

        if (! $account) {
            $companyModel = $company instanceof Company ? $company : Company::findOrFail($companyId);
            $this->provisionDefaultChartOfAccounts($companyModel);
            $account = Account::where('company_id', $companyId)->where('code', $code)->firstOrFail();
        }

        return $account;
    }

    /**
     * Post a balanced double-entry journal transaction.
     * Enforces: SUM(debits) === SUM(credits).
     */
    public function postEntry(
        Company $company,
        ?Branch $branch,
        string|Carbon $entryDate,
        string $referenceType,
        ?int $referenceId,
        string $description,
        array $items,
        ?User $postedBy = null,
        string $status = 'posted'
    ): JournalEntry {
        if (count($items) < 2) {
            throw new DomainException("A journal entry requires at least two line items (debit and credit).");
        }

        $entryDate = $entryDate instanceof Carbon ? $entryDate->toDateString() : $entryDate;

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $normalizedItems = [];

        foreach ($items as $item) {
            $accountId = $item['account_id'] ?? null;
            if (! $accountId && isset($item['account_code'])) {
                $acc = $this->getAccount($company, $item['account_code']);
                $accountId = $acc->id;
            }

            if (! $accountId) {
                throw new DomainException("Each journal entry item must have an account_id or valid account_code.");
            }

            $debit = round((float) ($item['debit'] ?? 0.0), 2);
            $credit = round((float) ($item['credit'] ?? 0.0), 2);

            if ($debit < 0 || $credit < 0) {
                throw new DomainException("Debit and Credit amounts must be non-negative numbers.");
            }

            if ($debit == 0 && $credit == 0) {
                continue; // Skip zero items
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $normalizedItems[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $item['memo'] ?? null,
            ];
        }

        $diff = abs($totalDebit - $totalCredit);
        if ($diff > 0.01) {
            throw new DomainException(sprintf(
                "Unbalanced journal entry: Total Debits (PKR %s) does not equal Total Credits (PKR %s). Discrepancy: PKR %s",
                number_format($totalDebit, 2),
                number_format($totalCredit, 2),
                number_format($diff, 2)
            ));
        }

        return DB::transaction(function () use ($company, $branch, $entryDate, $referenceType, $referenceId, $description, $normalizedItems, $postedBy, $status, $totalDebit, $totalCredit) {
            $entryNumber = $this->generateEntryNumber($branch);

            $entry = JournalEntry::create([
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'posted_by_id' => $postedBy?->id,
                'status' => $status,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            foreach ($normalizedItems as $itemData) {
                $entry->items()->create($itemData);

                // Update account current_balance
                $account = Account::lockForUpdate()->find($itemData['account_id']);
                if ($account) {
                    if ($account->normal_balance === 'debit') {
                        $account->current_balance += ($itemData['debit'] - $itemData['credit']);
                    } else {
                        $account->current_balance += ($itemData['credit'] - $itemData['debit']);
                    }
                    $account->save();
                }
            }

            return $entry;
        });
    }

    /**
     * Generate unique journal entry sequence number: JE-{branch_code}-{YYYYMM}-{seq}.
     */
    public function generateEntryNumber(?Branch $branch): string
    {
        $branchCode = strtoupper($branch?->code ?? 'HQ');
        $yearMonth = Carbon::now()->format('Ym');
        $prefix = "JE-{$branchCode}-{$yearMonth}-";

        $latest = JournalEntry::where('entry_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('entry_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // Automated Ledger Event Posting Triggers
    // ==========================================

    /**
     * 1. Post Down Payment receipt.
     */
    public function postDownPayment(InstallmentAgreement $agreement, Payment $payment): JournalEntry
    {
        $cashOrBankCode = in_array(strtolower($payment->payment_method), ['bank_transfer', 'cheque', 'online']) ? '1020' : '1010';

        $items = [
            [
                'account_code' => $cashOrBankCode,
                'debit' => $payment->amount,
                'credit' => 0,
                'memo' => "Down payment collected for Agreement #{$agreement->account_number}",
            ],
            [
                'account_code' => '1030', // Accounts Receivable Principal
                'debit' => 0,
                'credit' => $payment->amount,
                'memo' => "Down payment applied against Financed Principal #{$agreement->account_number}",
            ],
        ];

        return $this->postEntry(
            $agreement->company,
            $agreement->branch,
            $payment->payment_date ?? Carbon::today(),
            'down_payment',
            $payment->id,
            "Down payment received for #{$agreement->account_number} ({$agreement->customer?->full_name})",
            $items,
            $payment->cashier
        );
    }

    /**
     * 2. Post Merchandise Disbursement / Installment Contract Execution.
     */
    public function postMerchandiseDisbursement(InstallmentAgreement $agreement): JournalEntry
    {
        $purchaseCost = (float) ($agreement->serializedItem?->purchase_price
            ?? $agreement->serializedItem?->purchase_cost
            ?? ($agreement->cash_price * 0.85));

        $items = [
            // Debit Accounts Receivable Principal
            [
                'account_code' => '1030',
                'debit' => $agreement->total_financed,
                'credit' => 0,
                'memo' => "Principal receivable booked for Contract #{$agreement->account_number}",
            ],
            // Debit Accounts Receivable Unearned Markup
            [
                'account_code' => '1035',
                'debit' => $agreement->total_markup,
                'credit' => 0,
                'memo' => "Unearned financing markup booked for Contract #{$agreement->account_number}",
            ],
            // Debit Cost of Goods Sold (COGS)
            [
                'account_code' => '5010',
                'debit' => $purchaseCost,
                'credit' => 0,
                'memo' => "Cost of goods sold for S/N: " . ($agreement->serializedItem?->serial_number ?? 'Disbursed Item'),
            ],
            // Credit Unearned Financing Markup (Deferred Liability)
            [
                'account_code' => '2030',
                'debit' => 0,
                'credit' => $agreement->total_markup,
                'memo' => "Deferred markup recognized on execution #{$agreement->account_number}",
            ],
            // Credit Sales Margin
            [
                'account_code' => '4020',
                'debit' => 0,
                'credit' => $agreement->total_financed,
                'memo' => "Installment sale booking #{$agreement->account_number}",
            ],
            // Credit Inventory Asset
            [
                'account_code' => '1040',
                'debit' => 0,
                'credit' => $purchaseCost,
                'memo' => "Inventory derecognition for S/N: " . ($agreement->serializedItem?->serial_number ?? 'Disbursed Item'),
            ],
        ];

        return $this->postEntry(
            $agreement->company,
            $agreement->branch,
            $agreement->disbursed_at ?? Carbon::today(),
            'disbursement',
            $agreement->id,
            "Merchandise disbursement and contract execution for #{$agreement->account_number} ({$agreement->product?->name})",
            $items,
            $agreement->disbursedBy ?? $agreement->creator
        );
    }

    /**
     * 3. Post Monthly Installment Collection.
     */
    public function postInstallmentPayment(Payment $payment): JournalEntry
    {
        $agreement = $payment->agreement;
        $cashOrBankCode = in_array(strtolower($payment->payment_method), ['bank_transfer', 'cheque', 'online']) ? '1020' : '1010';

        $items = [];

        // 1. Debit Cash / Bank for total amount received
        $items[] = [
            'account_code' => $cashOrBankCode,
            'debit' => $payment->amount,
            'credit' => 0,
            'memo' => "Installment payment #{$payment->payment_number} received via {$payment->payment_method}",
        ];

        // 2. Credit AR Principal for principal component
        if ($payment->principal_paid > 0) {
            $items[] = [
                'account_code' => '1030',
                'debit' => 0,
                'credit' => $payment->principal_paid,
                'memo' => "Principal received against #{$agreement->account_number}",
            ];
        }

        // 3. Markup recognition:
        // Cr AR Unearned Markup & Dr Unearned Financing Markup (2030) & Cr Markup Revenue (4010)
        if ($payment->markup_paid > 0) {
            $items[] = [
                'account_code' => '1035', // AR Markup
                'debit' => 0,
                'credit' => $payment->markup_paid,
                'memo' => "Markup collected on #{$agreement->account_number}",
            ];
            $items[] = [
                'account_code' => '2030', // Unearned Markup Liability reduction
                'debit' => $payment->markup_paid,
                'credit' => 0,
                'memo' => "Deferred markup released to revenue on #{$agreement->account_number}",
            ];
            $items[] = [
                'account_code' => '4010', // Installment Financing Markup Revenue
                'debit' => 0,
                'credit' => $payment->markup_paid,
                'memo' => "Markup income recognized on #{$agreement->account_number}",
            ];
        }

        // 4. Late fee component (if paid)
        if ($payment->late_fee_paid > 0) {
            $items[] = [
                'account_code' => '4030', // Late fee surcharge revenue
                'debit' => 0,
                'credit' => $payment->late_fee_paid,
                'memo' => "Late fee surcharge collected on #{$agreement->account_number}",
            ];
        }

        return $this->postEntry(
            $payment->company,
            $payment->branch,
            $payment->payment_date ?? Carbon::today(),
            'installment_payment',
            $payment->id,
            "Installment collection #{$payment->payment_number} for Contract #{$agreement->account_number}",
            $items,
            $payment->cashier
        );
    }

    /**
     * 4. Post Late Fee Accrual.
     */
    public function postLateFeeAccrual(InstallmentSchedule $schedule, float $feeAmount): JournalEntry
    {
        $agreement = $schedule->agreement;

        $items = [
            [
                'account_code' => '1038', // AR Late Fees
                'debit' => $feeAmount,
                'credit' => 0,
                'memo' => "Late penalty accrued for Inst #{$schedule->installment_number} on Contract #{$agreement->account_number}",
            ],
            [
                'account_code' => '4030', // Late Payment Surcharge Revenue
                'debit' => 0,
                'credit' => $feeAmount,
                'memo' => "Late penalty revenue recognized for Inst #{$schedule->installment_number}",
            ],
        ];

        return $this->postEntry(
            $agreement->company,
            $agreement->branch,
            Carbon::today(),
            'late_fee_accrual',
            $schedule->id,
            "Late fee penalty of PKR {$feeAmount} accrued on Inst #{$schedule->installment_number} (#{$agreement->account_number})",
            $items
        );
    }

    /**
     * 5. Post Late Fee Supervisory Waiver.
     */
    public function postLateFeeWaiver(LateFeeWaiver $waiver): JournalEntry
    {
        $agreement = $waiver->agreement;

        $items = [
            [
                'account_code' => '5030', // Late Fee Waivers Expense
                'debit' => $waiver->waived_amount,
                'credit' => 0,
                'memo' => "Late fee waiver expense: {$waiver->reason}",
            ],
            [
                'account_code' => '1038', // AR Late Fees
                'debit' => 0,
                'credit' => $waiver->waived_amount,
                'memo' => "Late fee receivable write-off for Agreement #{$agreement->account_number}",
            ],
        ];

        return $this->postEntry(
            $waiver->company,
            $waiver->branch,
            $waiver->created_at ?? Carbon::today(),
            'late_fee_waiver',
            $waiver->id,
            "Supervisory late fee waiver of PKR {$waiver->waived_amount} on Contract #{$agreement->account_number}",
            $items,
            $waiver->waivedBy
        );
    }

    /**
     * 6. Post Early Contract Settlement with Unearned Markup Rebate.
     */
    public function postEarlySettlement(InstallmentAgreement $agreement, array $settlement, Payment $payment): JournalEntry
    {
        $cashOrBankCode = in_array(strtolower($payment->payment_method), ['bank_transfer', 'cheque', 'online']) ? '1020' : '1010';

        $items = [
            // Dr Cash/Bank with net settlement paid
            [
                'account_code' => $cashOrBankCode,
                'debit' => $settlement['net_settlement_amount'],
                'credit' => 0,
                'memo' => "Early payoff settlement received for #{$agreement->account_number}",
            ],
            // Dr Early Settlement Markup Rebate Concession
            [
                'account_code' => '5040',
                'debit' => $settlement['markup_rebate'],
                'credit' => 0,
                'memo' => "Unearned markup rebate concession ({$settlement['rebate_pct']}%) on early pre-closure",
            ],
            // Dr Unearned Financing Markup (Deferred Income derecognition)
            [
                'account_code' => '2030',
                'debit' => $settlement['unearned_markup'],
                'credit' => 0,
                'memo' => "Deferred markup released upon full settlement of #{$agreement->account_number}",
            ],
            // Cr AR Financed Principal
            [
                'account_code' => '1030',
                'debit' => 0,
                'credit' => $settlement['remaining_principal'],
                'memo' => "Full principal liquidation on #{$agreement->account_number}",
            ],
            // Cr AR Unearned Markup
            [
                'account_code' => '1035',
                'debit' => 0,
                'credit' => $settlement['unearned_markup'],
                'memo' => "Markup receivable cleared on #{$agreement->account_number}",
            ],
            // Cr Retained Markup Income
            [
                'account_code' => '4010',
                'debit' => 0,
                'credit' => $settlement['retained_markup'],
                'memo' => "Retained financing profit recognized upon settlement #{$agreement->account_number}",
            ],
        ];

        // Late fees paid in settlement
        if (($settlement['accrued_late_fees'] ?? 0) > 0) {
            $items[] = [
                'account_code' => '4030',
                'debit' => 0,
                'credit' => $settlement['accrued_late_fees'],
                'memo' => "Late penalties cleared in settlement #{$agreement->account_number}",
            ];
        }

        return $this->postEntry(
            $agreement->company,
            $agreement->branch,
            $payment->payment_date ?? Carbon::today(),
            'early_settlement',
            $agreement->id,
            "Early payoff pre-closure settlement for Contract #{$agreement->account_number}",
            $items,
            $payment->cashier
        );
    }

    /**
     * 7. Post Asset Repossession.
     */
    public function postRepossession(RecoveryCase $case, SerializedItem $item, float $assessedValue): JournalEntry
    {
        $agreement = $case->agreement;
        $remainingPrincipal = (float) $case->remaining_balance;

        $items = [
            // Dr Repossessed Merchandise Inventory Asset
            [
                'account_code' => '1050',
                'debit' => $assessedValue,
                'credit' => 0,
                'memo' => "Repossessed merchandise inventory valuation for S/N: {$item->serial_number}",
            ],
        ];

        if ($assessedValue < $remainingPrincipal) {
            $loss = round($remainingPrincipal - $assessedValue, 2);
            $items[] = [
                'account_code' => '5050', // Loss on Asset Repossession
                'debit' => $loss,
                'credit' => 0,
                'memo' => "Loss on repossession deficit for Case #{$case->case_number}",
            ];
            $items[] = [
                'account_code' => '1030', // AR Principal cleared
                'debit' => 0,
                'credit' => $remainingPrincipal,
                'memo' => "Principal receivable cleared upon repossession #{$agreement->account_number}",
            ];
        } else {
            $gain = round($assessedValue - $remainingPrincipal, 2);
            $items[] = [
                'account_code' => '1030',
                'debit' => 0,
                'credit' => $remainingPrincipal,
                'memo' => "Principal receivable cleared upon repossession #{$agreement->account_number}",
            ];
            if ($gain > 0) {
                $items[] = [
                    'account_code' => '4050', // Gain on Repossession
                    'debit' => 0,
                    'credit' => $gain,
                    'memo' => "Gain on asset repossession valuation for Case #{$case->case_number}",
                ];
            }
        }

        return $this->postEntry(
            $case->company,
            $case->branch,
            Carbon::today(),
            'repossession',
            $case->id,
            "Physical repossession of {$item->serial_number} for Case #{$case->case_number} (#{$agreement->account_number})",
            $items
        );
    }

    /**
     * 8. Post Bad Debt Write-off.
     */
    public function postWriteOff(RecoveryCase $case, float $writeOffAmount, ?User $authorizedBy = null): JournalEntry
    {
        $agreement = $case->agreement;

        $items = [
            [
                'account_code' => '5020', // Bad Debt Expense
                'debit' => $writeOffAmount,
                'credit' => 0,
                'memo' => "Bad debt write-off loss on defaulted Case #{$case->case_number}",
            ],
            [
                'account_code' => '1030', // AR Principal
                'debit' => 0,
                'credit' => $writeOffAmount,
                'memo' => "Derecognition of unrecoverable receivable #{$agreement->account_number}",
            ],
        ];

        return $this->postEntry(
            $case->company,
            $case->branch,
            Carbon::today(),
            'write_off',
            $case->id,
            "Unrecoverable bad debt write-off of PKR {$writeOffAmount} for Case #{$case->case_number}",
            $items,
            $authorizedBy
        );
    }
}
