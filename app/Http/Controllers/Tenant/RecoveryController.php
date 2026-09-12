<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\InstallmentSchedule;
use App\Models\LateFeeWaiver;
use App\Models\RecoveryCase;
use App\Models\RecoveryNotice;
use App\Services\Recovery\LateFeeEngine;
use App\Services\Recovery\RecoveryEscalationService;
use App\Services\Recovery\WaiverService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecoveryController extends Controller
{
    public function __construct(
        protected LateFeeEngine $lateFeeEngine,
        protected WaiverService $waiverService,
        protected RecoveryEscalationService $escalationService
    ) {}

    /**
     * Recovery & Delinquency Command Center.
     */
    public function dashboard(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');

        $casesQuery = RecoveryCase::where('company_id', $companyId);
        if ($branchId) {
            $casesQuery->where('branch_id', $branchId);
        }

        // Aging DPD buckets
        $aging = [
            'grace_period' => (clone $casesQuery)->where('stage', 'grace_period')->count(),
            'overdue_reminder' => (clone $casesQuery)->where('stage', 'overdue_reminder')->count(),
            'tele_collection' => (clone $casesQuery)->where('stage', 'tele_collection')->count(),
            'field_recovery' => (clone $casesQuery)->where('stage', 'field_recovery')->count(),
            'legal_notice' => (clone $casesQuery)->where('stage', 'legal_notice')->count(),
            'repossession_pending' => (clone $casesQuery)->where('stage', 'repossession_pending')->count(),
            'repossessed' => (clone $casesQuery)->where('stage', 'repossessed')->count(),
        ];

        // Overall KPIs
        $totalOverdue = (clone $casesQuery)->whereNotIn('status', ['settled', 'closed'])->sum('total_overdue_amount');
        $totalLateFees = (clone $casesQuery)->whereNotIn('status', ['settled', 'closed'])->sum('total_late_fees');
        $totalWaived = LateFeeWaiver::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('waived_amount');

        $activeCasesCount = (clone $casesQuery)->whereNotIn('status', ['settled', 'closed'])->count();
        $repossessedCount = (clone $casesQuery)->where('stage', 'repossessed')->count();

        // Recent high-priority cases
        $priorityCases = (clone $casesQuery)
            ->with(['customer', 'agreement.product', 'assignedOfficer', 'branch'])
            ->whereNotIn('status', ['settled', 'closed'])
            ->orderByDesc('days_past_due')
            ->limit(10)
            ->get();

        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.recovery.dashboard', compact(
            'aging',
            'totalOverdue',
            'totalLateFees',
            'totalWaived',
            'activeCasesCount',
            'repossessedCount',
            'priorityCases',
            'branches',
            'branchId'
        ));
    }

    /**
     * Late Fee Ledger & Waiver screen.
     */
    public function lateFees(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');
        $search = $request->get('search');

        $schedulesQuery = InstallmentSchedule::where('company_id', $companyId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->with(['agreement.customer', 'agreement.product', 'branch']);

        if ($branchId) {
            $schedulesQuery->where('branch_id', $branchId);
        }

        if ($search) {
            $schedulesQuery->whereHas('agreement.customer', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%")
                    ->orWhere('mobile_primary', 'like', "%{$search}%");
            })->orWhereHas('agreement', function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%");
            });
        }

        $schedules = $schedulesQuery->orderBy('due_date')->paginate(15)->withQueryString();

        // Recent waivers audit trail
        $recentWaivers = LateFeeWaiver::where('company_id', $companyId)
            ->with(['agreement.customer', 'schedule', 'waivedBy', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        $branches = Branch::where('company_id', $companyId)->get();
        $canWaive = $this->waiverService->canUserWaive(Auth::user());

        return view('tenant.recovery.late_fees', compact(
            'schedules',
            'recentWaivers',
            'branches',
            'branchId',
            'search',
            'canWaive'
        ));
    }

    /**
     * Execute late fee waiver.
     */
    public function waiveLateFee(Request $request, InstallmentSchedule $schedule)
    {
        $validated = $request->validate([
            'waived_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $waiver = $this->waiverService->waiveLateFee(
                $schedule,
                (float) $validated['waived_amount'],
                Auth::user(),
                $validated['reason']
            );

            return redirect()->back()->with('success', "Late fee penalty waiver of PKR " . number_format($waiver->waived_amount, 2) . " approved and audited successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Filterable recovery cases registry.
     */
    public function cases(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $stage = $request->get('stage');
        $status = $request->get('status');
        $branchId = $request->get('branch_id');
        $search = $request->get('search');

        $query = RecoveryCase::where('company_id', $companyId)
            ->with(['customer', 'agreement.product', 'assignedOfficer', 'branch']);

        if ($stage) {
            $query->where('stage', $stage);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('mobile_primary', 'like', "%{$search}%");
                    })
                    ->orWhereHas('agreement', function ($aq) use ($search) {
                        $aq->where('account_number', 'like', "%{$search}%");
                    });
            });
        }

        $cases = $query->orderByDesc('days_past_due')->paginate(15)->withQueryString();
        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.recovery.cases', compact('cases', 'branches', 'stage', 'status', 'branchId', 'search'));
    }

    /**
     * Detailed recovery case docket.
     */
    public function showCase(RecoveryCase $case)
    {
        $this->authorizeAccess($case);

        $case->load([
            'customer.creditProfile',
            'agreement.product',
            'agreement.serializedItem',
            'agreement.guarantors',
            'agreement.schedules' => fn($q) => $q->orderBy('installment_number'),
            'notices.issuedBy',
            'assignedOfficer',
            'repossessionAuthorizedBy',
            'repossessedBy',
            'writtenOffBy',
            'branch',
        ]);

        $canWaive = $this->waiverService->canUserWaive(Auth::user());

        return view('tenant.recovery.show', compact('case', 'canWaive'));
    }

    /**
     * Issue formal legal or overdue notice.
     */
    public function issueNotice(Request $request, RecoveryCase $case)
    {
        $this->authorizeAccess($case);

        $validated = $request->validate([
            'notice_type' => 'required|in:reminder_notice,formal_overdue_notice,guarantor_notice,final_demand_notice,legal_notice,repossession_warrant',
            'recipient_type' => 'required|in:customer,primary_guarantor,secondary_guarantor,all',
            'demand_days' => 'nullable|integer|min:1|max:30',
            'delivery_channel' => 'required|in:hand_delivery,registered_post,sms,whatsapp',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $notice = $this->escalationService->issueNotice(
                $case,
                $validated['notice_type'],
                $validated['recipient_type'],
                Auth::user(),
                [
                    'deadline_days' => $validated['demand_days'] ?? 7,
                    'delivery_channel' => $validated['delivery_channel'],
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            return redirect()->route('recovery.cases.show', $case)
                ->with('success', "Notice #{$notice->notice_number} issued successfully. You can now print or dispatch it.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Render printable A4 legal notice.
     */
    public function printNotice(RecoveryNotice $notice)
    {
        $notice->load(['recoveryCase.customer', 'agreement.product', 'agreement.serializedItem', 'issuedBy', 'company', 'recoveryCase.branch']);
        return view('tenant.recovery.notice_print', compact('notice'));
    }

    /**
     * Supervisory authorization of repossession.
     */
    public function authorizeRepossession(Request $request, RecoveryCase $case)
    {
        $this->authorizeAccess($case);

        $validated = $request->validate([
            'notes' => 'required|string|min:5|max:1000',
        ]);

        try {
            $this->escalationService->authorizeRepossession($case, Auth::user(), $validated['notes']);

            return redirect()->route('recovery.cases.show', $case)
                ->with('success', "Repossession authorization recorded. The collection squad has been authorized to retrieve the serialized product.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Execute physical asset repossession.
     */
    public function executeRepossession(Request $request, RecoveryCase $case)
    {
        $this->authorizeAccess($case);

        $validated = $request->validate([
            'condition' => 'required|in:like_new,good,fair,damaged,scrapped',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $this->escalationService->executeRepossession(
                $case,
                Auth::user(),
                $validated['condition'],
                $validated['notes']
            );

            return redirect()->route('recovery.cases.show', $case)
                ->with('success', "Asset repossession executed successfully. Item has been returned to branch inventory.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bad-debt write-off.
     */
    public function writeOff(Request $request, RecoveryCase $case)
    {
        $this->authorizeAccess($case);

        $validated = $request->validate([
            'loss_amount' => 'required|numeric|min:1',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        try {
            $this->escalationService->writeOffCase(
                $case,
                Auth::user(),
                (float) $validated['loss_amount'],
                $validated['reason']
            );

            return redirect()->route('recovery.cases.show', $case)
                ->with('success', "Unrecoverable debt write-off executed and recorded in the company loss ledger.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Run daily delinquency calculation on-demand.
     */
    public function runAssessment(Request $request)
    {
        $company = Company::find(Auth::user()->company_id);
        $branchId = $request->get('branch_id');
        $branch = $branchId ? Branch::find($branchId) : null;

        $stats = $this->lateFeeEngine->assessAllDelinquencies($company, $branch);

        return redirect()->back()->with(
            'success',
            "Delinquency Assessment Completed! Agreements Evaluated: {$stats['agreements_evaluated']}, Schedules Updated: {$stats['schedules_updated']}, Penalties Accrued: PKR " . number_format($stats['total_penalties_accrued'], 2) . ", Recovery Cases Synced: {$stats['cases_synced']}."
        );
    }

    protected function authorizeAccess(RecoveryCase $case): void
    {
        if ($case->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }
    }
}
