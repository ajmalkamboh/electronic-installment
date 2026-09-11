<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    /**
     * Show the form for editing company settings.
     */
    public function edit(TenantContext $tenantContext): View
    {
        $user = Auth::user();

        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can access company settings.');
        }

        $company = $tenantContext->getCompany();

        $stats = [
            'branches_count' => Branch::where('company_id', $company->id)->count(),
            'users_count' => User::where('company_id', $company->id)->count(),
        ];

        return view('tenant.company.settings', [
            'company' => $company,
            'stats' => $stats,
        ]);
    }

    /**
     * Update the company profile and settings.
     */
    public function update(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isCompanyAdmin()) {
            abort(403, 'Only company administrators can modify company settings.');
        }

        $company = $tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'legal_name' => ['nullable', 'string', 'max:191'],
            'ntn_strn' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'receipt_header' => ['nullable', 'string'],
            'receipt_footer' => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
        ]);

        $company->update($validated);

        return back()->with('success', 'Company profile and settings updated successfully.');
    }
}
