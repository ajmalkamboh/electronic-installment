<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CollectionAssignment;
use App\Models\CollectionLog;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\Collection\CollectionService;
use App\Services\Payment\PaymentEngine;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected CollectionService $collectionService,
        protected PaymentEngine $paymentEngine
    ) {}

    /**
     * Display Collection & Recovery Command Center.
     */
    public function dashboard(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch();

        $kpis = $this->collectionService->getCollectionKpis($company, $currentBranch);

        // Recent collection activity
        $recentLogs = CollectionLog::where('company_id', $company->id)
            ->when($currentBranch, fn ($q) => $q->where('branch_id', $currentBranch->id))
            ->with(['customer', 'agreement.product', 'collectionOfficer'])
            ->latest('visit_date')
            ->take(10)
            ->get();

        // Pending Promises to Pay (PTP)
        $pendingPtps = CollectionLog::where('company_id', $company->id)
            ->when($currentBranch, fn ($q) => $q->where('branch_id', $currentBranch->id))
            ->where('ptp_status', 'pending')
            ->whereNotNull('promise_to_pay_date')
            ->with(['customer', 'agreement', 'collectionOfficer'])
            ->orderBy('promise_to_pay_date')
            ->take(8)
            ->get();

        // Delinquent agreements needing attention / assignment
        $delinquentAgreements = InstallmentAgreement::where('company_id', $company->id)
            ->when($currentBranch, fn ($q) => $q->where('branch_id', $currentBranch->id))
            ->whereIn('status', ['active', 'overdue', 'defaulted'])
            ->whereHas('schedules', function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sq) {
                        $sq->whereIn('status', ['due', 'partially_paid'])
                            ->where('due_date', '<', Carbon::today()->toDateString());
                    });
            })
            ->with(['customer', 'product', 'activeAssignment.collectionOfficer', 'schedules'])
            ->take(10)
            ->get();

        $officers = $this->getCollectionOfficers($company, $currentBranch);

        return view('tenant.collections.dashboard', compact(
            'kpis',
            'recentLogs',
            'pendingPtps',
            'delinquentAgreements',
            'officers',
            'currentBranch'
        ));
    }

    /**
     * Display Daily Run Sheet for Field Collection Officers.
     */
    public function runSheet(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch();
        $user = $request->user();

        $officers = $this->getCollectionOfficers($company, $currentBranch);

        // Determine which officer's run sheet to view
        $selectedOfficerId = $request->input('officer_id');
        if ($selectedOfficerId) {
            $selectedOfficer = User::where('company_id', $company->id)->findOrFail($selectedOfficerId);
        } elseif ($user->role === 'collection_officer') {
            $selectedOfficer = $user;
        } else {
            $selectedOfficer = $officers->first() ?? $user;
        }

        $date = $request->input('date', Carbon::today()->toDateString());
        $runSheet = $this->collectionService->getOfficerDailyRunSheet($selectedOfficer, $date);

        return view('tenant.collections.run_sheet', compact(
            'runSheet',
            'selectedOfficer',
            'officers',
            'date',
            'currentBranch'
        ));
    }

    /**
     * Printable Daily Run Sheet for field deployment.
     */
    public function printRunSheet(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch();
        $user = $request->user();

        $selectedOfficerId = $request->input('officer_id');
        $selectedOfficer = $selectedOfficerId
            ? User::where('company_id', $company->id)->findOrFail($selectedOfficerId)
            : $user;

        $date = $request->input('date', Carbon::today()->toDateString());
        $runSheet = $this->collectionService->getOfficerDailyRunSheet($selectedOfficer, $date);

        return view('tenant.collections.run_sheet_print', compact(
            'runSheet',
            'selectedOfficer',
            'date',
            'company',
            'currentBranch'
        ));
    }

    /**
     * Display Registry of Collection Assignments.
     */
    public function assignments(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch();

        $query = CollectionAssignment::where('company_id', $company->id)
            ->when($currentBranch, fn ($q) => $q->where('branch_id', $currentBranch->id));

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($officerId = $request->input('officer_id')) {
            $query->where('collection_officer_id', $officerId);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $assignments = $query->with(['agreement.customer', 'agreement.product', 'collectionOfficer', 'assignedBy'])
            ->latest('assigned_date')
            ->paginate(15)
            ->withQueryString();

        $officers = $this->getCollectionOfficers($company, $currentBranch);

        $availableAgreements = InstallmentAgreement::where('company_id', $company->id)
            ->when($currentBranch, fn ($q) => $q->where('branch_id', $currentBranch->id))
            ->whereIn('status', ['active', 'overdue', 'defaulted'])
            ->with(['customer', 'product'])
            ->latest()
            ->take(50)
            ->get();

        return view('tenant.collections.assignments', compact(
            'assignments',
            'officers',
            'availableAgreements',
            'currentBranch'
        ));
    }

    /**
     * Assign or reassign an agreement to a collection officer.
     */
    public function assign(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'installment_agreement_id' => ['required', 'exists:installment_agreements,id'],
            'collection_officer_id' => ['required', 'exists:users,id'],
            'priority' => ['required', 'in:normal,high,urgent'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $agreement = InstallmentAgreement::where('company_id', $company->id)->findOrFail($validated['installment_agreement_id']);
        $officer = User::where('company_id', $company->id)->findOrFail($validated['collection_officer_id']);

        try {
            $assignment = $this->collectionService->assignAgreement(
                agreement: $agreement,
                officer: $officer,
                assignedBy: $request->user(),
                priority: $validated['priority'],
                notes: $validated['notes'] ?? null
            );

            return back()->with('success', "Agreement {$agreement->account_number} assigned to {$officer->name} ({$assignment->priority} priority).");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Log a field visit or customer interaction.
     */
    public function logVisit(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'installment_agreement_id' => ['required', 'exists:installment_agreements,id'],
            'interaction_type' => ['required', 'in:field_visit,phone_call,showroom_visit,guarantor_contact'],
            'interaction_status' => ['required', 'in:met_customer,customer_absent,promise_to_pay,refused_to_pay,dispute_raised,payment_collected'],
            'promise_to_pay_date' => ['nullable', 'date', 'after_or_equal:today'],
            'promised_amount' => ['nullable', 'numeric', 'min:1'],
            'location_notes' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $agreement = InstallmentAgreement::where('company_id', $company->id)->findOrFail($validated['installment_agreement_id']);

        try {
            $this->collectionService->logInteraction(
                agreement: $agreement,
                officer: $request->user(),
                data: $validated
            );

            return back()->with('success', "Visit / interaction logged for account {$agreement->account_number}.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Record cash collection in the field (starts in 'submitted' status).
     */
    public function collectFieldPayment(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'installment_agreement_id' => ['required', 'exists:installment_agreements,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $agreement = InstallmentAgreement::where('company_id', $company->id)->findOrFail($validated['installment_agreement_id']);

        try {
            $payment = $this->paymentEngine->recordFieldCollection(
                agreement: $agreement,
                amount: (float) $validated['amount'],
                collector: $request->user(),
                reference: $validated['reference_number'] ?? null,
                notes: $validated['notes'] ?? null
            );

            return back()->with('success', "Field collection of Rs. " . number_format($payment->amount) . " recorded in 'submitted' status (#{$payment->payment_number}). Awaiting branch cashier handover acknowledgment.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cashier drawer handover reconciliation screen (submitted field payments).
     */
    public function handovers(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch() ?? Branch::where('company_id', $company->id)->first();

        $selectedOfficerId = $request->input('officer_id');
        $officer = $selectedOfficerId ? User::where('company_id', $company->id)->find($selectedOfficerId) : null;

        $pendingHandovers = $this->collectionService->getPendingFieldHandovers($currentBranch, $officer);
        $totalUnsettled = (float) $pendingHandovers->sum('amount');

        $officers = $this->getCollectionOfficers($company, $currentBranch);

        return view('tenant.collections.handovers', compact(
            'pendingHandovers',
            'totalUnsettled',
            'officers',
            'selectedOfficerId',
            'currentBranch'
        ));
    }

    /**
     * Cashier acknowledges physical receipt of field collection cash into branch drawer.
     */
    public function acknowledgeHandover(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'payment_id' => ['nullable', 'exists:payments,id'],
            'collection_officer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            if (!empty($validated['payment_id'])) {
                $payment = Payment::where('company_id', $company->id)->findOrFail($validated['payment_id']);
                $this->paymentEngine->acknowledgeFieldHandover($payment, $request->user(), $validated['notes'] ?? null);
                $message = "Payment #{$payment->payment_number} (Rs. " . number_format($payment->amount) . ") acknowledged and accepted into branch drawer.";
            } elseif (!empty($validated['collection_officer_id'])) {
                $officer = User::where('company_id', $company->id)->findOrFail($validated['collection_officer_id']);
                $payments = Payment::where('company_id', $company->id)
                    ->where('collector_id', $officer->id)
                    ->where('status', 'submitted')
                    ->get();

                $total = 0;
                foreach ($payments as $p) {
                    $this->paymentEngine->acknowledgeFieldHandover($p, $request->user(), $validated['notes'] ?? null);
                    $total += (float) $p->amount;
                }

                $message = "Batch handover complete: " . count($payments) . " collections totaling Rs. " . number_format($total) . " from {$officer->name} acknowledged.";
            } else {
                return back()->withErrors(['error' => 'Please select a specific payment or collection officer to acknowledge handover.']);
            }

            return back()->with('success', $message);
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper to retrieve collection and recovery officers for a branch.
     */
    protected function getCollectionOfficers(Company $company, ?Branch $branch = null)
    {
        return User::where('company_id', $company->id)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->where(function ($q) {
                $q->where('role', 'collection_officer')
                    ->orWhereHas('roleRecord', fn ($rq) => $rq->where('name', 'collection_officer'));
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
