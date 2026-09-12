<?php

namespace App\Services\Report;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use App\Models\Payment;
use App\Models\ProductCategory;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsService
{
    /**
     * Compute Executive KPI Summary.
     */
    public function getExecutiveKpis(int $companyId, ?int $branchId = null): array
    {
        $agreementsQuery = InstallmentAgreement::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        $activeAgreements = (clone $agreementsQuery)
            ->where('status', 'active')
            ->with(['schedules'])
            ->get();

        $totalPortfolio = (float) $activeAgreements->sum('remaining_balance');
        $activeCount = $activeAgreements->count();

        $completedCount = (clone $agreementsQuery)->where('status', 'completed')->count();
        $defaultedCount = (clone $agreementsQuery)->whereIn('status', ['defaulted', 'repossessed', 'written_off'])->count();
        $totalContracts = $activeCount + $completedCount + $defaultedCount;

        // Portfolio at Risk (PAR) calculation
        $par30Amount = 0.0;
        $par60Amount = 0.0;
        $par90Amount = 0.0;
        $par30Count = 0;
        $par60Count = 0;
        $par90Count = 0;

        $today = Carbon::today();

        foreach ($activeAgreements as $agreement) {
            $oldestOverdue = $agreement->schedules
                ->where('status', '!=', 'paid')
                ->filter(fn($s) => Carbon::parse($s->due_date)->lt($today))
                ->sortBy('due_date')
                ->first();

            if ($oldestOverdue) {
                $daysOverdue = Carbon::parse($oldestOverdue->due_date)->diffInDays($today, false);
                $balance = (float) $agreement->remaining_balance;

                if ($daysOverdue > 30) {
                    $par30Amount += $balance;
                    $par30Count++;
                }
                if ($daysOverdue > 60) {
                    $par60Amount += $balance;
                    $par60Count++;
                }
                if ($daysOverdue > 90) {
                    $par90Amount += $balance;
                    $par90Count++;
                }
            }
        }

        $par30Ratio = $totalPortfolio > 0 ? round(($par30Amount / $totalPortfolio) * 100, 2) : 0.0;
        $par60Ratio = $totalPortfolio > 0 ? round(($par60Amount / $totalPortfolio) * 100, 2) : 0.0;
        $par90Ratio = $totalPortfolio > 0 ? round(($par90Amount / $totalPortfolio) * 100, 2) : 0.0;

        // Current month collection efficiency
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::today()->endOfMonth()->toDateString();

        $monthBilled = (float) InstallmentSchedule::whereHas('agreement', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId)
                ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
        })
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $monthCollected = (float) Payment::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'posted')
            ->whereBetween('payment_date', [$startOfMonth, Carbon::today()->toDateString()])
            ->sum('amount');

        $collectionEfficiency = $monthBilled > 0 ? round(($monthCollected / $monthBilled) * 100, 1) : 100.0;

        // Gross financed sales
        $totalSalesFinanced = (float) (clone $agreementsQuery)->sum('financed_principal');
        $totalDownPayments = (float) (clone $agreementsQuery)->sum('down_payment_paid');
        $totalGrossVolume = $totalSalesFinanced + $totalDownPayments;

        return [
            'total_portfolio' => round($totalPortfolio, 2),
            'active_accounts' => $activeCount,
            'completed_accounts' => $completedCount,
            'defaulted_accounts' => $defaultedCount,
            'total_completed_contracts' => $completedCount,
            'total_defaulted_contracts' => $defaultedCount,
            'total_contracts' => $totalContracts,
            'par30_amount' => round($par30Amount, 2),
            'par_30_amount' => round($par30Amount, 2),
            'par30_ratio' => $par30Ratio,
            'par_30_pct' => $par30Ratio,
            'par30_count' => $par30Count,
            'par60_amount' => round($par60Amount, 2),
            'par_60_amount' => round($par60Amount, 2),
            'par60_ratio' => $par60Ratio,
            'par_60_pct' => $par60Ratio,
            'par60_count' => $par60Count,
            'par90_amount' => round($par90Amount, 2),
            'par_90_amount' => round($par90Amount, 2),
            'par90_ratio' => $par90Ratio,
            'par_90_pct' => $par90Ratio,
            'par90_count' => $par90Count,
            'month_billed' => round($monthBilled, 2),
            'monthly_billed' => round($monthBilled, 2),
            'month_collected' => round($monthCollected, 2),
            'monthly_collected' => round($monthCollected, 2),
            'collection_efficiency' => $collectionEfficiency,
            'total_gross_volume' => round($totalGrossVolume, 2),
            'gross_sales_volume' => round($totalGrossVolume, 2),
            'total_sales_financed' => round($totalSalesFinanced, 2),
            'total_down_payments' => round($totalDownPayments, 2),
        ];
    }

    /**
     * Compute Detailed Aging Bucket Report.
     */
    public function getAgingReport(int $companyId, ?int $branchId = null, ?int $categoryId = null, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();

        $query = InstallmentAgreement::where('company_id', $companyId)
            ->where('status', 'active')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($categoryId, fn($q) => $q->whereHas('product', fn($pq) => $pq->where('category_id', $categoryId)))
            ->with(['customer', 'product.category', 'branch', 'schedules']);

        $agreements = $query->get();

        $buckets = [
            'current' => ['label' => 'Current (0-30 Days)', 'badge' => 'success', 'count' => 0, 'portfolio' => 0.0, 'amount' => 0.0, 'overdue_amount' => 0.0, 'late_fees' => 0.0, 'pct' => 0.0, 'percentage' => 0.0],
            'par30' => ['label' => 'PAR 30 (31-60 Days)', 'badge' => 'warning text-dark', 'count' => 0, 'portfolio' => 0.0, 'amount' => 0.0, 'overdue_amount' => 0.0, 'late_fees' => 0.0, 'pct' => 0.0, 'percentage' => 0.0],
            'par60' => ['label' => 'PAR 60 (61-90 Days)', 'badge' => 'warning', 'count' => 0, 'portfolio' => 0.0, 'amount' => 0.0, 'overdue_amount' => 0.0, 'late_fees' => 0.0, 'pct' => 0.0, 'percentage' => 0.0],
            'par90' => ['label' => 'PAR 90 (91-180 Days)', 'badge' => 'danger', 'count' => 0, 'portfolio' => 0.0, 'amount' => 0.0, 'overdue_amount' => 0.0, 'late_fees' => 0.0, 'pct' => 0.0, 'percentage' => 0.0],
            'loss' => ['label' => 'Loss (180+ Days)', 'badge' => 'dark', 'count' => 0, 'portfolio' => 0.0, 'amount' => 0.0, 'overdue_amount' => 0.0, 'late_fees' => 0.0, 'pct' => 0.0, 'percentage' => 0.0],
        ];

        $rows = [];
        $totalPortfolio = 0.0;
        $totalOverdueSum = 0.0;
        $totalLateFeesSum = 0.0;

        foreach ($agreements as $agreement) {
            $unpaidPastDue = $agreement->schedules
                ->where('status', '!=', 'paid')
                ->filter(fn($s) => Carbon::parse($s->due_date)->lte($asOfDate))
                ->sortBy('due_date');

            $oldest = $unpaidPastDue->first();
            $daysOverdue = 0;
            $overdueAmount = (float) $unpaidPastDue->sum('remaining_balance');
            $lateFees = (float) $unpaidPastDue->sum('late_fee_amount');
            $balance = (float) $agreement->remaining_balance;

            if ($oldest) {
                $daysOverdue = Carbon::parse($oldest->due_date)->diffInDays($asOfDate, false);
            }

            // Determine bucket
            if ($daysOverdue <= 30) {
                $bucketKey = 'current';
            } elseif ($daysOverdue <= 60) {
                $bucketKey = 'par30';
            } elseif ($daysOverdue <= 90) {
                $bucketKey = 'par60';
            } elseif ($daysOverdue <= 180) {
                $bucketKey = 'par90';
            } else {
                $bucketKey = 'loss';
            }

            $buckets[$bucketKey]['count']++;
            $buckets[$bucketKey]['portfolio'] += $balance;
            $buckets[$bucketKey]['amount'] += $balance;
            $buckets[$bucketKey]['overdue_amount'] += $overdueAmount;
            $buckets[$bucketKey]['late_fees'] += $lateFees;

            $totalPortfolio += $balance;
            $totalOverdueSum += $overdueAmount;
            $totalLateFeesSum += $lateFees;

            $rows[] = [
                'agreement_id' => $agreement->id,
                'agreement_number' => $agreement->account_number ?? 'AGR-' . $agreement->id,
                'customer_name' => $agreement->customer?->full_name ?? 'N/A',
                'customer_cnic' => $agreement->customer?->cnic ?? 'N/A',
                'customer_mobile' => $agreement->customer?->mobile_primary ?? 'N/A',
                'customer_phone' => $agreement->customer?->mobile_primary ?? 'N/A',
                'product_name' => $agreement->product?->model_name ?? $agreement->product?->brand ?? 'Merchandise',
                'category_name' => $agreement->product?->category?->name ?? 'Appliances',
                'branch_name' => $agreement->branch?->name ?? 'HQ',
                'total_financed' => (float) ($agreement->total_payable ?? $agreement->total_financed),
                'total_paid' => (float) $agreement->total_paid,
                'remaining_balance' => $balance,
                'days_overdue' => $daysOverdue,
                'overdue_amount' => $overdueAmount,
                'late_fees' => $lateFees,
                'bucket' => $buckets[$bucketKey]['label'],
                'bucket_key' => $bucketKey,
                'bucket_label' => $buckets[$bucketKey]['label'],
                'bucket_badge' => $buckets[$bucketKey]['badge'],
                'oldest_due_date' => $oldest ? Carbon::parse($oldest->due_date)->format('d-M-Y') : null,
                'status' => $agreement->status,
            ];
        }

        // Calculate bucket portfolio percentages
        foreach ($buckets as $k => $b) {
            $buckets[$k]['portfolio'] = round($b['portfolio'], 2);
            $buckets[$k]['amount'] = round($b['portfolio'], 2);
            $buckets[$k]['overdue_amount'] = round($b['overdue_amount'], 2);
            $buckets[$k]['late_fees'] = round($b['late_fees'], 2);
            $pct = $totalPortfolio > 0 ? round(($b['portfolio'] / $totalPortfolio) * 100, 1) : 0.0;
            $buckets[$k]['percentage'] = $pct;
            $buckets[$k]['pct'] = $pct;
        }

        // Sort rows by days overdue descending
        usort($rows, fn($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        $summary = [
            'current' => $buckets['current'],
            'par_30' => $buckets['par30'],
            'par30' => $buckets['par30'],
            'par_60' => $buckets['par60'],
            'par60' => $buckets['par60'],
            'par_90' => $buckets['par90'],
            'par90' => $buckets['par90'],
            'loss' => $buckets['loss'],
        ];

        return [
            'as_of_date' => $asOfDate->toDateString(),
            'total_portfolio' => round($totalPortfolio, 2),
            'total_overdue' => round($totalOverdueSum, 2),
            'total_late_fees' => round($totalLateFeesSum, 2),
            'total_accounts' => count($rows),
            'buckets' => $buckets,
            'summary' => $summary,
            'rows' => $rows,
            'records' => collect($rows),
        ];
    }

    /**
     * Compute Collection Efficiency over time.
     */
    public function getCollectionEfficiency(int $companyId, ?int $branchId = null, int $months = 6): array
    {
        $results = [];
        $today = Carbon::today();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = (clone $today)->subMonths($i);
            $start = (clone $date)->startOfMonth()->toDateString();
            $end = (clone $date)->endOfMonth()->toDateString();
            $monthName = $date->format('M Y');

            $billed = (float) InstallmentSchedule::whereHas('agreement', function ($q) use ($companyId, $branchId) {
                $q->where('company_id', $companyId)
                    ->when($branchId, fn($bq) => $bq->where('branch_id', $branchId));
            })
                ->whereBetween('due_date', [$start, $end])
                ->sum('total_amount');

            $collected = (float) Payment::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->where('status', 'posted')
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            $rate = $billed > 0 ? round(($collected / $billed) * 100, 1) : 100.0;

            $results[] = [
                'month' => $monthName,
                'label' => $monthName,
                'start_date' => $start,
                'end_date' => $end,
                'billed' => round($billed, 2),
                'target_billed' => round($billed, 2),
                'collected' => round($collected, 2),
                'actual_collected' => round($collected, 2),
                'rate' => $rate,
                'efficiency_pct' => $rate,
            ];
        }

        // Payment method breakdown for the entire period
        $methodStats = Payment::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'posted')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('payment_method')
            ->get();

        $paymentMethods = [];
        foreach ($methodStats as $stat) {
            $paymentMethods[] = [
                'method' => $stat->payment_method,
                'count' => (int) $stat->count,
                'total' => (float) $stat->total_amount,
                'total_amount' => (float) $stat->total_amount,
            ];
        }

        // Cashier recovery performance
        $cashierStats = Payment::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'posted')
            ->with(['cashier'])
            ->selectRaw('cashier_id, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('cashier_id')
            ->get();

        $cashierPerformance = [];
        foreach ($cashierStats as $c) {
            $cashierPerformance[] = [
                'cashier_id' => $c->cashier_id,
                'name' => $c->cashier?->name ?? 'Cashier Staff',
                'transactions_count' => (int) $c->count,
                'total_collected' => (float) $c->total_amount,
            ];
        }

        $periodTarget = round(array_sum(array_column($results, 'billed')), 2);
        $periodCollected = round(array_sum(array_column($results, 'collected')), 2);
        $overallEfficiency = $periodTarget > 0 ? round(($periodCollected / $periodTarget) * 100, 1) : 100.0;

        return [
            'monthly_trend' => $results,
            'method_breakdown' => $methodStats,
            'payment_methods' => $paymentMethods,
            'cashier_performance' => $cashierPerformance,
            'period_target' => $periodTarget,
            'period_collected' => $periodCollected,
            'overall_efficiency' => $overallEfficiency,
        ];
    }

    /**
     * Multi-Branch Showroom Comparative Leaderboard.
     */
    public function getBranchPerformance(int $companyId): array
    {
        $branches = Branch::where('company_id', $companyId)->get();
        $list = [];

        $today = Carbon::today();
        $startOfMonth = (clone $today)->startOfMonth()->toDateString();

        foreach ($branches as $branch) {
            $activeAgreements = InstallmentAgreement::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->with(['schedules'])
                ->get();

            $portfolio = (float) $activeAgreements->sum('remaining_balance');
            $activeCount = $activeAgreements->count();

            $par30 = 0.0;
            $par90 = 0.0;

            foreach ($activeAgreements as $agr) {
                $oldestOverdue = $agr->schedules
                    ->where('status', '!=', 'paid')
                    ->filter(fn($s) => Carbon::parse($s->due_date)->lt($today))
                    ->sortBy('due_date')
                    ->first();

                if ($oldestOverdue) {
                    $days = Carbon::parse($oldestOverdue->due_date)->diffInDays($today, false);
                    $bal = (float) $agr->remaining_balance;
                    if ($days > 30) {
                        $par30 += $bal;
                    }
                    if ($days > 90) {
                        $par90 += $bal;
                    }
                }
            }

            $par30Pct = $portfolio > 0 ? round(($par30 / $portfolio) * 100, 1) : 0.0;
            $par90Pct = $portfolio > 0 ? round(($par90 / $portfolio) * 100, 1) : 0.0;

            $monthCollected = (float) Payment::where('branch_id', $branch->id)
                ->where('status', 'posted')
                ->whereBetween('payment_date', [$startOfMonth, $today->toDateString()])
                ->sum('amount');

            $monthBilled = (float) InstallmentSchedule::where('branch_id', $branch->id)
                ->whereBetween('due_date', [$startOfMonth, (clone $today)->endOfMonth()->toDateString()])
                ->sum('total_amount');

            $recoveryRate = $monthBilled > 0 ? round(($monthCollected / $monthBilled) * 100, 1) : 100.0;

            $list[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'name' => $branch->name,
                'branch_code' => $branch->code,
                'code' => $branch->code,
                'city' => $branch->city,
                'active_accounts' => $activeCount,
                'total_portfolio' => round($portfolio, 2),
                'portfolio' => round($portfolio, 2),
                'par_30_amount' => round($par30, 2),
                'par30_amount' => round($par30, 2),
                'par_30_pct' => $par30Pct,
                'par30_pct' => $par30Pct,
                'par_90_amount' => round($par90, 2),
                'par90_amount' => round($par90, 2),
                'par_90_pct' => $par90Pct,
                'par90_pct' => $par90Pct,
                'monthly_collected' => round($monthCollected, 2),
                'month_collected' => round($monthCollected, 2),
                'recovery_efficiency' => $recoveryRate,
                'recovery_rate' => $recoveryRate,
            ];
        }

        // Sort by portfolio descending
        usort($list, fn($a, $b) => $b['portfolio'] <=> $a['portfolio']);

        return $list;
    }

    /**
     * Appliance Category & Brand Profitability / Default Breakdown.
     */
    public function getProductCategoryAnalytics(int $companyId, ?int $branchId = null): array
    {
        $categories = ProductCategory::where('company_id', $companyId)->get();
        $stats = [];

        foreach ($categories as $cat) {
            $agreements = InstallmentAgreement::where('company_id', $companyId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereHas('product', fn($q) => $q->where('category_id', $cat->id))
                ->get();

            $totalContracts = $agreements->count();
            $totalVolume = (float) $agreements->sum('financed_principal');
            $activeContracts = $agreements->where('status', 'active')->count();
            $defaultedContracts = $agreements->whereIn('status', ['defaulted', 'repossessed', 'written_off'])->count();
            $defaultRate = $totalContracts > 0 ? round(($defaultedContracts / $totalContracts) * 100, 1) : 0.0;

            $stats[] = [
                'category_id' => $cat->id,
                'category_name' => $cat->name,
                'name' => $cat->name,
                'code' => $cat->code,
                'units_sold' => $totalContracts,
                'total_units' => $totalContracts,
                'total_financed_volume' => round($totalVolume, 2),
                'financed_volume' => round($totalVolume, 2),
                'active_contracts' => $activeContracts,
                'active_units' => $activeContracts,
                'defaulted_contracts' => $defaultedContracts,
                'defaulted_units' => $defaultedContracts,
                'default_rate_pct' => $defaultRate,
                'default_rate' => $defaultRate,
                'average_ticket_size' => $totalContracts > 0 ? round($totalVolume / $totalContracts, 2) : 0.0,
            ];
        }

        // Sort by volume descending
        usort($stats, fn($a, $b) => $b['financed_volume'] <=> $a['financed_volume']);

        return $stats;
    }

    /**
     * Stream CSV Export.
     */
    public function exportCsv(string $type, int $companyId, array $filters = []): StreamedResponse
    {
        $filename = "{$type}-report-" . date('Y-m-d-His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($type, $companyId, $filters) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel Urdu/English character support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($type === 'aging') {
                $report = $this->getAgingReport(
                    $companyId,
                    $filters['branch_id'] ?? null,
                    $filters['category_id'] ?? null,
                    $filters['as_of_date'] ?? null
                );

                fputcsv($handle, ['Agreement #', 'Customer Name', 'CNIC', 'Mobile', 'Appliance', 'Category', 'Showroom', 'Financed Amount (PKR)', 'Paid Amount (PKR)', 'Remaining Balance (PKR)', 'Days Overdue', 'Overdue Amount (PKR)', 'Late Surcharges (PKR)', 'Aging Bucket', 'Oldest Due Date']);

                foreach ($report['rows'] as $row) {
                    fputcsv($handle, [
                        $row['agreement_number'],
                        $row['customer_name'],
                        $row['customer_cnic'],
                        $row['customer_mobile'],
                        $row['product_name'],
                        $row['category_name'],
                        $row['branch_name'],
                        number_format($row['total_financed'], 2, '.', ''),
                        number_format($row['total_paid'], 2, '.', ''),
                        number_format($row['remaining_balance'], 2, '.', ''),
                        $row['days_overdue'],
                        number_format($row['overdue_amount'], 2, '.', ''),
                        number_format($row['late_fees'], 2, '.', ''),
                        $row['bucket_label'],
                        $row['oldest_due_date'] ?? 'N/A',
                    ]);
                }
            } elseif ($type === 'branches') {
                $branches = $this->getBranchPerformance($companyId);
                fputcsv($handle, ['Branch Code', 'Showroom Name', 'City', 'Active Accounts', 'Gross Portfolio (PKR)', 'PAR 30 Amount (PKR)', 'PAR 30 %', 'PAR 90 Amount (PKR)', 'PAR 90 %', 'Current Month Collections (PKR)', 'Recovery Efficiency %']);

                foreach ($branches as $b) {
                    fputcsv($handle, [
                        $b['code'],
                        $b['name'],
                        $b['city'],
                        $b['active_accounts'],
                        number_format($b['portfolio'], 2, '.', ''),
                        number_format($b['par30_amount'], 2, '.', ''),
                        $b['par30_pct'] . '%',
                        number_format($b['par90_amount'], 2, '.', ''),
                        $b['par90_pct'] . '%',
                        number_format($b['month_collected'], 2, '.', ''),
                        $b['recovery_rate'] . '%',
                    ]);
                }
            } elseif ($type === 'collections') {
                $report = $this->getCollectionEfficiency($companyId, $filters['branch_id'] ?? null, 12);
                fputcsv($handle, ['Billing Month', 'Target Billed Amount (PKR)', 'Actual Cash Collected (PKR)', 'Collection Efficiency %']);

                foreach ($report['monthly_trend'] as $m) {
                    fputcsv($handle, [
                        $m['month'],
                        number_format($m['billed'], 2, '.', ''),
                        number_format($m['collected'], 2, '.', ''),
                        $m['rate'] . '%',
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
