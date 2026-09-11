<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CreditAssessment;
use App\Models\Customer;
use App\Services\Credit\CreditScoringEngine;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CreditAssessmentController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected CreditScoringEngine $scoringEngine
    ) {}

    /**
     * Display underwriting center & credit assessments directory.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user->can('credit.assess') && ! $user->can('credit.approve')) {
            abort(403, 'Unauthorized to access credit underwriting center.');
        }

        $company = $this->tenantContext->getCompany();

        $query = CreditAssessment::where('company_id', $company->id)
            ->with(['customer', 'assessedBy', 'latestApproval.approvedBy']);

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Risk Tier Filter
        if ($tier = $request->input('risk_tier')) {
            $query->where('risk_tier', $tier);
        }

        // Search Filter (Customer name, CNIC, Assessment ULID)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ulid', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('mobile_primary', 'like', "%{$search}%");
                    });
            });
        }

        $assessments = $query->orderBy('assessed_at', 'desc')->paginate(15)->withQueryString();

        // Metrics for summary cards
        $totalAssessments = CreditAssessment::where('company_id', $company->id)->count();
        $pendingCount = CreditAssessment::where('company_id', $company->id)->where('status', 'pending_approval')->count();
        $approvedCount = CreditAssessment::where('company_id', $company->id)->where('status', 'approved')->count();
        $rejectedCount = CreditAssessment::where('company_id', $company->id)->where('status', 'rejected')->count();
        $avgDti = round((float) CreditAssessment::where('company_id', $company->id)->avg('calculated_dti_percentage'), 1);

        return view('tenant.credit.assessments.index', compact(
            'assessments',
            'totalAssessments',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'avgDti'
        ));
    }

    /**
     * Show the credit assessment underwriting form for a specific customer.
     */
    public function create(Request $request, Customer $customer): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('credit.assess')) {
            abort(403, 'Unauthorized to conduct credit assessments.');
        }

        $company = $this->tenantContext->getCompany();
        if ($customer->company_id !== $company->id) {
            abort(404, 'Customer record not found.');
        }

        if ($customer->isBlacklisted()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Cannot conduct credit assessment: Customer is blacklisted on credit risk register.');
        }

        $customer->load(['guarantors', 'verifications', 'creditProfile']);

        // Default baseline calculation
        $defaultIncome = (float) ($customer->monthly_household_income ?? 60000.00);
        $defaultInstallment = round($defaultIncome * 0.25, -2);
        $initialDti = $this->scoringEngine->calculateDti($defaultIncome, $defaultInstallment, 0.0);
        $initialScore = $this->scoringEngine->computeCreditScore($customer, $defaultIncome, $defaultInstallment, 0.0);
        $initialRiskTier = $this->scoringEngine->evaluateRiskTier($initialDti, $initialScore);
        $suggestedLimit = $this->scoringEngine->calculateRecommendedLimit($defaultIncome, $initialDti, $initialScore);

        return view('tenant.credit.assessments.create', compact(
            'customer',
            'defaultIncome',
            'defaultInstallment',
            'initialDti',
            'initialScore',
            'initialRiskTier',
            'suggestedLimit'
        ));
    }

    /**
     * Store a quantitative credit assessment and queue it for managerial approval.
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('credit.assess')) {
            abort(403, 'Unauthorized to conduct credit assessments.');
        }

        $company = $this->tenantContext->getCompany();
        if ($customer->company_id !== $company->id) {
            abort(404, 'Customer record not found.');
        }

        if ($customer->isBlacklisted()) {
            return redirect()->route('customers.show', $customer)
                ->with('error', 'Cannot submit assessment: Customer is blacklisted.');
        }

        $validated = $request->validate([
            'monthly_income' => ['required', 'numeric', 'min:5000', 'max:10000000'],
            'existing_debt_obligations' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'proposed_installment_limit' => ['required', 'numeric', 'min:1000', 'max:10000000'],
            'recommended_limit' => ['required', 'numeric', 'min:10000', 'max:5000000'],
            'recommendation' => ['required', 'in:approved,conditional,rejected'],
            'conditions_summary' => ['nullable', 'string', 'max:1000'],
            'assessment_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $monthlyIncome = (float) $validated['monthly_income'];
        $existingDebts = (float) ($validated['existing_debt_obligations'] ?? 0.0);
        $proposedInstallment = (float) $validated['proposed_installment_limit'];

        // Underwrite through Scoring Engine
        $dti = $this->scoringEngine->calculateDti($monthlyIncome, $proposedInstallment, $existingDebts);
        $score = $this->scoringEngine->computeCreditScore($customer, $monthlyIncome, $proposedInstallment, $existingDebts);
        $riskTier = $this->scoringEngine->evaluateRiskTier($dti, $score);

        $assessment = DB::transaction(function () use ($company, $customer, $user, $validated, $monthlyIncome, $existingDebts, $proposedInstallment, $dti, $score, $riskTier) {
            // Supersede previous pending assessments
            CreditAssessment::where('company_id', $company->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'pending_approval')
                ->update(['status' => 'superseded']);

            return CreditAssessment::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'assessed_by_user_id' => $user->id,
                'monthly_income' => $monthlyIncome,
                'existing_debt_obligations' => $existingDebts,
                'proposed_installment_limit' => $proposedInstallment,
                'calculated_dti_percentage' => $dti,
                'score' => $score,
                'risk_tier' => $riskTier,
                'recommended_limit' => $validated['recommended_limit'],
                'recommendation' => $validated['recommendation'],
                'conditions_summary' => $validated['conditions_summary'] ?? null,
                'assessment_notes' => $validated['assessment_notes'] ?? null,
                'status' => 'pending_approval',
                'assessed_at' => now(),
            ]);
        });

        return redirect()->route('credit.assessments.show', $assessment)
            ->with('success', "Credit assessment recorded successfully with DTI of {$dti}% and Score {$score}/100. Transferred to Managerial Approval Queue.");
    }

    /**
     * Display credit assessment details dossier.
     */
    public function show(CreditAssessment $creditAssessment): View
    {
        $user = request()->user();
        if (! $user->can('credit.assess') && ! $user->can('credit.approve')) {
            abort(403, 'Unauthorized to view credit assessments.');
        }

        $company = $this->tenantContext->getCompany();
        if ($creditAssessment->company_id !== $company->id) {
            abort(404, 'Assessment record not found.');
        }

        $creditAssessment->load([
            'customer.guarantors',
            'customer.verifications',
            'customer.creditProfile',
            'assessedBy',
            'approvals.approvedBy',
        ]);

        return view('tenant.credit.assessments.show', compact('creditAssessment'));
    }
}
