<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->refresh();

        // Security check: Verify user status
        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is currently inactive or suspended. Please contact support.',
            ]);
        }

        // Super Admin bypass: Global administrator without company
        if ($user->isSuperAdmin() && ! $user->company) {
            return $next($request);
        }

        // Initialize tenant context for the user's company & branch
        if ($user->company) {
            $company = $user->company;

            // Handle suspended company status (BR-SAAS)
            if ($company->isSuspended()) {
                // If company admin, allow only subscription & billing portal access
                if ($user->isCompanyAdmin()) {
                    $this->tenantContext->setCompany($company);

                    if (! $request->routeIs('subscription.*') && ! $request->routeIs('logout')) {
                        return redirect()->route('subscription.index')->with(
                            'error',
                            'Your company subscription is suspended due to expiration or non-payment. Operational features are locked until the subscription is renewed.'
                        );
                    }

                    return $next($request);
                }

                // Non-admin employees are blocked from accessing a suspended company
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your company account is suspended due to an overdue subscription. Please contact your administrator.',
                ]);
            }

            // Inactive or cancelled company accounts
            if (! $company->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your company subscription or status is inactive. Please contact support.',
                ]);
            }

            $this->tenantContext->setCompany($company);

            // Determine active branch: check session override for admins
            $activeBranch = null;
            if ($user->isCompanyAdmin() && $request->session()->has('active_branch_id')) {
                $sessionBranchId = $request->session()->get('active_branch_id');
                $activeBranch = $company->branches()->where('id', $sessionBranchId)->where('status', 'active')->first();
            }

            // Fallback to user's assigned branch or first active branch
            if (! $activeBranch) {
                $activeBranch = $user->branch ?? $company->branches()->where('status', 'active')->first();
            }

            $this->tenantContext->setBranch($activeBranch);
        }

        return $next($request);
    }
}
