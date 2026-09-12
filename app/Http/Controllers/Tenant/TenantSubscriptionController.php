<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SaaSPlan;
use App\Services\Tenant\SubscriptionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display the tenant's current plan, quotas, and upgrade options.
     */
    public function index(): View
    {
        $company = $this->tenantContext->getCompany() ?? auth()->user()->company;

        if (! $company) {
            abort(404, 'No tenant company bound to this session.');
        }

        $subscription = $company->currentSubscription();
        $currentPlan = $company->currentPlan();
        $quotaUsage = $company->getQuotaUsage();

        $plans = SaaSPlan::active()->get();

        $featuresCatalog = [
            'documents_pdf' => 'PDF Contracts & Thermal Receipts',
            'basic_reporting' => 'Basic Operational Reporting',
            'sms_notifications' => 'Automated SMS Alerts',
            'whatsapp_notifications' => 'WhatsApp Cloud Dispatch',
            'inventory_transfers' => 'Inter-Branch Stock Transfers & Gate Passes',
            'credit_scoring' => 'Credit Scoring Engine',
            'general_ledger' => 'Double-Entry General Ledger',
            'advanced_analytics' => 'Executive Analytics & Aging PAR',
            'custom_branding' => 'White-label Custom Branding',
            'priority_support' => 'Priority Support SLA',
        ];

        return view('tenant.subscription.index', compact(
            'company',
            'subscription',
            'currentPlan',
            'quotaUsage',
            'plans',
            'featuresCatalog'
        ));
    }

    /**
     * Request or execute plan upgrade/downgrade.
     */
    public function upgrade(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $company = $this->tenantContext->getCompany() ?? auth()->user()->company;

        if (! $company) {
            abort(404, 'No tenant company found.');
        }

        $newPlan = SaaSPlan::findOrFail($validated['plan_id']);

        $this->subscriptionService->changePlan($company, $newPlan, $validated['billing_cycle']);

        return redirect()->route('subscription.index')
            ->with('success', "Plan successfully upgraded to {$newPlan->name} ({$validated['billing_cycle']})!");
    }
}
