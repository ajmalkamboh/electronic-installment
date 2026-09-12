<?php

namespace App\Services\Document;

use App\Models\Branch;
use App\Models\GeneratedDocument;
use App\Models\InstallmentAgreement;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    /**
     * Generate unique Document Number: DOC-{BRANCH}-{YYYYMM}-{SEQ}
     */
    public function generateDocumentNumber(Branch $branch, string $type): string
    {
        $typePrefix = match ($type) {
            'application_form' => 'APP',
            'verification_report' => 'VER',
            'credit_approval_sheet' => 'CRD',
            'installment_contract' => 'CTR',
            'guarantor_affidavit' => 'AFF',
            'delivery_note' => 'DLV',
            'payment_receipt' => 'RCT',
            'account_statement' => 'STM',
            'settlement_letter' => 'SET',
            'clearance_noc' => 'NOC',
            default => 'DOC',
        };

        $prefix = "{$typePrefix}-" . strtoupper($branch->code ?? 'BR') . '-' . date('Ym') . '-';
        $latest = GeneratedDocument::where('company_id', $branch->company_id)
            ->where('document_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('document_number');

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Record an immutable audit log entry of document generation or printing.
     */
    public function recordDocumentAudit(
        InstallmentAgreement $agreement,
        string $type,
        string $title,
        User $user,
        array $params = []
    ): GeneratedDocument {
        $branch = $agreement->branch;
        $docNumber = $this->generateDocumentNumber($branch, $type);

        return GeneratedDocument::create([
            'company_id' => $agreement->company_id,
            'branch_id' => $agreement->branch_id,
            'installment_agreement_id' => $agreement->id,
            'customer_id' => $agreement->customer_id,
            'document_number' => $docNumber,
            'document_type' => $type,
            'title' => $title,
            'generated_by_id' => $user->id,
            'parameters' => $params,
            'notes' => $params['notes'] ?? null,
        ]);
    }

    /**
     * 1. Compile payload for Customer Application Form.
     */
    public function prepareApplicationForm(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing(['customer', 'product', 'plan', 'branch', 'company', 'guarantors']);
        return [
            'type' => 'application_form',
            'title' => 'Customer Credit Application Dossier',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'plan' => $agreement->plan,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'guarantors' => $agreement->guarantors,
        ];
    }

    /**
     * 2. Compile payload for Customer Verification Report.
     */
    public function prepareVerificationReport(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing(['customer.verification', 'branch', 'company']);
        return [
            'type' => 'verification_report',
            'title' => 'Customer Physical & Residential Verification Report',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'verification' => $agreement->customer->verification,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
        ];
    }

    /**
     * 3. Compile payload for Credit Assessment & Approval Sheet.
     */
    public function prepareCreditAssessment(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing(['customer.creditProfile', 'creditAssessment.approval', 'branch', 'company']);
        return [
            'type' => 'credit_approval_sheet',
            'title' => 'Credit Assessment & Committee Approval Sheet',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'assessment' => $agreement->creditAssessment,
            'approval' => $agreement->creditAssessment?->approval,
            'creditProfile' => $agreement->customer->creditProfile,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
        ];
    }

    /**
     * 4. Compile payload for Legal Installment Contract.
     */
    public function prepareInstallmentContract(InstallmentAgreement $agreement, array $options = []): array
    {
        $agreement->loadMissing([
            'customer',
            'product',
            'serializedItem',
            'plan',
            'branch',
            'company',
            'guarantors',
            'schedules' => fn($q) => $q->orderBy('installment_number'),
        ]);

        return [
            'type' => 'installment_contract',
            'title' => 'Legal Installment Financing Agreement & Promissory Contract',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'serializedItem' => $agreement->serializedItem,
            'plan' => $agreement->plan,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'guarantors' => $agreement->guarantors,
            'schedules' => $agreement->schedules,
            'stampPaperMargin' => (bool) ($options['stamp_paper_margin'] ?? false),
        ];
    }

    /**
     * 5. Compile payload for Guarantor Undertaking & Affidavit.
     */
    public function prepareGuarantorAffidavit(InstallmentAgreement $agreement, ?int $guarantorId = null): array
    {
        $agreement->loadMissing(['customer', 'branch', 'company', 'guarantors']);

        $guarantor = null;
        if ($guarantorId) {
            $guarantor = $agreement->guarantors->firstWhere('id', $guarantorId);
        }
        if (! $guarantor) {
            $guarantor = $agreement->guarantors()->wherePivot('is_primary', true)->first()
                ?? $agreement->guarantors->first();
        }

        return [
            'type' => 'guarantor_affidavit',
            'title' => 'Guarantor Undertaking & Promissory Affidavit',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'guarantor' => $guarantor,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
        ];
    }

    /**
     * 6. Compile payload for Delivery & Handover Note.
     */
    public function prepareDeliveryNote(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing(['customer', 'product', 'serializedItem', 'branch', 'company', 'disbursedBy']);

        return [
            'type' => 'delivery_note',
            'title' => 'Merchandise Handover & Delivery Note',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'serializedItem' => $agreement->serializedItem,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'disbursedBy' => $agreement->disbursedBy,
        ];
    }

    /**
     * 7. Compile payload for Payment Receipt (58mm, 80mm, A4).
     */
    public function preparePaymentReceipt(Payment $payment, string $format = '80mm'): array
    {
        $payment->loadMissing(['customer', 'agreement.product', 'branch', 'company', 'cashier', 'allocations.schedule']);

        return [
            'type' => 'payment_receipt',
            'title' => 'Payment Receipt',
            'payment' => $payment,
            'agreement' => $payment->agreement,
            'customer' => $payment->customer,
            'branch' => $payment->branch,
            'company' => $payment->company,
            'format' => in_array($format, ['58mm', '80mm', 'a4']) ? $format : '80mm',
        ];
    }

    /**
     * 8. Compile payload for Customer Statement of Account.
     */
    public function prepareAccountStatement(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing([
            'customer',
            'product',
            'branch',
            'company',
            'schedules' => fn($q) => $q->orderBy('installment_number'),
            'payments' => fn($q) => $q->orderBy('payment_date')->orderBy('id'),
            'lateFeeWaivers',
        ]);

        $totalBilled = (float) $agreement->schedules->sum('total_amount');
        $totalPrincipalPaid = (float) $agreement->payments->sum('principal_paid');
        $totalMarkupPaid = (float) $agreement->payments->sum('markup_paid');
        $totalLateFeesPaid = (float) $agreement->payments->sum('late_fee_paid');
        $totalPaid = (float) $agreement->payments->sum('amount');
        $accruedLateFees = (float) $agreement->schedules->sum('late_fee_amount');
        $totalWaived = (float) $agreement->lateFeeWaivers->sum('waived_amount');
        $netOutstanding = (float) $agreement->remaining_balance + $accruedLateFees;

        return [
            'type' => 'account_statement',
            'title' => 'Customer Financial Statement of Account',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'schedules' => $agreement->schedules,
            'payments' => $agreement->payments,
            'metrics' => [
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'total_principal_paid' => $totalPrincipalPaid,
                'total_markup_paid' => $totalMarkupPaid,
                'total_late_fees_paid' => $totalLateFeesPaid,
                'accrued_late_fees' => $accruedLateFees,
                'total_waived' => $totalWaived,
                'net_outstanding' => $netOutstanding,
            ],
        ];
    }

    /**
     * 9. Compile payload for Early Settlement Calculation & Letter.
     */
    public function prepareSettlementLetter(InstallmentAgreement $agreement, float $rebatePct = 50.0): array
    {
        $agreement->loadMissing(['customer', 'product', 'serializedItem', 'branch', 'company', 'schedules']);

        $settlement = $agreement->calculateEarlySettlement($rebatePct);

        return [
            'type' => 'settlement_letter',
            'title' => 'Early Contract Settlement Calculation & Payoff Offer',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'serializedItem' => $agreement->serializedItem,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'settlement' => $settlement,
        ];
    }

    /**
     * 10. Compile payload for Clearance Certificate & No Objection Certificate (NOC).
     */
    public function prepareClearanceNoc(InstallmentAgreement $agreement): array
    {
        $agreement->loadMissing(['customer', 'product', 'serializedItem', 'branch', 'company', 'guarantors']);

        $totalRemaining = (float) $agreement->remaining_balance + (float) $agreement->schedules()->sum('late_fee_amount');
        if ($totalRemaining > 0.01 && $agreement->status !== 'completed') {
            throw new DomainException("Cannot issue Clearance Certificate & NOC: Contract #{$agreement->account_number} still has an outstanding balance of PKR " . number_format($totalRemaining, 2));
        }

        return [
            'type' => 'clearance_noc',
            'title' => 'Certificate of Total Contract Clearance & No Objection Certificate (NOC)',
            'agreement' => $agreement,
            'customer' => $agreement->customer,
            'product' => $agreement->product,
            'serializedItem' => $agreement->serializedItem,
            'branch' => $agreement->branch,
            'company' => $agreement->company,
            'guarantors' => $agreement->guarantors,
            'clearedAt' => $agreement->completed_at ?? Carbon::now(),
        ];
    }
}
