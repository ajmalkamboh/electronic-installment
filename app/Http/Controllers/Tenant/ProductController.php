<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function index(Request $request): View
    {
        $company = $this->tenantContext->getCompany();

        $query = Product::where('company_id', $company->id)
            ->with(['category', 'supplier', 'branchInventories']);

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'like', "%{$search}%")
                    ->orWhere('model_name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category Filter
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Serialization Filter
        if ($request->filled('is_serialized')) {
            $query->where('is_serialized', $request->boolean('is_serialized'));
        }

        $products = $query->orderBy('brand')->orderBy('model_name')->paginate(15)->withQueryString();

        // Metrics
        $totalProducts = Product::where('company_id', $company->id)->count();
        $serializedCount = Product::where('company_id', $company->id)->where('is_serialized', true)->count();
        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('tenant.products.index', compact('products', 'totalProducts', 'serializedCount', 'categories'));
    }

    public function create(): View
    {
        $company = $this->tenantContext->getCompany();

        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('tenant.products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'category_id' => ['required', Rule::exists('product_categories', 'id')->where('company_id', $company->id)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $company->id)],
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->where('company_id', $company->id)],
            'base_cash_price' => ['required', 'numeric', 'min:500', 'max:10000000'],
            'min_down_payment_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_serialized' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'max:50'],
            'storage' => ['nullable', 'string', 'max:50'],
            'ram' => ['nullable', 'string', 'max:50'],
        ]);

        $specs = array_filter([
            'color' => $request->input('color'),
            'storage' => $request->input('storage'),
            'ram' => $request->input('ram'),
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'category_id' => $validated['category_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'brand' => $validated['brand'],
            'model_name' => $validated['model_name'],
            'sku' => ! empty($validated['sku']) ? strtoupper(trim($validated['sku'])) : null,
            'base_cash_price' => (float) $validated['base_cash_price'],
            'min_down_payment_pct' => (float) $validated['min_down_payment_pct'],
            'is_serialized' => $request->boolean('is_serialized', true),
            'description' => $validated['description'] ?? null,
            'specifications' => $specs ?: null,
            'is_active' => true,
        ]);

        return redirect()->route('products.show', $product)
            ->with('success', "Product '{$product->brand} {$product->model_name}' added to catalog with SKU: {$product->sku}.");
    }

    public function show(Product $product): View
    {
        $company = $this->tenantContext->getCompany();
        if ($product->company_id !== $company->id) {
            abort(404);
        }

        $product->load(['category', 'supplier', 'branchInventories.branch']);

        $serializedItems = $product->serializedItems()
            ->with(['branch', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('tenant.products.show', compact('product', 'serializedItems'));
    }

    public function edit(Product $product): View
    {
        $company = $this->tenantContext->getCompany();
        if ($product->company_id !== $company->id) {
            abort(404);
        }

        $categories = ProductCategory::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('tenant.products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($product->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'category_id' => ['required', Rule::exists('product_categories', 'id')->where('company_id', $company->id)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $company->id)],
            'brand' => ['required', 'string', 'max:100'],
            'model_name' => ['required', 'string', 'max:191'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)->where('company_id', $company->id)],
            'base_cash_price' => ['required', 'numeric', 'min:500', 'max:10000000'],
            'min_down_payment_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_serialized' => ['boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);

        $product->update([
            'category_id' => $validated['category_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'brand' => $validated['brand'],
            'model_name' => $validated['model_name'],
            'sku' => ! empty($validated['sku']) ? strtoupper(trim($validated['sku'])) : $product->sku,
            'base_cash_price' => (float) $validated['base_cash_price'],
            'min_down_payment_pct' => (float) $validated['min_down_payment_pct'],
            'is_serialized' => $request->boolean('is_serialized', true),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('products.show', $product)
            ->with('success', "Product '{$product->brand} {$product->model_name}' updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($product->company_id !== $company->id) {
            abort(404);
        }

        if ($product->serializedItems()->where('status', '!=', 'disbursed')->exists()) {
            return back()->with('error', 'Cannot delete product with physical units in stock or reserved.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', "Product '{$product->brand} {$product->model_name}' removed from active catalog.");
    }
}
