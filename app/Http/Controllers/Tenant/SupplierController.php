<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function index(): View
    {
        $company = $this->tenantContext->getCompany();

        $suppliers = Supplier::where('company_id', $company->id)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('tenant.suppliers.index', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'ntn_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        Supplier::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'ntn_number' => $validated['ntn_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('suppliers.index')->with('success', "Wholesale supplier '{$validated['name']}' registered.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($supplier->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'ntn_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $supplier->update([
            'name' => $validated['name'],
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'ntn_number' => $validated['ntn_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('suppliers.index')->with('success', "Supplier '{$supplier->name}' updated.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($supplier->company_id !== $company->id) {
            abort(404);
        }

        if ($supplier->products()->exists()) {
            return back()->with('error', "Cannot delete supplier '{$supplier->name}' because products are mapped to it.");
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', "Supplier '{$supplier->name}' removed.");
    }
}
