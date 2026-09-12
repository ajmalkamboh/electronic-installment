<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Tenant\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TenantManagementController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * List all tenant companies.
     */
    public function index(Request $request): View
    {
        $query = Company::with(['subscriptions' => function ($q) {
            $q->latest('id')->with('plan');
        }])->withCount(['branches', 'users', 'agreements']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('plan_id')) {
            $planId = $request->get('plan_id');
            $query->whereHas('subscriptions', function ($q) use ($planId) {
                $q->where('saas_plan_id', $planId)->whereIn('status', ['active', 'trial']);
            });
        }

        $companies = $query->latest('id')->paginate(15)->withQueryString();
        $plans = SaaSPlan::active()->get();

        return view('admin.companies.index', compact('companies', 'plans'));
    }

    /**
     * Show onboarding form for a new tenant company.
     */
    public function create(): View
    {
        $plans = SaaSPlan::active()->get();

        return view('admin.companies.create', compact('plans'));
    }

    /**
     * Store new tenant company with default branch, company admin, and subscription.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:191'],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', 'unique:companies,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'plan_id' => ['required', 'exists:saas_plans,id'],
            'is_trial' => ['nullable', 'boolean'],
            'admin_name' => ['required', 'string', 'max:191'],
            'admin_email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $company = DB::transaction(function () use ($validated, $request) {
            // 1. Create Company
            $company = Company::create([
                'name' => $validated['company_name'],
                'legal_name' => $validated['legal_name'] ?? $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'city' => $validated['city'],
                'address' => $validated['address'],
                'currency' => 'PKR',
                'status' => $request->boolean('is_trial') ? 'trial' : 'active',
            ]);

            // 2. Create Default HQ Branch
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => $validated['city'].' Main Branch',
                'code' => strtoupper(substr($validated['city'], 0, 3)).'-01',
                'city' => $validated['city'],
                'address' => $validated['address'] ?? ($validated['city'].' City Center'),
                'is_main' => true,
                'status' => 'active',
            ]);

            // 3. Create Company Admin User
            $adminRole = Role::where('name', 'company_admin')->first();

            $admin = User::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => 'company_admin',
                'role_id' => $adminRole?->id,
                'status' => 'active',
            ]);

            if ($adminRole) {
                $admin->roles()->sync([$adminRole->id]);
            }

            // 4. Subscribe to SaaS Plan
            $plan = SaaSPlan::findOrFail($validated['plan_id']);
            $this->subscriptionService->subscribe($company, $plan, [
                'is_trial' => $request->boolean('is_trial'),
                'billing_cycle' => 'monthly',
                'notes' => 'Tenant registered via Super Admin command center.',
            ]);

            return $company;
        });

        return redirect()->route('admin.companies.show', $company)
            ->with('success', "Tenant '{$company->name}' onboarded successfully with plan subscription!");
    }

    /**
     * Show tenant dossier and subscription management console.
     */
    public function show(Company $company): View
    {
        $company->load([
            'branches' => fn ($q) => $q->latest('is_main'),
            'users' => fn ($q) => $q->latest('id')->limit(10),
            'subscriptions' => fn ($q) => $q->latest('id')->with('plan'),
        ]);

        $currentSubscription = $company->currentSubscription();
        $plans = SaaSPlan::active()->get();
        $quotaUsage = $company->getQuotaUsage();

        return view('admin.companies.show', compact('company', 'currentSubscription', 'plans', 'quotaUsage'));
    }

    /**
     * Change tenant subscription plan.
     */
    public function changePlan(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $newPlan = SaaSPlan::findOrFail($validated['plan_id']);
        $this->subscriptionService->changePlan($company, $newPlan, $validated['billing_cycle']);

        return redirect()->route('admin.companies.show', $company)
            ->with('success', "Plan upgraded to {$newPlan->name} ({$validated['billing_cycle']}) successfully.");
    }

    /**
     * Extend subscription or trial end date.
     */
    public function extendSubscription(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string'],
        ]);

        $sub = $company->currentSubscription();
        if (! $sub) {
            return redirect()->back()->with('error', 'No subscription found for this company.');
        }

        $now = Carbon::now();
        if ($sub->isTrial()) {
            $base = ($sub->trial_ends_at && $sub->trial_ends_at->isFuture()) ? $sub->trial_ends_at : $now;
            $sub->trial_ends_at = (clone $base)->addDays((int) $validated['days']);
        } else {
            $base = ($sub->ends_at && $sub->ends_at->isFuture()) ? $sub->ends_at : $now;
            $sub->ends_at = (clone $base)->addDays((int) $validated['days']);
        }

        $sub->status = $sub->isTrial() ? 'trial' : 'active';
        $sub->notes = ($sub->notes ? $sub->notes."\n" : '')."Extended by {$validated['days']} days on {$now->toDateTimeString()}. Note: ".($validated['notes'] ?? 'None');
        $sub->save();

        $company->status = $sub->status;
        $company->save();

        return redirect()->route('admin.companies.show', $company)
            ->with('success', "Subscription extended by {$validated['days']} days.");
    }

    /**
     * Toggle company status between active and suspended.
     */
    public function toggleStatus(Request $request, Company $company): RedirectResponse
    {
        if ($company->status === 'suspended') {
            $this->subscriptionService->reactivateCompany($company);
            $message = "Company '{$company->name}' reactivated and restored to service.";
        } else {
            $reason = $request->input('reason', 'Administrative lock applied');
            $this->subscriptionService->suspendCompany($company, $reason);
            $message = "Company '{$company->name}' has been suspended.";
        }

        return redirect()->route('admin.companies.show', $company)->with('success', $message);
    }
}
