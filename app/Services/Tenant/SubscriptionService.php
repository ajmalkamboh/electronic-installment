<?php

namespace App\Services\Tenant;

use App\Models\Company;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Subscribe a tenant company to a SaaS plan.
     */
    public function subscribe(Company $company, SaaSPlan $plan, array $options = []): Subscription
    {
        return DB::transaction(function () use ($company, $plan, $options) {
            $isTrial = $options['is_trial'] ?? false;
            $billingCycle = $options['billing_cycle'] ?? 'monthly';
            $startsAt = isset($options['starts_at']) ? Carbon::parse($options['starts_at']) : Carbon::now();

            $endsAt = null;
            $trialEndsAt = null;

            if ($isTrial) {
                $trialDays = $options['trial_days'] ?? $plan->trial_days ?? 14;
                $trialEndsAt = (clone $startsAt)->addDays($trialDays);
                $status = 'trial';
                $amountPaid = 0.00;
            } else {
                $status = 'active';
                if ($billingCycle === 'yearly') {
                    $endsAt = (clone $startsAt)->addYear();
                    $amountPaid = $options['amount_paid'] ?? $plan->price_yearly;
                } else {
                    $endsAt = (clone $startsAt)->addMonth();
                    $amountPaid = $options['amount_paid'] ?? $plan->price_monthly;
                }
            }

            $subscription = Subscription::create([
                'company_id' => $company->id,
                'saas_plan_id' => $plan->id,
                'status' => $status,
                'billing_cycle' => $billingCycle,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
                'grace_days' => $options['grace_days'] ?? 7,
                'amount_paid' => $amountPaid,
                'payment_method' => $options['payment_method'] ?? ($isTrial ? null : 'bank_transfer'),
                'notes' => $options['notes'] ?? ($isTrial ? 'Free trial started' : 'Direct plan subscription activated'),
            ]);

            $company->status = $isTrial ? 'trial' : 'active';
            $company->save();

            return $subscription;
        });
    }

    /**
     * Change a tenant's subscription to a new plan tier.
     */
    public function changePlan(Company $company, SaaSPlan $newPlan, string $cycle = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($company, $newPlan, $cycle) {
            $currentSub = $company->currentSubscription();

            $startsAt = Carbon::now();
            $endsAt = $cycle === 'yearly' ? (clone $startsAt)->addYear() : (clone $startsAt)->addMonth();
            $amount = $cycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;

            if ($currentSub && $currentSub->isActive()) {
                $currentSub->status = 'cancelled';
                $currentSub->cancelled_at = Carbon::now();
                $currentSub->notes = ($currentSub->notes ? $currentSub->notes."\n" : '')."Upgraded to {$newPlan->name} plan on ".Carbon::now()->toDateTimeString();
                $currentSub->save();
            }

            $newSubscription = Subscription::create([
                'company_id' => $company->id,
                'saas_plan_id' => $newPlan->id,
                'status' => 'active',
                'billing_cycle' => $cycle,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => null,
                'grace_days' => 7,
                'amount_paid' => $amount,
                'payment_method' => 'manual_invoice',
                'notes' => "Plan changed to {$newPlan->name} ({$cycle})",
            ]);

            $company->status = 'active';
            $company->save();

            return $newSubscription;
        });
    }

    /**
     * Renew an existing subscription.
     */
    public function renewSubscription(
        Subscription $subscription,
        ?int $durationDays = null,
        ?float $amount = null,
        ?string $method = 'bank_transfer'
    ): Subscription {
        return DB::transaction(function () use ($subscription, $durationDays, $amount, $method) {
            $now = Carbon::now();
            $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture()) ? $subscription->ends_at : $now;

            if ($durationDays !== null) {
                $newEndsAt = (clone $baseDate)->addDays($durationDays);
            } elseif ($subscription->billing_cycle === 'yearly') {
                $newEndsAt = (clone $baseDate)->addYear();
            } else {
                $newEndsAt = (clone $baseDate)->addMonth();
            }

            $subscription->status = 'active';
            $subscription->ends_at = $newEndsAt;
            $subscription->trial_ends_at = null;
            $subscription->amount_paid = $amount ?? ($subscription->billing_cycle === 'yearly' ? $subscription->plan->price_yearly : $subscription->plan->price_monthly);
            $subscription->payment_method = $method;
            $subscription->notes = ($subscription->notes ? $subscription->notes."\n" : '')."Renewed on {$now->toDateTimeString()} until {$newEndsAt->toDateString()}";
            $subscription->save();

            $subscription->company->status = 'active';
            $subscription->company->save();

            return $subscription;
        });
    }

    /**
     * Cancel a company's subscription.
     */
    public function cancelSubscription(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->status = 'cancelled';
        $subscription->cancelled_at = Carbon::now();
        $subscription->notes = ($subscription->notes ? $subscription->notes."\n" : '').'Cancelled: '.($reason ?? 'No reason provided');
        $subscription->save();

        return $subscription;
    }

    /**
     * Manually suspend a company.
     */
    public function suspendCompany(Company $company, ?string $reason = null): void
    {
        DB::transaction(function () use ($company, $reason) {
            $company->status = 'suspended';
            $company->save();

            $sub = $company->currentSubscription();
            if ($sub) {
                $sub->status = 'suspended';
                $sub->notes = ($sub->notes ? $sub->notes."\n" : '').'Suspended by admin: '.($reason ?? 'Manual suspension');
                $sub->save();
            }
        });
    }

    /**
     * Reactivate a suspended company.
     */
    public function reactivateCompany(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $company->status = 'active';
            $company->save();

            $sub = $company->currentSubscription();
            if ($sub && $sub->status === 'suspended') {
                $sub->status = 'active';
                // If it was expired, ensure it has at least 30 days
                if (! $sub->ends_at || $sub->ends_at->isPast()) {
                    $sub->ends_at = Carbon::now()->addMonth();
                }
                $sub->save();
            }
        });
    }

    /**
     * Evaluate current subscription status of a company based on dates and grace periods.
     */
    public function evaluateCompanyStatus(Company $company): string
    {
        $sub = $company->subscriptions()->latest('id')->first();

        if (! $sub) {
            return 'suspended';
        }

        $now = Carbon::now();

        // 1. If currently in trial
        if ($sub->status === 'trial') {
            if ($sub->trial_ends_at && $now->greaterThan($sub->trial_ends_at)) {
                $graceEnd = (clone $sub->trial_ends_at)->addDays($sub->grace_days ?? 7);
                if ($now->greaterThan($graceEnd)) {
                    return 'suspended';
                }

                return 'past_due';
            }

            return 'trial';
        }

        // 2. If active or past_due
        if ($sub->ends_at) {
            if ($now->greaterThan($sub->ends_at)) {
                $graceEnd = (clone $sub->ends_at)->addDays($sub->grace_days ?? 7);
                if ($now->greaterThan($graceEnd)) {
                    return 'suspended';
                }

                return 'past_due';
            }

            return 'active';
        }

        return $sub->status;
    }

    /**
     * Execute batch evaluation of all company subscriptions.
     * Auto-transitions expired/grace-exceeded companies into suspended.
     *
     * @return array{checked: int, updated: int, suspended: int, past_due: int}
     */
    public function processScheduledLifecycleChecks(): array
    {
        $checked = 0;
        $updated = 0;
        $suspended = 0;
        $pastDue = 0;

        $companies = Company::with(['subscriptions' => function ($query) {
            $query->latest('id');
        }])->get();

        foreach ($companies as $company) {
            $checked++;
            $sub = $company->currentSubscription();
            if (! $sub) {
                continue;
            }

            $newStatus = $this->evaluateCompanyStatus($company);

            if ($newStatus !== $sub->status || ($newStatus === 'suspended' && $company->status !== 'suspended')) {
                $sub->status = $newStatus;
                $sub->save();

                if ($newStatus === 'suspended') {
                    $company->status = 'suspended';
                    $company->save();
                    $suspended++;
                } elseif ($newStatus === 'past_due') {
                    $pastDue++;
                }

                $updated++;
            }
        }

        return [
            'checked' => $checked,
            'updated' => $updated,
            'suspended' => $suspended,
            'past_due' => $pastDue,
        ];
    }
}
