<?php

namespace App\Services\Payment;

use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleGenerator
{
    /**
     * Generate chronological installment payment schedule for an agreement.
     */
    public function generateSchedule(InstallmentAgreement $agreement): Collection
    {
        // Remove existing schedules if any exist for this agreement
        InstallmentSchedule::where('installment_agreement_id', $agreement->id)->delete();

        $tenure = (int) $agreement->tenure_months;
        if ($tenure <= 0) {
            $tenure = 12;
        }

        $financedPrincipal = (float) $agreement->financed_principal;
        $totalMarkup = (float) $agreement->markup_amount;
        $totalFinanced = (float) $agreement->total_financed;

        // Base monthly amounts rounded to 2 decimals
        $baseMonthlyPrincipal = round($financedPrincipal / $tenure, 2);
        $baseMonthlyMarkup = round($totalMarkup / $tenure, 2);

        $accumulatedPrincipal = 0.0;
        $accumulatedMarkup = 0.0;

        $firstDueDate = Carbon::parse($agreement->first_due_date);
        $frequency = $agreement->installment_frequency ?? 'monthly';

        $schedules = collect();

        for ($i = 1; $i <= $tenure; $i++) {
            // Compute due date according to frequency
            $dueDate = match ($frequency) {
                'weekly' => (clone $firstDueDate)->addWeeks($i - 1),
                'bi_weekly' => (clone $firstDueDate)->addWeeks(($i - 1) * 2),
                'monthly' => (clone $firstDueDate)->addMonths($i - 1),
                default => (clone $firstDueDate)->addMonths($i - 1),
            };

            // Final installment rounding reconciliation to ensure exact sum match
            if ($i === $tenure) {
                $principal = round($financedPrincipal - $accumulatedPrincipal, 2);
                $markup = round($totalMarkup - $accumulatedMarkup, 2);
            } else {
                $principal = $baseMonthlyPrincipal;
                $markup = $baseMonthlyMarkup;
            }

            $accumulatedPrincipal += $principal;
            $accumulatedMarkup += $markup;
            $totalAmount = round($principal + $markup, 2);

            // Determine initial schedule status based on date
            $status = 'pending';
            if ($dueDate->isPast() && ! $dueDate->isToday()) {
                $status = 'overdue';
            } elseif ($dueDate->isToday() || $i === 1) {
                $status = 'due';
            }

            $schedule = InstallmentSchedule::create([
                'company_id' => $agreement->company_id,
                'branch_id' => $agreement->branch_id,
                'installment_agreement_id' => $agreement->id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => $principal,
                'markup_amount' => $markup,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'remaining_balance' => $totalAmount,
                'late_fee_amount' => 0.00,
                'status' => $status,
                'paid_at' => null,
            ]);

            $schedules->push($schedule);
        }

        return $schedules;
    }
}
