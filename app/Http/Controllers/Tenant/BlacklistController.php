<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlacklistController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Flag or unflag a customer on the institutional blacklist.
     */
    public function toggle(Request $request, Customer $customer): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('credit.blacklist')) {
            abort(403, 'Unauthorized to manage blacklist registry.');
        }

        $company = $this->tenantContext->getCompany();
        if ($customer->company_id !== $company->id) {
            abort(404, 'Customer record not found.');
        }

        if ($customer->isBlacklisted()) {
            // Restore / Un-blacklist
            DB::transaction(function () use ($customer) {
                $customer->update(['status' => 'restricted']); // Kept restricted for careful review
                if ($customer->creditProfile) {
                    $customer->creditProfile->update([
                        'blacklisted_reason' => null,
                        'blacklisted_at' => null,
                        'credit_score' => 40,
                    ]);
                }
            });

            return back()->with('success', "Customer {$customer->full_name} has been removed from the blacklist and placed in restricted review status.");
        }

        // Blacklist customer
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->update(['status' => 'blacklisted']);
            if ($customer->creditProfile) {
                $customer->creditProfile->update([
                    'blacklisted_reason' => $validated['reason'],
                    'blacklisted_at' => now(),
                    'credit_score' => 0,
                    'max_authorized_credit' => 0.00,
                ]);
            }

            // Reject any pending assessments
            $customer->creditAssessments()
                ->where('status', 'pending_approval')
                ->update(['status' => 'rejected']);
        });

        return back()->with('success', "Customer {$customer->full_name} has been placed on the institutional blacklist. Credit limit revoked.");
    }
}
