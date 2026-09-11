<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    /**
     * Display a listing of branches for the active company.
     */
    public function index(TenantContext $tenantContext): View
    {
        $company = $tenantContext->getCompany();

        $branches = Branch::where('company_id', $company->id)
            ->withCount('users')
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        return view('tenant.branches.index', [
            'company' => $company,
            'branches' => $branches,
            'activeBranch' => $tenantContext->getBranch(),
        ]);
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create(TenantContext $tenantContext): View
    {
        return view('tenant.branches.create', [
            'company' => $tenantContext->getCompany(),
        ]);
    }

    /**
     * Store a newly created branch in storage.
     */
    public function store(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $company = $tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('branches')->where('company_id', $company->id),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        $isMain = $request->boolean('is_main');

        // If this branch is designated as Main, reset other branches
        if ($isMain) {
            Branch::where('company_id', $company->id)->update(['is_main' => false]);
        }

        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'is_main' => $isMain,
            'status' => 'active',
        ]);

        return redirect()->route('branches.index')->with('success', "Branch {$branch->name} ({$branch->code}) created successfully.");
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit(Branch $branch, TenantContext $tenantContext): View
    {
        $company = $tenantContext->getCompany();

        if ($branch->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        return view('tenant.branches.edit', [
            'company' => $company,
            'branch' => $branch,
        ]);
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(Request $request, Branch $branch, TenantContext $tenantContext): RedirectResponse
    {
        $company = $tenantContext->getCompany();

        if ($branch->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('branches')
                    ->where('company_id', $company->id)
                    ->ignore($branch->id),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'status' => ['required', 'in:active,inactive'],
            'is_main' => ['nullable', 'boolean'],
        ]);

        $isMain = $request->boolean('is_main');

        if ($isMain) {
            Branch::where('company_id', $company->id)
                ->where('id', '!=', $branch->id)
                ->update(['is_main' => false]);
        }

        $branch->update([
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
            'is_main' => $isMain,
        ]);

        return redirect()->route('branches.index')->with('success', "Branch {$branch->name} updated successfully.");
    }

    /**
     * Toggle active/inactive status of a branch.
     */
    public function toggleStatus(Branch $branch, TenantContext $tenantContext): RedirectResponse
    {
        $company = $tenantContext->getCompany();

        if ($branch->company_id !== $company->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($branch->is_main && $branch->status === 'active') {
            return back()->with('error', 'Cannot deactivate the primary headquarters branch.');
        }

        $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
        $branch->update(['status' => $newStatus]);

        return back()->with('success', "Branch {$branch->name} status changed to {$newStatus}.");
    }
}
