<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function index(): View
    {
        $company = $this->tenantContext->getCompany();

        $categories = ProductCategory::where('company_id', $company->id)
            ->with(['parent', 'children'])
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $parentCategories = ProductCategory::where('company_id', $company->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('tenant.categories.index', compact('categories', 'parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('product_categories', 'id')->where('company_id', $company->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        // Check uniqueness within company
        if (ProductCategory::where('company_id', $company->id)->where('slug', $slug)->exists()) {
            $slug = $slug . '-' . Str::lower(Str::random(4));
        }

        ProductCategory::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? 'bi-box-seam',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with('success', "Category '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($category->company_id !== $company->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('product_categories', 'id')->where('company_id', $company->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? $category->icon,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        $company = $this->tenantContext->getCompany();
        if ($category->company_id !== $company->id) {
            abort(404);
        }

        if ($category->products()->exists()) {
            return back()->with('error', "Cannot delete category '{$category->name}' because products are currently assigned to it.");
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', "Category '{$category->name}' removed.");
    }
}
