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

        if (!$user) {
            return redirect()->route('login');
        }

        // Security check: Verify user status
        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is currently inactive or suspended. Please contact support.',
            ]);
        }

        // Initialize tenant context for the user's company & branch
        if ($user->company) {
            if (!$user->company->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your company subscription or status is inactive. Please contact support.',
                ]);
            }

            $this->tenantContext->setCompany($user->company);

            // Determine active branch: check session override for admins
            $activeBranch = null;
            if ($user->isCompanyAdmin() && $request->session()->has('active_branch_id')) {
                $sessionBranchId = $request->session()->get('active_branch_id');
                $activeBranch = $user->company->branches()->where('id', $sessionBranchId)->where('status', 'active')->first();
            }

            // Fallback to user's assigned branch or first active branch
            if (!$activeBranch) {
                $activeBranch = $user->branch ?? $user->company->branches()->where('status', 'active')->first();
            }

            $this->tenantContext->setBranch($activeBranch);
        }

        return $next($request);
    }
}
