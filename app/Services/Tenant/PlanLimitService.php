<?php

namespace App\Services\Tenant;

use App\Exceptions\FeatureDisabledException;
use App\Exceptions\QuotaExceededException;
use App\Models\Company;

class PlanLimitService
{
    /**
     * Check user quota for a company.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function checkUserQuota(Company $company): array
    {
        $plan = $company->currentPlan();

        if (! $plan) {
            return ['allowed' => true, 'current' => $company->activeUsersCount(), 'limit' => 999, 'message' => null];
        }

        $current = $company->activeUsersCount();
        $limit = $plan->max_users;
        $allowed = $current < $limit;

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $limit,
            'message' => $allowed ? null : "User limit reached: Your current {$plan->name} plan permits up to {$limit} user seats. Please upgrade to add more staff.",
        ];
    }

    /**
     * Check branch quota for a company.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function checkBranchQuota(Company $company): array
    {
        $plan = $company->currentPlan();

        if (! $plan) {
            return ['allowed' => true, 'current' => $company->activeBranchesCount(), 'limit' => 999, 'message' => null];
        }

        $current = $company->activeBranchesCount();
        $limit = $plan->max_branches;
        $allowed = $current < $limit;

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $limit,
            'message' => $allowed ? null : "Showroom branch limit reached: Your {$plan->name} plan permits up to {$limit} branch outlets. Please upgrade to add more branches.",
        ];
    }

    /**
     * Check active agreements quota for a company.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function checkAgreementQuota(Company $company): array
    {
        $plan = $company->currentPlan();

        if (! $plan) {
            return ['allowed' => true, 'current' => $company->activeAgreementsCount(), 'limit' => 9999, 'message' => null];
        }

        $current = $company->activeAgreementsCount();
        $limit = $plan->max_active_agreements;
        $allowed = $current < $limit;

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $limit,
            'message' => $allowed ? null : "Active agreement limit reached: Your {$plan->name} plan permits up to {$limit} active installment agreements. Please upgrade your subscription to write more contracts.",
        ];
    }

    /**
     * Check monthly transactions quota for a company.
     *
     * @return array{allowed: bool, current: int, limit: int, message: ?string}
     */
    public function checkTransactionQuota(Company $company): array
    {
        $plan = $company->currentPlan();

        if (! $plan) {
            return ['allowed' => true, 'current' => $company->currentMonthTransactionsCount(), 'limit' => 99999, 'message' => null];
        }

        $current = $company->currentMonthTransactionsCount();
        $limit = $plan->max_monthly_transactions;
        $allowed = $current < $limit;

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $limit,
            'message' => $allowed ? null : "Monthly transaction limit reached: Your {$plan->name} plan permits up to {$limit} transactions per calendar month. Please upgrade your plan.",
        ];
    }

    /**
     * Check if a specific feature is enabled for the company's current plan.
     */
    public function canAccessFeature(Company $company, string $featureKey): bool
    {
        $plan = $company->currentPlan();

        if (! $plan) {
            return true;
        }

        return $plan->hasFeature($featureKey);
    }

    /**
     * Assert user creation is within quota or throw exception.
     *
     * @throws QuotaExceededException
     */
    public function assertCanCreateUser(Company $company): void
    {
        $check = $this->checkUserQuota($company);

        if (! $check['allowed']) {
            throw new QuotaExceededException(
                message: $check['message'] ?? 'User quota limit exceeded.',
                quotaType: 'users',
                currentUsage: $check['current'],
                maxLimit: $check['limit']
            );
        }
    }

    /**
     * Assert branch creation is within quota or throw exception.
     *
     * @throws QuotaExceededException
     */
    public function assertCanCreateBranch(Company $company): void
    {
        $check = $this->checkBranchQuota($company);

        if (! $check['allowed']) {
            throw new QuotaExceededException(
                message: $check['message'] ?? 'Branch quota limit exceeded.',
                quotaType: 'branches',
                currentUsage: $check['current'],
                maxLimit: $check['limit']
            );
        }
    }

    /**
     * Assert agreement creation is within quota or throw exception.
     *
     * @throws QuotaExceededException
     */
    public function assertCanCreateAgreement(Company $company): void
    {
        $check = $this->checkAgreementQuota($company);

        if (! $check['allowed']) {
            throw new QuotaExceededException(
                message: $check['message'] ?? 'Active agreement quota limit exceeded.',
                quotaType: 'agreements',
                currentUsage: $check['current'],
                maxLimit: $check['limit']
            );
        }
    }

    /**
     * Assert transaction recording is within quota or throw exception.
     *
     * @throws QuotaExceededException
     */
    public function assertCanRecordTransaction(Company $company): void
    {
        $check = $this->checkTransactionQuota($company);

        if (! $check['allowed']) {
            throw new QuotaExceededException(
                message: $check['message'] ?? 'Monthly transaction quota limit exceeded.',
                quotaType: 'transactions',
                currentUsage: $check['current'],
                maxLimit: $check['limit']
            );
        }
    }

    /**
     * Assert feature is enabled or throw exception.
     *
     * @throws FeatureDisabledException
     */
    public function assertFeatureEnabled(Company $company, string $featureKey): void
    {
        if (! $this->canAccessFeature($company, $featureKey)) {
            $planName = $company->currentPlan()?->name ?? 'current';
            throw new FeatureDisabledException(
                message: "Feature '{$featureKey}' is not enabled in your {$planName} plan. Please upgrade to access this functionality.",
                featureKey: $featureKey
            );
        }
    }
}
