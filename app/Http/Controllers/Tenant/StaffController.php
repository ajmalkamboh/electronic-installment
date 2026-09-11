<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of the staff members.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = User::where('company_id', $company->id)
            ->with(['branch', 'roleRecord']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        // Branch Filter
        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        // Role Filter
        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        }

        // Status Filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $staffMembers = $query->orderBy('name')->paginate(15)->withQueryString();

        // Metrics for summary cards
        $totalStaff = User::where('company_id', $company->id)->count();
        $activeStaff = User::where('company_id', $company->id)->where('status', 'active')->count();
        $suspendedStaff = User::where('company_id', $company->id)->where('status', 'suspended')->count();
        $branchesCovered = User::where('company_id', $company->id)->whereNotNull('branch_id')->distinct('branch_id')->count('branch_id');

        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();
        $roles = Role::availableForCompany($company->id)->orderBy('display_name')->get();

        return view('tenant.staff.index', compact(
            'staffMembers',
            'branches',
            'roles',
            'totalStaff',
            'activeStaff',
            'suspendedStaff',
            'branchesCovered'
        ));
    }

    /**
     * Show the form for creating a new staff member.
     */
    public function create(): View
    {
        $company = $this->tenantContext->getCompany();

        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();
        $roles = Role::availableForCompany($company->id)->orderBy('display_name')->get();

        return view('tenant.staff.create', compact('branches', 'roles'));
    }

    /**
     * Store a newly created staff member in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'employee_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'cnic' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) use ($company) {
                    $query->whereNull('company_id')->orWhere('company_id', $company->id);
                }),
            ],
            'designation' => ['nullable', 'string', 'max:100'],
            'joining_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'string', 'in:active,suspended,inactive'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $user = User::create([
            'company_id' => $company->id,
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'employee_code' => $validated['employee_code'] ?? null,
            'cnic' => $validated['cnic'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role_id' => $role->id,
            'role' => $role->name,
            'designation' => $validated['designation'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'salary' => $validated['salary'] ?? null,
            'status' => $validated['status'],
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return redirect()->route('staff.index')
            ->with('success', "Staff member '{$user->name}' ({$role->display_name}) registered successfully.");
    }

    /**
     * Show the form for editing the specified staff member.
     */
    public function edit(User $staff): View
    {
        $company = $this->tenantContext->getCompany();

        // Enforce company boundary
        if ((int) $staff->company_id !== (int) $company->id) {
            abort(404, 'Employee record not found.');
        }

        $branches = Branch::where('company_id', $company->id)->where('status', 'active')->orderBy('name')->get();
        $roles = Role::availableForCompany($company->id)->orderBy('display_name')->get();

        return view('tenant.staff.edit', compact('staff', 'branches', 'roles'));
    }

    /**
     * Update the specified staff member in storage.
     */
    public function update(Request $request, User $staff): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $staff->company_id !== (int) $company->id) {
            abort(404, 'Employee record not found.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users')->ignore($staff->id)],
            'employee_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users')->where(fn ($query) => $query->where('company_id', $company->id))->ignore($staff->id),
            ],
            'cnic' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(function ($query) use ($company) {
                    $query->whereNull('company_id')->orWhere('company_id', $company->id);
                }),
            ],
            'designation' => ['nullable', 'string', 'max:100'],
            'joining_date' => ['nullable', 'date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,suspended,inactive'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Prevent self-suspension
        if ((int) $staff->id === (int) Auth::id() && $validated['status'] !== 'active') {
            return back()->withErrors(['status' => 'You cannot suspend or deactivate your own administrative account.']);
        }

        $role = Role::findOrFail($validated['role_id']);

        $updateData = [
            'branch_id' => $validated['branch_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_code' => $validated['employee_code'] ?? null,
            'cnic' => $validated['cnic'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role_id' => $role->id,
            'role' => $role->name,
            'designation' => $validated['designation'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'salary' => $validated['salary'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $staff->update($updateData);
        $staff->roles()->sync([$role->id]);

        return redirect()->route('staff.index')
            ->with('success', "Staff record for '{$staff->name}' updated successfully.");
    }

    /**
     * Toggle status (active <-> suspended) for a staff member.
     */
    public function toggleStatus(User $staff): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $staff->company_id !== (int) $company->id) {
            abort(404, 'Employee record not found.');
        }

        // Prevent self-suspension
        if ((int) $staff->id === (int) Auth::id()) {
            return back()->withErrors(['error' => 'You cannot toggle the status of your own account.']);
        }

        $newStatus = $staff->status === 'active' ? 'suspended' : 'active';
        $staff->update(['status' => $newStatus]);

        $actionWord = $newStatus === 'active' ? 'activated' : 'suspended';

        return back()->with('success', "Staff member '{$staff->name}' has been {$actionWord}.");
    }

    /**
     * Reset employee password by administrator.
     */
    public function resetPassword(Request $request, User $staff): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $staff->company_id !== (int) $company->id) {
            abort(404, 'Employee record not found.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', "Password for '{$staff->name}' has been reset successfully.");
    }
}
