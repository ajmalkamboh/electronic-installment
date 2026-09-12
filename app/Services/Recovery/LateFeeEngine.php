<?php

namespace App\Services\Recovery;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LateFeeEngine
{
    public function __construct(
        protected RecoveryEscalationService $escalationService
    ) {}

    /**
     * Resolve late fee parameters for an installment schedule based on Plan overrides or Company defaults.
     */
    public function resolveParameters(InstallmentSchedule $schedule): array
    {
        $agreement = $schedule->agreement;
        $plan = $agreement?->plan;
        $company = $schedule->company ?? $agreement?->company;

        $gracePeriodDays = $plan?->grace_period_days
            ?? $company?->grace_period_days
            ?? 5;

        $feeType = $plan?->late_fee_type
            ?? $company?->late_fee_type
            ?? 'fixed';

        $feeAmount = (float) ($plan?->late_fee_amount
            ?? $company?->late_fee_amount
            ?? 500.00);

        $maxCap = (float) ($plan?->max_penalty_cap
            ?? $company?->max_penalty_cap
            ?? 2500.00);

        return [
            'grace_period_days' => (int) $gracePeriodDays,
            'fee_type' => $feeType,
            'fee_amount' => $feeAmount,
            'max_cap' => $maxCap,
        ];
    }

    /**
     * Calculate accrued late fee for a single schedule as of a specific date.
     */
    public function calculateFee(InstallmentSchedule $schedule, ?Carbon $asOfDate = null): float
    {
        if ($schedule->isPaid()) {
            return 0.00;
        }

        $params = $this->resolveParameters($schedule);
        $daysOverdue = $schedule->daysOverdue($asOfDate);

        // Within grace period -> zero late fee
        if ($daysOverdue <= $params['grace_period_days']) {
            return 0.00;
        }

        $penaltyDays = $daysOverdue - $params['grace_period_days'];
        $fee = 0.00;

        switch ($params['fee_type']) {
            case 'daily_penalty':
                $fee = $params['fee_amount'] * $penaltyDays;
                break;

            case 'percentage':
                // Percentage of installment's remaining balance or total amount
                $base = (float) $schedule->remaining_balance > 0
                    ? (float) $schedule->remaining_balance
                    : (float) $schedule->total_amount;
                $fee = round(($params['fee_amount'] / 100.0) * $base, 2);
                break;

            case 'fixed':
            default:
                $fee = $params['fee_amount'];
                break;
        }

        // Apply maximum penalty cap
        if ($params['max_cap'] > 0) {
            $fee = min($fee, $params['max_cap']);
        }

        // Cap fee at total installment amount to prevent punitive usury
        $fee = min($fee, (float) $schedule->total_amount);

        return round(max(0.00, $fee), 2);
    }

    /**
     * Accrue and persist the calculated late fee on the schedule.
     */
    public function accrueLateFee(InstallmentSchedule $schedule, ?Carbon $asOfDate = null): float
    {
        if ($schedule->isPaid()) {
            return 0.00;
        }

        $calculatedFee = $this->calculateFee($schedule, $asOfDate);
        $daysOverdue = $schedule->daysOverdue($asOfDate);

        // Retain any supervisor waiver adjustments:
        // total previously waived on this schedule
        $totalWaived = (float) $schedule->waivers()->sum('waived_amount');
        $netLateFee = max(0.00, round($calculatedFee - $totalWaived, 2));

        $schedule->late_fee_amount = $netLateFee;

        // If due date has passed and schedule is not paid, update status to overdue
        if ($daysOverdue > 0 && $schedule->status !== 'overdue') {
            $schedule->status = 'overdue';
        }

        $schedule->save();

        return $netLateFee;
    }

    /**
     * Batch assess all delinquencies across active agreements for a company or branch.
     * Returns statistics array.
     */
    public function assessAllDelinquencies(
        Company $company,
        ?Branch $branch = null,
        ?Carbon $asOfDate = null
    ): array {
        $checkDate = $asOfDate ?? Carbon::today();

        $query = InstallmentAgreement::where('company_id', $company->id)
            ->whereIn('status', ['active', 'overdue', 'defaulted']);

        if ($branch) {
            $query->where('branch_id', $branch->id);
        }

        $agreements = $query->with(['schedules', 'customer', 'plan'])->get();

        $stats = [
            'agreements_evaluated' => $agreements->count(),
            'schedules_updated' => 0,
            'total_penalties_accrued' => 0.00,
            'cases_synced' => 0,
            'as_of_date' => $checkDate->toDateString(),
        ];

        DB::transaction(function () use ($agreements, $checkDate, &$stats) {
            foreach ($agreements as $agreement) {
                $hasOverdue = false;
                $agreementMaxDpd = 0;

                foreach ($agreement->schedules as $schedule) {
                    if ($schedule->isPaid()) {
                        continue;
                    }

                    $daysOverdue = $schedule->daysOverdue($checkDate);
                    if ($daysOverdue > 0) {
                        $hasOverdue = true;
                        if ($daysOverdue > $agreementMaxDpd) {
                            $agreementMaxDpd = $daysOverdue;
                        }

                        $fee = $this->accrueLateFee($schedule, $checkDate);
                        $stats['schedules_updated']++;
                        $stats['total_penalties_accrued'] += $fee;
                    }
                }

                // If severely delinquent (e.g. 90+ days), transition to defaulted
                if ($agreementMaxDpd >= 90 && $agreement->status === 'active') {
                    $agreement->status = 'defaulted';
                    $agreement->save();
                }

                // Sync recovery case lifecycle
                if ($hasOverdue) {
                    $this->escalationService->syncAgreementRecoveryCase($agreement, $checkDate);
                    $stats['cases_synced']++;
                }
            }
        });

        $stats['total_penalties_accrued'] = round($stats['total_penalties_accrued'], 2);

        return $stats;
    }
}
