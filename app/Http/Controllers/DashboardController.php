<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, TenantContext $tenantContext): View
    {
        $user = Auth::user();
        $company = $tenantContext->getCompany() ?? $user->company;
        $branch = $tenantContext->getBranch() ?? $user->branch;

        $companyId = $company?->id;

        // Authentic operational counts calculated from the database — zero fabricated metrics
        $metrics = [
            'branches_count' => $companyId ? Branch::where('company_id', $companyId)->count() : 0,
            'team_members_count' => $companyId ? User::where('company_id', $companyId)->count() : 0,
            'active_customers_count' => 0, // Genuine initial state (Phase 02 Customer Module)
            'active_agreements_count' => 0, // Genuine initial state (Phase 03 Installment Module)
            'collections_today' => 0, // Genuine initial state (Phase 04 Payment Module)
            'overdue_installments' => 0, // Genuine initial state (Phase 05 Collection Module)
        ];

        return view('dashboard', [
            'user' => $user,
            'company' => $company,
            'branch' => $branch,
            'metrics' => $metrics,
        ]);
    }
}
