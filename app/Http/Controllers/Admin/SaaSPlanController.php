<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaaSPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaaSPlanController extends Controller
{
    /**
     * Standard list of feature toggles available across tiers.
     */
    public const AVAILABLE_FEATURES = [
        'documents_pdf' => 'PDF Contracts & Thermal Receipts',
        'basic_reporting' => 'Basic Operational Reports',
        'sms_notifications' => 'Automated SMS Alerts',
        'whatsapp_notifications' => 'WhatsApp Cloud Dispatch',
        'inventory_transfers' => 'Inter-Branch Stock Transfers & Gate Passes',
        'credit_scoring' => 'Dynamic Risk & Credit Scoring Engine',
        'general_ledger' => 'Double-Entry General Ledger & Chart of Accounts',
        'advanced_analytics' => 'Portfolio Aging PAR 30/60/90 & Leaderboards',
        'custom_branding' => 'White-label Custom Logo & Receipt Templates',
        'priority_support' => '24/7 Priority SLA Technical Support',
    ];

    /**
     * Display all SaaS plans.
     */
    public function index(): View
    {
        $plans = SaaSPlan::withCount(['subscriptions' => function ($q) {
            $q->whereIn('status', ['active', 'trial']);
        }])->orderBy('sort_order')->orderBy('price_monthly')->get();

        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Show form for creating a new plan.
     */
    public function create(): View
    {
        $features = self::AVAILABLE_FEATURES;

        return view('admin.plans.create', compact('features'));
    }

    /**
     * Store new plan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_branches' => ['required', 'integer', 'min:1'],
            'max_active_agreements' => ['required', 'integer', 'min:1'],
            'max_monthly_transactions' => ['required', 'integer', 'min:1'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['features'] = $request->input('features', []);

        $plan = SaaSPlan::create($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' created successfully.");
    }

    /**
     * Show edit form for plan.
     */
    public function edit(SaaSPlan $plan): View
    {
        $features = self::AVAILABLE_FEATURES;

        return view('admin.plans.edit', compact('plan', 'features'));
    }

    /**
     * Update plan.
     */
    public function update(Request $request, SaaSPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_branches' => ['required', 'integer', 'min:1'],
            'max_active_agreements' => ['required', 'integer', 'min:1'],
            'max_monthly_transactions' => ['required', 'integer', 'min:1'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['features'] = $request->input('features', []);

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Toggle plan active status.
     */
    public function toggleActive(SaaSPlan $plan): RedirectResponse
    {
        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $statusText = $plan->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan '{$plan->name}' is now {$statusText}.");
    }
}
