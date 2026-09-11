<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InstallmentAgreement;
use App\Models\Payment;
use App\Services\Payment\PaymentEngine;
use App\Services\Tenant\TenantContext;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PaymentEngine $paymentEngine
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = Payment::where('company_id', $company->id)
            ->with(['agreement.customer', 'agreement.product', 'branch', 'cashier'])
            ->latest('payment_date')
            ->latest('id');

        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('agreement', function ($aq) use ($search) {
                        $aq->where('account_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('mobile_primary', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->paginate(20)->withQueryString();
        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->get();

        $totalCollected = Payment::where('company_id', $company->id)->sum('amount');
        $todayCollected = Payment::where('company_id', $company->id)->whereDate('payment_date', today())->sum('amount');
        $paymentCount = Payment::where('company_id', $company->id)->count();

        return view('tenant.payments.index', compact(
            'payments',
            'branches',
            'totalCollected',
            'todayCollected',
            'paymentCount'
        ));
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $selectedAgreement = null;
        if ($agreementId = $request->input('agreement_id')) {
            $selectedAgreement = InstallmentAgreement::where('company_id', $company->id)
                ->with(['customer', 'product', 'schedules'])
                ->find($agreementId);
        }

        $activeAgreements = InstallmentAgreement::where('company_id', $company->id)
            ->where('status', 'active')
            ->with(['customer', 'product'])
            ->latest()
            ->get();

        return view('tenant.payments.create', compact('activeAgreements', 'selectedAgreement'));
    }

    /**
     * Store a newly recorded payment in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'installment_agreement_id' => ['required', 'exists:installment_agreements,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bank_transfer,raast,easypaisa,jazzcash,cheque'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $agreement = InstallmentAgreement::where('company_id', $company->id)->findOrFail($validated['installment_agreement_id']);

        try {
            $payment = $this->paymentEngine->recordPayment(
                $agreement,
                (float) $validated['amount'],
                $validated['payment_method'],
                $validated['reference_number'] ?? null,
                $request->user(),
                $validated['notes'] ?? null
            );

            return redirect()->route('payments.show', $payment->id)
                ->with('success', "Payment {$payment->payment_number} of Rs. " . number_format($payment->amount) . " successfully processed and allocated.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified payment receipt dossier.
     */
    public function show(Payment $payment): View
    {
        $this->authorizePayment($payment);

        $payment->load([
            'agreement.customer',
            'agreement.product',
            'branch',
            'cashier',
            'allocations.schedule',
        ]);

        return view('tenant.payments.receipt', compact('payment'));
    }

    /**
     * Printable receipt view.
     */
    public function print(Payment $payment): View
    {
        $this->authorizePayment($payment);

        $payment->load([
            'company',
            'branch',
            'customer',
            'agreement.product',
            'cashier',
            'allocations.schedule',
        ]);

        return view('tenant.payments.print', compact('payment'));
    }

    protected function authorizePayment(Payment $payment): void
    {
        $company = $this->tenantContext->getCompany();
        if ((int) $payment->company_id !== (int) $company->id) {
            abort(404);
        }
    }
}
