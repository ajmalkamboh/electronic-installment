<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuarantorController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Add a guarantor to an existing customer dossier.
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        $validated = $request->validate([
            'cnic' => ['required', 'string', 'max:20'],
            'full_name' => ['required', 'string', 'max:191'],
            'relationship' => ['required', 'string', 'max:50'],
            'mobile' => ['required', 'string', 'max:25'],
            'address' => ['required', 'string'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer_name' => ['nullable', 'string', 'max:191'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
        ]);

        // BR-GUAR-02: Debtor cannot act as their own guarantor
        if ($validated['cnic'] === $customer->cnic) {
            return back()->withErrors(['cnic' => 'A customer cannot act as their own guarantor.']);
        }

        Guarantor::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'cnic' => $validated['cnic'],
            'full_name' => $validated['full_name'],
            'relationship' => $validated['relationship'],
            'mobile' => $validated['mobile'],
            'address' => $validated['address'],
            'occupation' => $validated['occupation'] ?? null,
            'employer_name' => $validated['employer_name'] ?? null,
            'monthly_income' => $validated['monthly_income'] ?? null,
            'is_verified' => false,
        ]);

        return back()->with('success', "Guarantor '{$validated['full_name']}' added to customer dossier.");
    }

    /**
     * Toggle the verification status of a guarantor.
     */
    public function toggleVerified(Guarantor $guarantor): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $guarantor->company_id !== (int) $company->id) {
            abort(404, 'Guarantor record not found.');
        }

        $guarantor->update([
            'is_verified' => !$guarantor->is_verified,
        ]);

        $statusWord = $guarantor->is_verified ? 'verified' : 'unverified';

        return back()->with('success', "Guarantor '{$guarantor->full_name}' marked as {$statusWord}.");
    }

    /**
     * Remove a guarantor from the customer dossier.
     */
    public function destroy(Guarantor $guarantor): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $guarantor->company_id !== (int) $company->id) {
            abort(404, 'Guarantor record not found.');
        }

        $customer = $guarantor->customer;
        $name = $guarantor->full_name;
        $guarantor->delete();

        return back()->with('success', "Guarantor '{$name}' removed from dossier.");
    }
}
