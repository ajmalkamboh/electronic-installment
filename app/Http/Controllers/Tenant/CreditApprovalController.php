<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CreditApproval;
use App\Models\CreditAssessment;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreditApprovalController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Display managerial approval queue for pending credit assessments.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user->can('credit.approve')) {
            abort(403, 'Unauthorized to view credit approval queue.');
        }

        $company = $this->tenantContext->getCompany();

        $pendingAssessments = CreditAssessment::where('company_id', $company->id)
            ->where('status', 'pending_approval')
            ->with(['customer.creditProfile', 'customer.guarantors', 'customer.verifications', 'assessedBy'])
            ->orderBy('assessed_at', 'asc')
            ->paginate(15);

        $recentDecisions = CreditApproval::where('company_id', $company->id)
            ->with(['customer', 'creditAssessment', 'approvedBy'])
            ->orderBy('decided_at', 'desc')
            ->take(5)
            ->get();

        return view('tenant.credit.approvals.index', compact('pendingAssessments', 'recentDecisions'));
    }

    /**
     * Authorize or reject a pending credit assessment.
     */
    public function store(Request $request, CreditAssessment $creditAssessment): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('credit.approve')) {
            abort(403, 'Unauthorized to authorize credit decisions.');
        }

        $company = $this->tenantContext->getCompany();
        if ($creditAssessment->company_id !== $company->id) {
            abort(404, 'Assessment record not found.');
        }

        if ($creditAssessment->status !== 'pending_approval') {
            return back()->with('error', 'This assessment has already been decided and cannot be re-approved.');
        }

        $customer = $creditAssessment->customer;
        if ($customer->isBlacklisted()) {
            return back()->with('error', 'Action rejected: Customer is currently on the blacklisted risk register.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,conditional,rejected'],
            'authorized_credit_limit' => ['required', 'numeric', 'min:0', 'max:5000000'],
            'conditions_imposed' => ['nullable', 'string', 'max:1000'],
            'approval_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approvalLevel = $user->hasRole('company_admin') ? 'level_2_company_admin' : 'level_1_branch_manager';

        DB::transaction(function () use ($company, $creditAssessment, $customer, $user, $validated, $approvalLevel) {
            // 1. Create Credit Approval audit record
            CreditApproval::create([
                'company_id' => $company->id,
                'credit_assessment_id' => $creditAssessment->id,
                'customer_id' => $customer->id,
                'approved_by_user_id' => $user->id,
                'approval_level' => $approvalLevel,
                'decision' => $validated['decision'],
                'authorized_credit_limit' => $validated['decision'] === 'rejected' ? 0.00 : (float) $validated['authorized_credit_limit'],
                'conditions_imposed' => $validated['conditions_imposed'] ?? null,
                'approval_notes' => $validated['approval_notes'] ?? null,
                'decided_at' => now(),
            ]);

            // 2. Update Credit Assessment status
            $newStatus = $validated['decision'] === 'rejected' ? 'rejected' : 'approved';
            $creditAssessment->update(['status' => $newStatus]);

            // 3. If approved / conditional, update customer credit profile
            if ($newStatus === 'approved') {
                $profile = $customer->creditProfile;
                if ($profile) {
                    $profile->update([
                        'max_authorized_credit' => (float) $validated['authorized_credit_limit'],
                        'credit_score' => $creditAssessment->score,
                    ]);
                }

                // If customer is verified and pending_verification, transition to active
                if ($customer->status === 'pending_verification' && $customer->isVerified()) {
                    $customer->update(['status' => 'active']);
                }
            }
        });

        $decisionLabel = ucfirst($validated['decision']);
        return redirect()->route('credit.assessments.show', $creditAssessment)
            ->with('success', "Managerial credit decision recorded: Application has been {$decisionLabel}.");
    }
}
