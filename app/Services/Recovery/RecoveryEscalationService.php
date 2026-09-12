<?php

namespace App\Services\Recovery;

use App\Models\Branch;
use App\Models\InstallmentAgreement;
use App\Models\RecoveryCase;
use App\Models\RecoveryNotice;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RecoveryEscalationService
{
    /**
     * Determine recovery escalation stage based on Days Past Due (DPD).
     */
    public function determineEscalationStage(int $daysPastDue): string
    {
        if ($daysPastDue <= 0) {
            return 'resolved';
        }

        if ($daysPastDue <= 5) {
            return 'grace_period';
        }

        if ($daysPastDue <= 14) {
            return 'overdue_reminder';
        }

        if ($daysPastDue <= 29) {
            return 'tele_collection';
        }

        if ($daysPastDue <= 59) {
            return 'field_recovery';
        }

        if ($daysPastDue <= 89) {
            return 'legal_notice';
        }

        return 'repossession_pending';
    }

    /**
     * Generate unique Case Number: RC-{BRANCH}-{YYYYMM}-{SEQ}
     */
    public function generateCaseNumber(Branch $branch): string
    {
        $prefix = 'RC-' . strtoupper($branch->code ?? 'BR') . '-' . date('Ym') . '-';
        $latest = RecoveryCase::where('company_id', $branch->company_id)
            ->where('case_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('case_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique Notice Number: NOT-{BRANCH}-{YYYYMM}-{SEQ}
     */
    public function generateNoticeNumber(Branch $branch): string
    {
        $prefix = 'NOT-' . strtoupper($branch->code ?? 'BR') . '-' . date('Ym') . '-';
        $latest = RecoveryNotice::where('company_id', $branch->company_id)
            ->where('notice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('notice_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Sync or create RecoveryCase for an agreement based on overdue schedules.
     */
    public function syncAgreementRecoveryCase(
        InstallmentAgreement $agreement,
        ?Carbon $asOfDate = null
    ): ?RecoveryCase {
        $checkDate = $asOfDate ?? Carbon::today();

        // Calculate DPD & outstanding overdue amounts
        $unpaidSchedules = $agreement->schedules()
            ->where('status', '!=', 'paid')
            ->get();

        $maxDpd = 0;
        $totalOverdue = 0.00;
        $totalLateFees = 0.00;

        foreach ($unpaidSchedules as $sch) {
            $days = $sch->daysOverdue($checkDate);
            if ($days > 0) {
                if ($days > $maxDpd) {
                    $maxDpd = $days;
                }
                $totalOverdue += (float) $sch->remaining_balance;
            }
            $totalLateFees += (float) $sch->late_fee_amount;
        }

        // If no overdue schedules, check if there is an active case that can now be marked resolved
        if ($maxDpd === 0) {
            $existingCase = RecoveryCase::where('installment_agreement_id', $agreement->id)
                ->whereNotIn('status', ['settled', 'closed'])
                ->first();

            if ($existingCase) {
                $existingCase->stage = 'resolved';
                $existingCase->status = 'settled';
                $existingCase->days_past_due = 0;
                $existingCase->total_overdue_amount = 0.00;
                $existingCase->total_late_fees = 0.00;
                $existingCase->settled_at = Carbon::now();
                $existingCase->save();
            }

            return $existingCase;
        }

        // Find or create open recovery case
        $recoveryCase = RecoveryCase::where('installment_agreement_id', $agreement->id)
            ->whereNotIn('status', ['settled', 'closed', 'written_off'])
            ->first();

        if (! $recoveryCase) {
            $branch = $agreement->branch;
            $caseNumber = $this->generateCaseNumber($branch);

            $recoveryCase = new RecoveryCase([
                'company_id' => $agreement->company_id,
                'branch_id' => $agreement->branch_id,
                'installment_agreement_id' => $agreement->id,
                'customer_id' => $agreement->customer_id,
                'case_number' => $caseNumber,
                'status' => 'open',
            ]);
        }

        $newStage = $this->determineEscalationStage($maxDpd);

        // Do not downgrade stage if already authorized for repossession or repossessed
        $terminalStages = ['repossession_pending', 'repossessed', 'written_off'];
        if (! in_array($recoveryCase->stage, $terminalStages, true)) {
            $recoveryCase->stage = $newStage;
        }

        $recoveryCase->days_past_due = $maxDpd;
        $recoveryCase->total_overdue_amount = round($totalOverdue, 2);
        $recoveryCase->total_late_fees = round($totalLateFees, 2);

        // Auto-assign to collection officer if in field recovery and agreement has active assignment
        if (empty($recoveryCase->assigned_officer_id)) {
            $activeAssignment = $agreement->activeAssignment;
            if ($activeAssignment) {
                $recoveryCase->assigned_officer_id = $activeAssignment->collection_officer_id;
            }
        }

        $recoveryCase->save();

        return $recoveryCase;
    }

    /**
     * Issue a formal legal or overdue notice.
     */
    public function issueNotice(
        RecoveryCase $case,
        string $noticeType,
        string $recipientType,
        User $issuedBy,
        array $options = []
    ): RecoveryNotice {
        $agreement = $case->agreement;
        $customer = $case->customer;
        $branch = $case->branch;

        $recipientName = $customer->full_name;
        $recipientContact = $customer->mobile_primary;
        $recipientAddress = $customer->present_address ?? $customer->city;

        if ($recipientType === 'primary_guarantor') {
            $primaryGuarantor = $agreement->guarantors()->wherePivot('is_primary', true)->first();
            if ($primaryGuarantor) {
                $recipientName = $primaryGuarantor->full_name;
                $recipientContact = $primaryGuarantor->mobile;
                $recipientAddress = $primaryGuarantor->address ?? $primaryGuarantor->city;
            }
        }

        $deadlineDays = (int) ($options['deadline_days'] ?? 7);
        $deadlineDate = Carbon::today()->addDays($deadlineDays)->toDateString();
        $totalDemand = (float) $case->total_overdue_amount + (float) $case->total_late_fees;

        $noticeNumber = $this->generateNoticeNumber($branch);

        return DB::transaction(function () use (
            $case,
            $agreement,
            $noticeNumber,
            $noticeType,
            $recipientType,
            $recipientName,
            $recipientContact,
            $recipientAddress,
            $totalDemand,
            $deadlineDate,
            $issuedBy,
            $options
        ) {
            $notice = RecoveryNotice::create([
                'company_id' => $case->company_id,
                'recovery_case_id' => $case->id,
                'installment_agreement_id' => $agreement->id,
                'notice_number' => $noticeNumber,
                'notice_type' => $noticeType,
                'recipient_type' => $recipientType,
                'recipient_name' => $recipientName,
                'recipient_contact' => $recipientContact,
                'recipient_address' => $recipientAddress,
                'overdue_amount' => $case->total_overdue_amount,
                'late_fees_amount' => $case->total_late_fees,
                'total_demand_amount' => $totalDemand,
                'demand_deadline' => $deadlineDate,
                'issued_at' => Carbon::now(),
                'issued_by_id' => $issuedBy->id,
                'delivery_channel' => $options['delivery_channel'] ?? 'hand_delivery',
                'status' => 'issued',
                'notes' => $options['notes'] ?? null,
            ]);

            // Update case milestone
            if (in_array($noticeType, ['reminder_notice', 'formal_overdue_notice'])) {
                $case->warning_notice_at = Carbon::now();
                $case->warning_notice_ref = $noticeNumber;
            } elseif (in_array($noticeType, ['legal_notice', 'final_demand_notice', 'repossession_warrant'])) {
                $case->legal_notice_at = Carbon::now();
                $case->legal_notice_ref = $noticeNumber;
                $case->status = 'escalated';
            }

            $case->save();

            return $notice;
        });
    }

    /**
     * Supervisory authorization of repossession.
     */
    public function authorizeRepossession(
        RecoveryCase $case,
        User $authorizedBy,
        string $notes
    ): RecoveryCase {
        if (! $authorizedBy->isCompanyAdmin() && ! $authorizedBy->hasRole('branch_manager') && ! $authorizedBy->hasPermissionTo('recovery.repossess')) {
            throw new AuthorizationException("Unauthorized: Only Branch Managers or Company Admins can authorize asset repossession.");
        }

        $case->stage = 'repossession_pending';
        $case->status = 'escalated';
        $case->repossession_authorized_at = Carbon::now();
        $case->repossession_authorized_by_id = $authorizedBy->id;
        $case->repossession_notes = $notes;
        $case->save();

        $agreement = $case->agreement;
        if ($agreement && in_array($agreement->status, ['active', 'overdue'])) {
            $agreement->status = 'defaulted';
            $agreement->save();
        }

        return $case;
    }

    /**
     * Execute asset repossession, updating serialized inventory and contract status.
     */
    public function executeRepossession(
        RecoveryCase $case,
        User $executedBy,
        string $condition,
        ?string $notes = null
    ): RecoveryCase {
        return DB::transaction(function () use ($case, $executedBy, $condition, $notes) {
            $case->stage = 'repossessed';
            $case->status = 'repossessed';
            $case->repossessed_at = Carbon::now();
            $case->repossessed_by_id = $executedBy->id;
            $case->repossessed_condition = $condition;
            $case->repossession_notes = ($case->repossession_notes ? $case->repossession_notes . "\n" : '') . ($notes ?? 'Asset physically recovered.');
            $case->save();

            $agreement = $case->agreement;
            if ($agreement) {
                // If agreement has serialized item, update its status back to in_stock/repossessed
                if ($agreement->serializedItem) {
                    $item = $agreement->serializedItem;
                    $item->status = 'repossessed';
                    $item->notes = "Repossessed from Agreement #{$agreement->account_number}. Condition: {$condition}.";
                    $item->save();
                }

                $agreement->status = 'completed'; // Closed through repossession
                $agreement->save();
            }

            return $case;
        });
    }

    /**
     * Supervisory bad-debt write-off.
     */
    public function writeOffCase(
        RecoveryCase $case,
        User $authorizedBy,
        float $lossAmount,
        string $reason
    ): RecoveryCase {
        if (! $authorizedBy->isCompanyAdmin()) {
            throw new AuthorizationException("Unauthorized: Only Company Administrators can authorize a bad-debt write-off.");
        }

        if ($lossAmount <= 0) {
            throw new DomainException("Write-off amount must be greater than zero.");
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw new DomainException("Mandatory justification is required for writing off unrecoverable debt.");
        }

        return DB::transaction(function () use ($case, $authorizedBy, $lossAmount, $reason) {
            $case->stage = 'written_off';
            $case->status = 'written_off';
            $case->written_off_at = Carbon::now();
            $case->written_off_by_id = $authorizedBy->id;
            $case->written_off_amount = $lossAmount;
            $case->written_off_reason = $reason;
            $case->closed_at = Carbon::now();
            $case->save();

            $agreement = $case->agreement;
            if ($agreement) {
                $agreement->status = 'defaulted';
                $agreement->save();
            }

            return $case;
        });
    }
}
