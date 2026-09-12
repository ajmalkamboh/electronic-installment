<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GeneratedDocument;
use App\Models\InstallmentAgreement;
use App\Models\Payment;
use App\Services\Document\DocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Central Document Hub.
     */
    public function hub(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = $request->get('branch_id');
        $search = $request->get('search');

        $agreementsQuery = InstallmentAgreement::where('company_id', $companyId)
            ->with(['customer', 'product', 'branch', 'plan'])
            ->latest();

        if ($branchId) {
            $agreementsQuery->where('branch_id', $branchId);
        }

        if ($search) {
            $agreementsQuery->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('cnic', 'like', "%{$search}%")
                            ->orWhere('mobile_primary', 'like', "%{$search}%");
                    });
            });
        }

        $agreements = $agreementsQuery->paginate(12)->withQueryString();

        // Recent printed documents audit log
        $recentDocs = GeneratedDocument::where('company_id', $companyId)
            ->with(['customer', 'agreement', 'generatedBy', 'branch'])
            ->latest()
            ->limit(10)
            ->get();

        $branches = Branch::where('company_id', $companyId)->get();

        return view('tenant.documents.hub', compact('agreements', 'recentDocs', 'branches', 'branchId', 'search'));
    }

    /**
     * Dedicated Documents & Print Center for an Agreement.
     */
    public function agreementCenter(InstallmentAgreement $agreement)
    {
        $this->authorizeAccess($agreement);

        $agreement->loadMissing([
            'customer.creditProfile',
            'customer.verification',
            'product',
            'serializedItem',
            'plan',
            'branch',
            'company',
            'guarantors',
            'schedules',
            'payments',
            'generatedDocuments.generatedBy',
        ]);

        $settlementPreview = $agreement->calculateEarlySettlement(50.0);
        $isCleared = (float) $agreement->remaining_balance <= 0.01 && (float) $agreement->schedules()->sum('late_fee_amount') <= 0.01;

        return view('tenant.documents.agreement_center', compact('agreement', 'settlementPreview', 'isCleared'));
    }

    /**
     * Unified Print Controller rendering any of the standard legal documents.
     */
    public function print(string $type, InstallmentAgreement $agreement, Request $request)
    {
        $this->authorizeAccess($agreement);

        $options = $request->all();

        $data = match ($type) {
            'application-form' => $this->documentService->prepareApplicationForm($agreement),
            'verification-report' => $this->documentService->prepareVerificationReport($agreement),
            'credit-assessment' => $this->documentService->prepareCreditAssessment($agreement),
            'installment-contract' => $this->documentService->prepareInstallmentContract($agreement, $options),
            'guarantor-affidavit' => $this->documentService->prepareGuarantorAffidavit($agreement, $request->get('guarantor_id')),
            'delivery-note' => $this->documentService->prepareDeliveryNote($agreement),
            'account-statement' => $this->documentService->prepareAccountStatement($agreement),
            'settlement-letter' => $this->documentService->prepareSettlementLetter($agreement, (float) ($request->get('rebate_pct') ?? 50.0)),
            'clearance-noc' => $this->documentService->prepareClearanceNoc($agreement),
            default => abort(404, "Invalid document type: {$type}"),
        };

        // Record audit
        $docAuditType = str_replace('-', '_', $type);
        if ($docAuditType === 'installment_contract') {
            $docAuditType = 'installment_contract';
        }

        $this->documentService->recordDocumentAudit(
            $agreement,
            $data['type'],
            $data['title'],
            Auth::user(),
            $options
        );

        $viewName = "tenant.documents.templates." . str_replace('-', '_', $type);

        return view($viewName, $data);
    }

    /**
     * Specialized Thermal / A4 Payment Receipt Print Controller.
     */
    public function printReceipt(Payment $payment, Request $request)
    {
        if ($payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }

        $format = $request->get('format', '80mm');
        $data = $this->documentService->preparePaymentReceipt($payment, $format);

        if ($payment->agreement) {
            $this->documentService->recordDocumentAudit(
                $payment->agreement,
                'payment_receipt',
                "Payment Receipt #{$payment->payment_number} ({$format})",
                Auth::user(),
                ['format' => $format, 'payment_id' => $payment->id]
            );
        }

        return view('tenant.documents.templates.thermal_receipt', $data);
    }

    /**
     * Mark agreement completed and issue official clearance NOC.
     */
    public function issueNoc(Request $request, InstallmentAgreement $agreement)
    {
        $this->authorizeAccess($agreement);

        $totalRemaining = (float) $agreement->remaining_balance + (float) $agreement->schedules()->sum('late_fee_amount');
        if ($totalRemaining > 0.01) {
            return redirect()->back()->with('error', "Cannot issue NOC: Agreement has an unpaid balance of PKR " . number_format($totalRemaining, 2));
        }

        if ($agreement->status !== 'completed') {
            $agreement->status = 'completed';
            $agreement->completed_at = Carbon::now();
            $agreement->save();
        }

        return redirect()->route('documents.print', ['type' => 'clearance-noc', 'agreement' => $agreement]);
    }

    protected function authorizeAccess(InstallmentAgreement $agreement): void
    {
        if ($agreement->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }
    }
}
