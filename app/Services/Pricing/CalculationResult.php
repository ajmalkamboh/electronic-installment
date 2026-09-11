<?php

namespace App\Services\Pricing;

class CalculationResult
{
    public function __construct(
        public float $cashPrice,
        public float $downPayment,
        public float $downPaymentPct,
        public float $financedPrincipal,
        public int $tenureMonths,
        public float $markupRatePct,
        public float $markupAmount,
        public float $totalFinanced,
        public float $totalPayable,
        public float $installmentAmount,
        public string $installmentFrequency = 'monthly',
        public float $profitMarginPct = 0.0,
        public string $calculationModel = 'flat_percentage',
        public bool $requiresManagerApproval = false,
        public ?string $approvalReason = null
    ) {}

    public function toArray(): array
    {
        return [
            'cash_price' => $this->cashPrice,
            'down_payment' => $this->downPayment,
            'down_payment_pct' => $this->downPaymentPct,
            'financed_principal' => $this->financedPrincipal,
            'tenure_months' => $this->tenureMonths,
            'markup_rate_pct' => $this->markupRatePct,
            'markup_amount' => $this->markupAmount,
            'total_financed' => $this->totalFinanced,
            'total_payable' => $this->totalPayable,
            'installment_amount' => $this->installmentAmount,
            'installment_frequency' => $this->installmentFrequency,
            'profit_margin_pct' => $this->profitMarginPct,
            'calculation_model' => $this->calculationModel,
            'requires_manager_approval' => $this->requiresManagerApproval,
            'approval_reason' => $this->approvalReason,
        ];
    }
}
