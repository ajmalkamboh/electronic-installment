<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InstallmentAgreement;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    /**
     * Display the Platform Super Admin Command Center Dashboard.
     */
    public function index(): View
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $suspendedCompanies = Company::where('status', 'suspended')->count();
        $trialCompanies = Company::where('status', 'trial')->count();

        $totalUsers = User::whereNotNull('company_id')->count();
        $totalAgreements = InstallmentAgreement::whereIn('status', ['active', 'approved', 'disbursed', 'defaulted'])->count();

        // Calculate MRR from active subscriptions
        $mrr = Subscription::where('status', 'active')
            ->join('saas_plans', 'subscriptions.saas_plan_id', '=', 'saas_plans.id')
            ->sum('saas_plans.price_monthly');

        // Plan distribution
        $plans = SaaSPlan::withCount(['subscriptions' => function ($query) {
            $query->whereIn('status', ['active', 'trial']);
        }])->get();

        // Recent tenant registrations
        $recentCompanies = Company::with(['subscriptions' => function ($query) {
            $query->latest('id')->with('plan');
        }])->latest('id')->limit(6)->get();

        // Expiring subscriptions (within next 14 days)
        $expiringSubscriptions = Subscription::with(['company', 'plan'])
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($query) {
                $now = Carbon::now();
                $fourteenDays = Carbon::now()->addDays(14);
                $query->whereBetween('ends_at', [$now, $fourteenDays])
                    ->orWhereBetween('trial_ends_at', [$now, $fourteenDays]);
            })
            ->orderBy('ends_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalCompanies',
            'activeCompanies',
            'suspendedCompanies',
            'trialCompanies',
            'totalUsers',
            'totalAgreements',
            'mrr',
            'plans',
            'recentCompanies',
            'expiringSubscriptions'
        ));
    }
}
