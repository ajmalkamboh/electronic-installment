<?php

namespace App\Services\Pricing;

use App\Models\InstallmentPlan;
use App\Models\Product;

class PricingEngine
{
    /**
     * Calculate financial parameters for a product and plan.
     */
    public function calculatePlan(
        Product $product,
        InstallmentPlan $plan,
        ?float $customDownPayment = null,
        ?float $customMarkupRate = null
    ): CalculationResult {
        $cashPrice = (float) $product->base_cash_price;
        $tenureMonths = (int) $plan->tenure_months;
        $model = $plan->markup_calculation_model;
        $markupRate = $customMarkupRate !== null ? (float) $customMarkupRate : (float) $plan->default_markup_rate_pct;

        // Effective minimum down payment is the higher of product or plan policy
        $minDownPaymentPct = max((float) $product->min_down_payment_pct, (float) $plan->min_down_payment_pct);

        $defaultDownPayment = round($cashPrice * ($minDownPaymentPct / 100.0), -2);
        $downPayment = $customDownPayment !== null ? max(0.0, (float) $customDownPayment) : $defaultDownPayment;

        $fixedAmount = $plan->fixed_markup_amount ? (float) $plan->fixed_markup_amount : null;

        return $this->calculateCustom(
            $cashPrice,
            $tenureMonths,
            $model,
            $markupRate,
            $downPayment,
            $fixedAmount,
            $plan->installment_frequency,
            $minDownPaymentPct
        );
    }

    /**
     * Compute custom pricing parameters based on raw numerical inputs.
     */
    public function calculateCustom(
        float $cashPrice,
        int $tenureMonths,
        string $model,
        float $markupRate,
        float $downPayment,
        ?float $fixedAmount = null,
        string $frequency = 'monthly',
        float $minDownPaymentPct = 20.0
    ): CalculationResult {
        $tenureMonths = max(1, $tenureMonths);
        $cashPrice = max(0.0, $cashPrice);
        $downPayment = max(0.0, min($cashPrice, $downPayment));

        $downPaymentPct = $cashPrice > 0 ? round(($downPayment / $cashPrice) * 100.0, 2) : 0.0;
        $financedPrincipal = max(0.0, $cashPrice - $downPayment);

        // Check if down payment deviation requires managerial sign-off
        $requiresApproval = false;
        $approvalReason = null;

        $minAllowedDownPayment = round($cashPrice * ($minDownPaymentPct / 100.0), 2);
        if ($downPayment < $minAllowedDownPayment) {
            $requiresApproval = true;
            $approvalReason = "Agreed down payment (Rs. " . number_format($downPayment) . ") is below the minimum {$minDownPaymentPct}% threshold (Rs. " . number_format($minAllowedDownPayment) . ").";
        }

        // Calculate Markup based on selected mathematical model
        $markupAmount = 0.0;

        switch ($model) {
            case 'fixed_amount':
                $markupAmount = max(0.0, (float) ($fixedAmount ?? 0.0));
                break;

            case 'reducing_balance':
                if ($markupRate > 0 && $financedPrincipal > 0) {
                    $monthlyRate = ($markupRate / 100.0) / 12.0;
                    $factor = pow(1.0 + $monthlyRate, $tenureMonths);
                    $monthlyEmi = $financedPrincipal * ($monthlyRate * $factor) / ($factor - 1.0);
                    $totalRepayable = $monthlyEmi * $tenureMonths;
                    $markupAmount = max(0.0, round($totalRepayable - $financedPrincipal, 2));
                }
                break;

            case 'flat_percentage':
            default:
                // Flat Annual Rate applied over tenure fraction: P * (Rate / 100) * (Tenure / 12)
                $annualFraction = $tenureMonths / 12.0;
                $markupAmount = round($financedPrincipal * ($markupRate / 100.0) * $annualFraction, 2);
                break;
        }

        $totalFinanced = $financedPrincipal + $markupAmount;
        $totalPayable = $downPayment + $totalFinanced;
        $installmentAmount = round($totalFinanced / $tenureMonths, 2);

        $profitMarginPct = $cashPrice > 0 ? round(($markupAmount / $cashPrice) * 100.0, 2) : 0.0;

        return new CalculationResult(
            cashPrice: $cashPrice,
            downPayment: $downPayment,
            downPaymentPct: $downPaymentPct,
            financedPrincipal: $financedPrincipal,
            tenureMonths: $tenureMonths,
            markupRatePct: $markupRate,
            markupAmount: $markupAmount,
            totalFinanced: $totalFinanced,
            totalPayable: $totalPayable,
            installmentAmount: $installmentAmount,
            installmentFrequency: $frequency,
            profitMarginPct: $profitMarginPct,
            calculationModel: $model,
            requiresManagerApproval: $requiresApproval,
            approvalReason: $approvalReason
        );
    }

    /**
     * Compare side-by-side financing terms across standard tenure tiers (3, 6, 12, 18, 24 months).
     *
     * @param  array<int>  $tenures
     * @return array<int, CalculationResult>
     */
    public function compareTenures(
        Product $product,
        ?float $customDownPayment = null,
        array $tenures = [3, 6, 12, 18, 24]
    ): array {
        $results = [];

        // Industry standard standard markup tiers for electronic showrooms in Pakistan
        $standardAnnualRates = [
            3 => 15.00,  // Short promo tenure
            6 => 20.00,  // Semi-annual
            9 => 22.50,  // 9-month
            12 => 25.00, // Standard 1-year
            18 => 28.00, // Extended
            24 => 30.00, // 2-year
        ];

        $minDownPaymentPct = (float) $product->min_down_payment_pct;
        $defaultDownPayment = round(((float) $product->base_cash_price) * ($minDownPaymentPct / 100.0), -2);
        $downPayment = $customDownPayment !== null ? (float) $customDownPayment : $defaultDownPayment;

        foreach ($tenures as $tenure) {
            $rate = $standardAnnualRates[$tenure] ?? 25.00;
            $results[$tenure] = $this->calculateCustom(
                cashPrice: (float) $product->base_cash_price,
                tenureMonths: $tenure,
                model: 'flat_percentage',
                markupRate: $rate,
                downPayment: $downPayment,
                frequency: 'monthly',
                minDownPaymentPct: $minDownPaymentPct
            );
        }

        return $results;
    }
}
