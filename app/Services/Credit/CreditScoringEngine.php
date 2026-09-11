<?php

namespace App\Services\Credit;

use App\Models\Customer;

class CreditScoringEngine
{
    public const MAX_REGULATORY_DTI_PERCENTAGE = 40.0;

    /**
     * Calculate Debt-to-Income (DTI) ratio percentage.
     *
     * DTI = ((Proposed Installment + Existing Monthly Debt Obligations) / Monthly Income) * 100
     */
    public function calculateDti(float $monthlyIncome, float $proposedInstallment, float $existingDebts = 0.0): float
    {
        if ($monthlyIncome <= 0) {
            return 100.0;
        }

        $totalObligations = $proposedInstallment + max(0.0, $existingDebts);
        $dti = ($totalObligations / $monthlyIncome) * 100.0;

        return round(min(100.0, max(0.0, $dti)), 2);
    }

    /**
     * Determine risk tier based on computed credit score and DTI ratio.
     */
    public function evaluateRiskTier(float $dti, int $score): string
    {
        if ($score >= 75 && $dti <= 30.0) {
            return 'low';
        }

        if ($score >= 55 && $dti <= self::MAX_REGULATORY_DTI_PERCENTAGE) {
            return 'medium';
        }

        if ($score >= 35 && $dti <= 50.0) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Compute a holistic credit score (0 to 100) based on financial underwriting criteria.
     */
    public function computeCreditScore(
        Customer $customer,
        float $monthlyIncome,
        float $proposedInstallment,
        float $existingDebts = 0.0
    ): int {
        // Hard stop: Blacklisted individual gets zero score
        if ($customer->isBlacklisted()) {
            return 0;
        }

        $dti = $this->calculateDti($monthlyIncome, $proposedInstallment, $existingDebts);

        // 1. Debt-to-Income Capacity Score (Max 35 points)
        $dtiScore = 0;
        if ($dti <= 20.0) {
            $dtiScore = 35;
        } elseif ($dti <= 30.0) {
            $dtiScore = 25;
        } elseif ($dti <= self::MAX_REGULATORY_DTI_PERCENTAGE) {
            $dtiScore = 15;
        } elseif ($dti <= 50.0) {
            $dtiScore = 5;
        } else {
            $dtiScore = 0;
        }

        // 2. Residence Stability & Tenure (Max 25 points)
        $residenceScore = 0;
        $isOwned = in_array(strtolower((string) $customer->residence_type), ['owned', 'family_owned']);
        $tenureYears = (int) ($customer->residence_tenure_years ?? 0);

        if ($isOwned && $tenureYears >= 5) {
            $residenceScore = 25;
        } elseif ($isOwned) {
            $residenceScore = 20;
        } elseif ($tenureYears >= 3) {
            $residenceScore = 15;
        } elseif ($tenureYears >= 1) {
            $residenceScore = 10;
        } else {
            $residenceScore = 5;
        }

        // 3. Guarantors & Verification Status (Max 25 points)
        $guarantorScore = 0;
        $verifiedGuarantorsCount = $customer->guarantors()->where('is_verified', true)->count();
        $totalGuarantorsCount = $customer->guarantors()->count();

        if ($verifiedGuarantorsCount >= 2) {
            $guarantorScore = 25;
        } elseif ($verifiedGuarantorsCount === 1 && $customer->isVerified()) {
            $guarantorScore = 20;
        } elseif ($verifiedGuarantorsCount === 1) {
            $guarantorScore = 15;
        } elseif ($totalGuarantorsCount > 0) {
            $guarantorScore = 10;
        } else {
            $guarantorScore = 0;
        }

        // 4. Repayment Track Record (Max 15 points)
        $repaymentScore = 10; // Default baseline for clean new applicants
        $profile = $customer->creditProfile;
        if ($profile) {
            $completedAgreements = (int) $profile->completed_agreements_count;
            if ($completedAgreements > 0) {
                $repaymentScore = min(15, 10 + ($completedAgreements * 2));
            }
        }

        // Calculate Subtotal
        $totalScore = $dtiScore + $residenceScore + $guarantorScore + $repaymentScore;

        // 5. Penalties for Historical Overdue Days Past Due (DPD)
        if ($profile && $profile->total_dpd_days > 0) {
            $dpdPenalty = min(40, (int) ($profile->total_dpd_days * 2));
            $totalScore -= $dpdPenalty;
        }

        return max(0, min(100, $totalScore));
    }

    /**
     * Compute algorithmically recommended credit limit.
     */
    public function calculateRecommendedLimit(float $monthlyIncome, float $dti, int $score): float
    {
        if ($score < 35 || $dti > 50.0) {
            return 0.00;
        }

        $disposableIncome = max(0.0, $monthlyIncome * (1.0 - ($dti / 100.0)));

        $multiplier = 2.0;
        if ($score >= 75) {
            $multiplier = 4.0;
        } elseif ($score >= 55) {
            $multiplier = 2.5;
        }

        $recommended = round($disposableIncome * $multiplier, -3); // Round to nearest thousand

        return max(30000.0, min(600000.0, $recommended));
    }
}
