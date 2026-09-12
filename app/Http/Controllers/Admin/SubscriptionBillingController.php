<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Services\Tenant\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * List all platform subscriptions and billing histories.
     */
    public function index(Request $request): View
    {
        $query = Subscription::with(['company', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('saas_plan_id', $request->get('plan_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('company', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest('id')->paginate(20)->withQueryString();
        $plans = SaaSPlan::all();

        return view('admin.subscriptions.index', compact('subscriptions', 'plans'));
    }

    /**
     * Record a manual payment / renewal for a subscription.
     */
    public function recordPayment(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'notes' => ['nullable', 'string'],
        ]);

        $days = $validated['months'] * 30;

        $this->subscriptionService->renewSubscription(
            $subscription,
            $days,
            (float) $validated['amount_paid'],
            $validated['payment_method']
        );

        return redirect()->back()->with('success', 'Payment of PKR '.number_format($validated['amount_paid'])." recorded and subscription renewed for {$validated['months']} months.");
    }
}
