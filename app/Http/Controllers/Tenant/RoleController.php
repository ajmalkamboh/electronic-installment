<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of system and custom roles.
     */
    public function index(): View
    {
        $company = $this->tenantContext->getCompany();

        $roles = Role::availableForCompany($company->id)
            ->withCount(['permissions', 'primaryUsers'])
            ->orderBy('is_system', 'desc')
            ->orderBy('display_name', 'asc')
            ->get();

        return view('tenant.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new custom role.
     */
    public function create(): View
    {
        $permissionsGrouped = Permission::all()->groupBy('group');

        return view('tenant.roles.create', compact('permissionsGrouped'));
    }

    /**
     * Store a newly created custom role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $slug = Str::slug($validated['display_name'], '_');

        // Check uniqueness within company
        $exists = Role::where('company_id', $company->id)->where('name', $slug)->exists()
            || Role::system()->where('name', $slug)->exists();

        if ($exists) {
            $slug .= '_' . time();
        }

        $role = Role::create([
            'company_id' => $company->id,
            'name' => $slug,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('roles.index')
            ->with('success', "Custom role '{$role->display_name}' created successfully with " . count($validated['permissions'] ?? []) . " permissions.");
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): View
    {
        $company = $this->tenantContext->getCompany();

        // Enforce company boundary for custom roles
        if (!$role->is_system && (int) $role->company_id !== (int) $company->id) {
            abort(404, 'Role not found.');
        }

        $permissionsGrouped = Permission::all()->groupBy('group');
        $assignedPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

        return view('tenant.roles.edit', compact('role', 'permissionsGrouped', 'assignedPermissionIds'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if (!$role->is_system && (int) $role->company_id !== (int) $company->id) {
            abort(404, 'Role not found.');
        }

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // If custom role, allow updating display name and description
        if (!$role->is_system) {
            $role->update([
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
            ]);
        }

        // Sync permissions
        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('roles.index')
            ->with('success', "Role '{$role->display_name}' permissions updated successfully.");
    }

    /**
     * Remove the specified custom role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ($role->is_system) {
            return back()->withErrors(['error' => 'System predefined roles cannot be deleted.']);
        }

        if ((int) $role->company_id !== (int) $company->id) {
            abort(404, 'Role not found.');
        }

        if ($role->primaryUsers()->count() > 0 || $role->users()->count() > 0) {
            return back()->withErrors(['error' => "Cannot delete role '{$role->display_name}' because staff members are actively assigned to it."]);
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', "Custom role '{$role->display_name}' has been deleted.");
    }
}
