<?php

namespace App\Services\Recovery;

use App\Models\InstallmentSchedule;
use App\Models\LateFeeWaiver;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class WaiverService
{
    /**
     * Check if a user has supervisory authority to waive late penalties.
     */
    public function canUserWaive(User $user): bool
    {
        if ($user->isCompanyAdmin()) {
            return true;
        }

        if ($user->hasRole('branch_manager')) {
            return true;
        }

        return $user->hasPermissionTo('payments.waive_late_fee');
    }

    /**
     * Authorize and record an immutable late fee waiver.
     */
    public function waiveLateFee(
        InstallmentSchedule $schedule,
        float $waiverAmount,
        User $authorizedBy,
        string $reason
    ): LateFeeWaiver {
        if (! $this->canUserWaive($authorizedBy)) {
            throw new AuthorizationException("Unauthorized: Only supervisory roles (Branch Manager or Company Admin) can approve late fee waivers.");
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw new DomainException("A mandatory justification reason must be provided for audit purposes.");
        }

        if ($waiverAmount <= 0) {
            throw new DomainException("Waiver amount must be greater than zero.");
        }

        $currentLateFee = (float) $schedule->late_fee_amount;
        if ($waiverAmount > $currentLateFee) {
            throw new DomainException("Waiver amount (PKR {$waiverAmount}) cannot exceed current accrued late fee (PKR {$currentLateFee}).");
        }

        return DB::transaction(function () use ($schedule, $waiverAmount, $currentLateFee, $authorizedBy, $reason) {
            $remainingLateFee = round($currentLateFee - $waiverAmount, 2);

            $waiver = LateFeeWaiver::create([
                'company_id' => $schedule->company_id,
                'branch_id' => $schedule->branch_id,
                'installment_agreement_id' => $schedule->installment_agreement_id,
                'installment_schedule_id' => $schedule->id,
                'waived_by_id' => $authorizedBy->id,
                'original_late_fee' => $currentLateFee,
                'waived_amount' => $waiverAmount,
                'remaining_late_fee' => $remainingLateFee,
                'reason' => $reason,
            ]);

            $schedule->late_fee_amount = $remainingLateFee;
            $schedule->save();

            // Sync open recovery case total late fees if exists
            $agreement = $schedule->agreement;
            $activeCase = $agreement?->activeRecoveryCase;
            if ($activeCase) {
                $newTotalLateFees = (float) $agreement->schedules()->sum('late_fee_amount');
                $activeCase->total_late_fees = $newTotalLateFees;
                $activeCase->save();
            }

            return $waiver;
        });
    }
}
