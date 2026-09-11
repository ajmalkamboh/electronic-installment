<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPlan;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstallmentPlanController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function index(): View
    {
        $company = $this->tenantContext->getCompany();

        $plans = InstallmentPlan::where('company_id', $company->id)
            ->orderBy('tenure_months')
            ->get();

        return view('tenant.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('tenant.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'tenure_months' => ['required', 'integer', 'in:1,2,3,4,6,9,12,18,24,36'],
            'markup_calculation_model' => ['required', 'in:flat_percentage,fixed_amount,reducing_balance'],
            'default_markup_rate_pct' => ['required', 'numeric', 'min:0', 'max:200'],
            'fixed_markup_amount' => ['nullable', 'numeric', 'min:0'],
            'min_down_payment_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_frequency' => ['required', 'in:monthly,bi_weekly,weekly'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        if (InstallmentPlan::where('company_id', $company->id)->where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::lower(Str::random(4));
        }

        $plan = InstallmentPlan::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'tenure_months' => (int) $validated['tenure_months'],
            'markup_calculation_model' => $validated['markup_calculation_model'],
            'default_markup_rate_pct' => (float) $validated['default_markup_rate_pct'],
            'fixed_markup_amount' => ! empty($validated['fixed_markup_amount']) ? (float) $validated['fixed_markup_amount'] : null,
            'min_down_payment_pct' => (float) $validated['min_down_payment_pct'],
            'installment_frequency' => $validated['installment_frequency'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('plans.index')->with('success', "Installment plan '{$plan->name}' created successfully.");
    }

    public function edit(InstallmentPlan $plan): View
    {
        $company = $this->tenantContext->getCompany();
        if ($plan->company_id !== $company->id) {
            abort(404);
        }

        return view('tenant.plans.edit', compact('plan'));
    }

    public function update(Request $request, InstallmentPlan $plan): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($plan->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'tenure_months' => ['required', 'integer', 'in:1,2,3,4,6,9,12,18,24,36'],
            'markup_calculation_model' => ['required', 'in:flat_percentage,fixed_amount,reducing_balance'],
            'default_markup_rate_pct' => ['required', 'numeric', 'min:0', 'max:200'],
            'fixed_markup_amount' => ['nullable', 'numeric', 'min:0'],
            'min_down_payment_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'installment_frequency' => ['required', 'in:monthly,bi_weekly,weekly'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'tenure_months' => (int) $validated['tenure_months'],
            'markup_calculation_model' => $validated['markup_calculation_model'],
            'default_markup_rate_pct' => (float) $validated['default_markup_rate_pct'],
            'fixed_markup_amount' => ! empty($validated['fixed_markup_amount']) ? (float) $validated['fixed_markup_amount'] : null,
            'min_down_payment_pct' => (float) $validated['min_down_payment_pct'],
            'installment_frequency' => $validated['installment_frequency'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('plans.index')->with('success', "Installment plan '{$plan->name}' updated.");
    }

    public function toggle(InstallmentPlan $plan): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($plan->company_id !== $company->id) {
            abort(404);
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'activated' : 'deactivated';
        return redirect()->route('plans.index')->with('success', "Plan '{$plan->name}' {$status}.");
    }

    public function destroy(InstallmentPlan $plan): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($plan->company_id !== $company->id) {
            abort(404);
        }

        $plan->delete();

        return redirect()->route('plans.index')->with('success', "Plan '{$plan->name}' removed.");
    }
}
