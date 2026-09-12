<?php

namespace App\Http\Middleware;

use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function __construct(
        protected PlanLimitService $planLimitService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $company = $this->tenantContext->getCompany() ?? $request->user()?->company;

        if (! $company) {
            return $next($request);
        }

        $check = match ($resource) {
            'users' => $this->planLimitService->checkUserQuota($company),
            'branches' => $this->planLimitService->checkBranchQuota($company),
            'agreements' => $this->planLimitService->checkAgreementQuota($company),
            'transactions' => $this->planLimitService->checkTransactionQuota($company),
            default => ['allowed' => true, 'message' => null],
        };

        if (! $check['allowed']) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Quota Exceeded',
                    'message' => $check['message'],
                    'resource' => $resource,
                    'current' => $check['current'] ?? 0,
                    'limit' => $check['limit'] ?? 0,
                ], 403);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $check['message'] ?? 'Subscription plan limit exceeded. Please upgrade your plan.');
        }

        return $next($request);
    }
}
