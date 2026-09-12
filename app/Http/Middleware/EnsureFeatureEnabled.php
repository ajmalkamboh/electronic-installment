<?php

namespace App\Http\Middleware;

use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        protected PlanLimitService $planLimitService,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $company = $this->tenantContext->getCompany() ?? $request->user()?->company;

        if (! $company) {
            return $next($request);
        }

        if (! $this->planLimitService->canAccessFeature($company, $feature)) {
            $planName = $company->currentPlan()?->name ?? 'Current';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Feature Disabled',
                    'message' => "The feature '{$feature}' is not included in your {$planName} plan. Please upgrade your plan.",
                    'feature' => $feature,
                ], 403);
            }

            return redirect()->route('subscription.index')
                ->with('error', "The feature '{$feature}' is not available on your {$planName} tier. Please upgrade your subscription to unlock this feature.");
        }

        return $next($request);
    }
}
