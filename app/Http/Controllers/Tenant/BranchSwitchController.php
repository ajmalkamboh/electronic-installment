<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchSwitchController extends Controller
{
    /**
     * Switch the active operational branch context in session.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $user = Auth::user();
        $company = $user->company;

        if (!$company) {
            abort(403, 'User does not belong to an active company.');
        }

        // Locate branch strictly within the user's company
        $branch = Branch::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('id', $request->input('branch_id'))
            ->where('status', 'active')
            ->firstOrFail();

        // Permission check: Company Admins can switch anywhere; others can only switch to their assigned branch
        if (!$user->isCompanyAdmin() && $user->branch_id !== $branch->id) {
            abort(403, 'You are not authorized to switch to this branch.');
        }

        $request->session()->put('active_branch_id', $branch->id);

        return back()->with('success', "Operating location switched to: {$branch->name} ({$branch->code})");
    }
}
