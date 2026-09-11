<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerVerification;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerVerificationController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Log a physical or telephonic field investigation report.
     */
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404, 'Customer record not found.');
        }

        $validated = $request->validate([
            'verification_type' => ['required', 'in:field_visit,telephonic,utility_bill'],
            'residence_confirmed' => ['required', 'boolean'],
            'workplace_confirmed' => ['nullable', 'boolean'],
            'investigator_notes' => ['required', 'string', 'max:2000'],
            'outcome' => ['required', 'in:approved,conditional,rejected'],
            'verified_at' => ['required', 'date'],
        ]);

        CustomerVerification::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'verified_by_user_id' => Auth::id(),
            'verification_type' => $validated['verification_type'],
            'residence_confirmed' => $validated['residence_confirmed'],
            'workplace_confirmed' => $validated['workplace_confirmed'] ?? null,
            'investigator_notes' => $validated['investigator_notes'],
            'outcome' => $validated['outcome'],
            'verified_at' => $validated['verified_at'],
        ]);

        // Automatically update customer status based on verification outcome
        if ($validated['outcome'] === 'approved' && $customer->status === 'pending_verification') {
            $customer->update(['status' => 'active']);
        } elseif ($validated['outcome'] === 'rejected') {
            $customer->update(['status' => 'restricted']);
        }

        return back()->with('success', "Field verification investigation logged with outcome: " . ucfirst($validated['outcome']));
    }
}
