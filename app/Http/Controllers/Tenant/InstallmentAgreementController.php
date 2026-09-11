<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Services\Agreement\AgreementService;
use App\Services\Tenant\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallmentAgreementController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AgreementService $agreementService
    ) {}

    /**
     * Display a listing of installment agreements.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = InstallmentAgreement::where('company_id', $company->id)
            ->with(['customer', 'branch', 'product', 'serializedItem', 'plan', 'creator'])
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('mobile_primary', 'like', "%{$search}%");
                    })
                    ->orWhereHas('serializedItem', function ($sq) use ($search) {
                        $sq->where('imei_1', 'like', "%{$search}%")
                            ->orWhere('serial_number', 'like', "%{$search}%");
                    });
            });
        }

        $agreements = $query->paginate(15)->withQueryString();

        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->get();

        $counts = [
            'all' => InstallmentAgreement::where('company_id', $company->id)->count(),
            'draft' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'draft')->count(),
            'under_review' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'under_review')->count(),
            'approved' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'approved')->count(),
            'active' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'active')->count(),
            'completed' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'completed')->count(),
            'defaulted' => InstallmentAgreement::where('company_id', $company->id)->where('status', 'defaulted')->count(),
        ];

        return view('tenant.agreements.index', compact('agreements', 'branches', 'counts'));
    }

    /**
     * Show the agreement creation wizard.
     */
    public function create(Request $request): View
    {
        $company = $this->tenantContext->getCompany();
        $currentBranch = $this->tenantContext->getBranch();

        $selectedCustomer = null;
        if ($customerId = $request->input('customer_id')) {
            $selectedCustomer = Customer::where('company_id', $company->id)
                ->with(['guarantors' => fn ($q) => $q->where('is_verified', true)])
                ->find($customerId);
        }

        $customers = Customer::where('company_id', $company->id)
            ->where('status', '!=', 'blacklisted')
            ->orderBy('full_name')
            ->get();

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get();

        $plans = InstallmentPlan::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('tenure_months')
            ->get();

        $availableItems = SerializedItem::where('company_id', $company->id)
            ->where('branch_id', $currentBranch->id)
            ->where('status', 'in_stock')
            ->with('product')
            ->get();

        return view('tenant.agreements.create', compact(
            'customers',
            'selectedCustomer',
            'products',
            'plans',
            'availableItems',
            'currentBranch'
        ));
    }

    /**
     * Store a newly created agreement draft in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'serialized_item_id' => ['nullable', 'exists:serialized_items,id'],
            'installment_plan_id' => ['required', 'exists:installment_plans,id'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'markup_rate' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'start_date' => ['nullable', 'date'],
            'first_due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'guarantor_ids' => ['nullable', 'array'],
            'guarantor_ids.*' => ['exists:guarantors,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $agreement = $this->agreementService->createDraft($validated, $request->user());

            return redirect()->route('agreements.show', $agreement->id)
                ->with('success', "Installment Agreement {$agreement->account_number} drafted successfully. Selected hardware unit is now reserved.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified agreement dossier.
     */
    public function show(InstallmentAgreement $agreement): View
    {
        $this->authorizeAgreement($agreement);

        $agreement->load([
            'customer.guarantors',
            'branch',
            'product.category',
            'serializedItem',
            'plan',
            'guarantors',
            'creator',
            'approvedBy',
            'disbursedBy',
        ]);

        return view('tenant.agreements.show', compact('agreement'));
    }

    /**
     * Submit draft agreement for underwriting review.
     */
    public function submit(InstallmentAgreement $agreement, Request $request): RedirectResponse
    {
        $this->authorizeAgreement($agreement);

        try {
            $this->agreementService->submitForReview($agreement, $request->user());

            return back()->with('success', "Agreement {$agreement->account_number} submitted for underwriting review.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Approve agreement.
     */
    public function approve(InstallmentAgreement $agreement, Request $request): RedirectResponse
    {
        $this->authorizeAgreement($agreement);

        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->agreementService->approve($agreement, $request->user(), $validated['approval_notes'] ?? null);

            return back()->with('success', "Agreement {$agreement->account_number} has been approved.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Record down payment collection.
     */
    public function recordDownPayment(InstallmentAgreement $agreement, Request $request): RedirectResponse
    {
        $this->authorizeAgreement($agreement);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bank_transfer,raast,easypaisa,jazzcash,cheque'],
            'receipt_ref' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->agreementService->recordDownPayment(
                $agreement,
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['receipt_ref'] ?? null,
                $request->user(),
                $validated['notes'] ?? null
            );

            return back()->with('success', "Down payment of Rs. " . number_format($validated['amount']) . " recorded successfully.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Disburse product hardware and activate agreement.
     */
    public function disburse(InstallmentAgreement $agreement, Request $request): RedirectResponse
    {
        $this->authorizeAgreement($agreement);

        $validated = $request->validate([
            'handover_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->agreementService->disburseAndActivate(
                $agreement,
                $request->user(),
                $validated['handover_notes'] ?? null
            );

            return back()->with('success', "Merchandise disbursed to customer! Agreement {$agreement->account_number} is now active.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel agreement and release reserved hardware.
     */
    public function cancel(InstallmentAgreement $agreement, Request $request): RedirectResponse
    {
        $this->authorizeAgreement($agreement);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->agreementService->cancel(
                $agreement,
                $request->user(),
                $validated['cancellation_reason']
            );

            return back()->with('success', "Agreement {$agreement->account_number} cancelled and reserved merchandise released back to stock.");
        } catch (DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Printable legal agreement document.
     */
    public function print(InstallmentAgreement $agreement): View
    {
        $this->authorizeAgreement($agreement);

        $agreement->load([
            'company',
            'branch',
            'customer',
            'product',
            'serializedItem',
            'plan',
            'guarantors',
            'creator',
            'approvedBy',
        ]);

        return view('tenant.agreements.print', compact('agreement'));
    }

    protected function authorizeAgreement(InstallmentAgreement $agreement): void
    {
        $company = $this->tenantContext->getCompany();
        if ((int) $agreement->company_id !== (int) $company->id) {
            abort(404);
        }
    }
}
