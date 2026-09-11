<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Services\Pricing\PricingEngine;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingCalculatorController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PricingEngine $pricingEngine
    ) {}

    /**
     * Showroom interactive quotation and pricing simulator.
     */
    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('brand')
            ->orderBy('model_name')
            ->get();

        $plans = InstallmentPlan::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('tenure_months')
            ->get();

        $selectedProduct = null;
        $selectedPlan = null;
        $calculationResult = null;
        $tenureComparisons = [];

        if ($productId = $request->input('product_id')) {
            $selectedProduct = Product::where('company_id', $company->id)->find($productId);
        }

        if (! $selectedProduct && $products->isNotEmpty()) {
            $selectedProduct = $products->first();
        }

        if ($selectedProduct) {
            $planId = $request->input('plan_id');
            $selectedPlan = $planId ? InstallmentPlan::where('company_id', $company->id)->find($planId) : null;
            if (! $selectedPlan && $plans->isNotEmpty()) {
                $selectedPlan = $plans->where('tenure_months', 12)->first() ?? $plans->first();
            }

            if ($selectedPlan) {
                $customDownPayment = $request->has('down_payment') ? (float) $request->input('down_payment') : null;
                $customMarkupRate = $request->has('markup_rate') ? (float) $request->input('markup_rate') : null;

                $calculationResult = $this->pricingEngine->calculatePlan(
                    $selectedProduct,
                    $selectedPlan,
                    $customDownPayment,
                    $customMarkupRate
                );

                $tenureComparisons = $this->pricingEngine->compareTenures(
                    $selectedProduct,
                    $customDownPayment
                );
            }
        }

        return view('tenant.pricing.calculator', compact(
            'products',
            'plans',
            'selectedProduct',
            'selectedPlan',
            'calculationResult',
            'tenureComparisons'
        ));
    }

    /**
     * AJAX endpoint for real-time live quotation calculation.
     */
    public function calculate(Request $request): JsonResponse
    {
        $company = $this->tenantContext->getCompany();

        $request->validate([
            'cash_price' => ['nullable', 'numeric', 'min:0'],
            'product_id' => ['nullable', 'exists:products,id'],
            'plan_id' => ['nullable', 'exists:installment_plans,id'],
            'tenure_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'markup_rate' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'calculation_model' => ['nullable', 'in:flat_percentage,fixed_amount,reducing_balance'],
        ]);

        $cashPrice = (float) $request->input('cash_price', 0);
        $product = null;

        if ($productId = $request->input('product_id')) {
            $product = Product::where('company_id', $company->id)->find($productId);
            if ($product) {
                $cashPrice = (float) $product->base_cash_price;
            }
        }

        $plan = null;
        if ($planId = $request->input('plan_id')) {
            $plan = InstallmentPlan::where('company_id', $company->id)->find($planId);
        }

        $tenureMonths = $plan ? (int) $plan->tenure_months : (int) $request->input('tenure_months', 12);
        $model = $plan ? $plan->markup_calculation_model : $request->input('calculation_model', 'flat_percentage');
        $markupRate = $request->has('markup_rate') && $request->input('markup_rate') !== null
            ? (float) $request->input('markup_rate')
            : ($plan ? (float) $plan->default_markup_rate_pct : 25.0);

        $minDownPaymentPct = $product ? (float) $product->min_down_payment_pct : ($plan ? (float) $plan->min_down_payment_pct : 20.0);
        $defaultDownPayment = round($cashPrice * ($minDownPaymentPct / 100.0), -2);
        $downPayment = $request->has('down_payment') && $request->input('down_payment') !== null
            ? (float) $request->input('down_payment')
            : $defaultDownPayment;

        $result = $this->pricingEngine->calculateCustom(
            cashPrice: $cashPrice,
            tenureMonths: $tenureMonths,
            model: $model,
            markupRate: $markupRate,
            downPayment: $downPayment,
            fixedAmount: $plan?->fixed_markup_amount ? (float) $plan->fixed_markup_amount : null,
            frequency: $plan?->installment_frequency ?? 'monthly',
            minDownPaymentPct: $minDownPaymentPct
        );

        $comparisons = [];
        if ($product) {
            $comparisonObjects = $this->pricingEngine->compareTenures($product, $downPayment);
            foreach ($comparisonObjects as $t => $comp) {
                $comparisons[$t] = $comp->toArray();
            }
        }

        return response()->json([
            'success' => true,
            'result' => $result->toArray(),
            'comparisons' => $comparisons,
        ]);
    }
}
