<?php

namespace App\Services\Collection;

use App\Models\Branch;
use App\Models\CollectionAssignment;
use App\Models\CollectionLog;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CollectionService
{
    /**
     * Assign an installment agreement to a field collection officer.
     */
    public function assignAgreement(
        InstallmentAgreement $agreement,
        User $officer,
        User $assignedBy,
        string $priority = 'normal',
        ?string $notes = null
    ): CollectionAssignment {
        if ($agreement->isCompleted() || $agreement->isCancelled()) {
            throw new DomainException("Cannot assign agreement in '{$agreement->status}' status.");
        }

        return DB::transaction(function () use ($agreement, $officer, $assignedBy, $priority, $notes) {
            // Mark any previous active assignment as reassigned
            CollectionAssignment::where('installment_agreement_id', $agreement->id)
                ->where('status', 'active')
                ->update(['status' => 'reassigned']);

            return CollectionAssignment::create([
                'company_id' => $agreement->company_id,
                'branch_id' => $agreement->branch_id,
                'installment_agreement_id' => $agreement->id,
                'collection_officer_id' => $officer->id,
                'assigned_by_id' => $assignedBy->id,
                'assigned_date' => Carbon::today(),
                'status' => 'active',
                'priority' => in_array($priority, ['normal', 'high', 'urgent']) ? $priority : 'normal',
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Log a field visit or customer interaction.
     */
    public function logInteraction(
        InstallmentAgreement $agreement,
        User $officer,
        array $data
    ): CollectionLog {
        $ptpStatus = null;
        $ptpDate = !empty($data['promise_to_pay_date']) ? Carbon::parse($data['promise_to_pay_date']) : null;

        if ($ptpDate || ($data['interaction_status'] ?? '') === 'promise_to_pay') {
            $ptpStatus = 'pending';
        }

        return CollectionLog::create([
            'company_id' => $agreement->company_id,
            'branch_id' => $agreement->branch_id,
            'installment_agreement_id' => $agreement->id,
            'customer_id' => $agreement->customer_id,
            'collection_officer_id' => $officer->id,
            'visit_date' => !empty($data['visit_date']) ? Carbon::parse($data['visit_date']) : Carbon::now(),
            'interaction_type' => $data['interaction_type'] ?? 'field_visit',
            'interaction_status' => $data['interaction_status'] ?? 'met_customer',
            'promise_to_pay_date' => $ptpDate?->toDateString(),
            'promised_amount' => !empty($data['promised_amount']) ? (float) $data['promised_amount'] : null,
            'ptp_status' => $ptpStatus,
            'location_notes' => $data['location_notes'] ?? null,
            'notes' => $data['notes'] ?? null,
            'follow_up_date' => !empty($data['follow_up_date']) ? Carbon::parse($data['follow_up_date'])->toDateString() : null,
        ]);
    }

    /**
     * Retrieve daily run sheet for a specific collection officer.
     */
    public function getOfficerDailyRunSheet(User $officer, ?string $date = null): Collection
    {
        return CollectionAssignment::where('collection_officer_id', $officer->id)
            ->where('status', 'active')
            ->with([
                'agreement.customer',
                'agreement.product',
                'agreement.schedules' => function ($q) {
                    $q->where('status', '!=', 'paid')->orderBy('installment_number');
                },
                'agreement.latestCollectionLog',
            ])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 ELSE 3 END")
            ->get();
    }

    /**
     * Retrieve submitted field cash payments awaiting cashier drawer handover.
     */
    public function getPendingFieldHandovers(Branch $branch, ?User $officer = null): Collection
    {
        $query = Payment::where('company_id', $branch->company_id)
            ->where('branch_id', $branch->id)
            ->where('status', 'submitted')
            ->with(['collector', 'customer', 'agreement.product'])
            ->latest();

        if ($officer) {
            $query->where('collector_id', $officer->id);
        }

        return $query->get();
    }

    /**
     * Calculate collection dashboard key performance indicators.
     */
    public function getCollectionKpis(Company $company, ?Branch $branch = null): array
    {
        $branchId = $branch?->id;

        $assignmentQuery = CollectionAssignment::where('company_id', $company->id)->where('status', 'active');
        $pendingPtpQuery = CollectionLog::where('company_id', $company->id)->where('ptp_status', 'pending');
        $unsettledCashQuery = Payment::where('company_id', $company->id)->where('status', 'submitted');
        $agreementQuery = InstallmentAgreement::where('company_id', $company->id)->whereIn('status', ['active', 'overdue', 'defaulted']);

        if ($branchId) {
            $assignmentQuery->where('branch_id', $branchId);
            $pendingPtpQuery->where('branch_id', $branchId);
            $unsettledCashQuery->where('branch_id', $branchId);
            $agreementQuery->where('branch_id', $branchId);
        }

        $activeAssignments = $assignmentQuery->count();
        $pendingPtps = $pendingPtpQuery->count();
        $unsettledCash = (float) $unsettledCashQuery->sum('amount');
        $unsettledCount = $unsettledCashQuery->count();

        // Calculate overdue accounts and total overdue balance
        $overdueAccounts = $agreementQuery->whereHas('schedules', function ($q) {
            $q->where('status', 'overdue')
                ->orWhere(function ($sq) {
                    $sq->whereIn('status', ['due', 'partially_paid'])
                        ->where('due_date', '<', Carbon::today()->toDateString());
                });
        })->count();

        return [
            'active_assignments' => $activeAssignments,
            'pending_ptps' => $pendingPtps,
            'unsettled_cash' => $unsettledCash,
            'unsettled_count' => $unsettledCount,
            'overdue_accounts' => $overdueAccounts,
        ];
    }
}
