<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected ProductCategory $category;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Crown Electronics',
            'slug' => 'crown-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Gulberg Showroom',
            'code' => 'GLB-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Crown Admin',
            'email' => 'admin@crown.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Samsung Direct Pakistan',
            'phone' => '042-35876543',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_products_catalog(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Product Master Catalog');
    }

    public function test_admin_can_create_new_product(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'brand' => 'Samsung',
            'model_name' => 'Galaxy S25 Ultra 512GB',
            'sku' => 'SAM-S25U-512',
            'base_cash_price' => 385000.00,
            'min_down_payment_pct' => 25.00,
            'is_serialized' => '1',
            'color' => 'Titanium Silver',
            'storage' => '512GB',
            'ram' => '12GB',
            'description' => 'Flagship smartphone with Snapdragon 8 Elite',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('products.store'), $payload);

        $product = Product::where('sku', 'SAM-S25U-512')->first();
        $this->assertNotNull($product);

        $response->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'brand' => 'Samsung',
            'model_name' => 'Galaxy S25 Ultra 512GB',
            'sku' => 'SAM-S25U-512',
            'base_cash_price' => 385000.00,
            'min_down_payment_pct' => 25.00,
            'is_serialized' => true,
        ]);
    }

    public function test_enforces_sku_uniqueness_per_company(): void
    {
        Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Apple',
            'model_name' => 'iPhone 16 Pro',
            'sku' => 'IPHONE-16P',
            'base_cash_price' => 450000.00,
            'min_down_payment_pct' => 20.00,
        ]);

        $payload = [
            'category_id' => $this->category->id,
            'brand' => 'Apple',
            'model_name' => 'iPhone 16 Pro Duplicate',
            'sku' => 'IPHONE-16P', // Duplicate
            'base_cash_price' => 450000.00,
            'min_down_payment_pct' => 20.00,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('products.store'), $payload);

        $response->assertSessionHasErrors('sku');
    }

    public function test_allows_same_sku_in_different_company_tenants(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
        ]);

        $otherCategory = ProductCategory::create([
            'company_id' => $otherCompany->id,
            'name' => 'Phones',
            'slug' => 'phones',
        ]);

        Product::create([
            'company_id' => $otherCompany->id,
            'category_id' => $otherCategory->id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Apple',
            'model_name' => 'iPhone 16 Pro',
            'sku' => 'IPHONE-SHARED-SKU',
            'base_cash_price' => 450000.00,
            'min_down_payment_pct' => 20.00,
        ]);

        // Creating in Crown Electronics with the same SKU should succeed
        $payload = [
            'category_id' => $this->category->id,
            'brand' => 'Apple',
            'model_name' => 'iPhone 16 Pro Crown',
            'sku' => 'IPHONE-SHARED-SKU',
            'base_cash_price' => 460000.00,
            'min_down_payment_pct' => 20.00,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('products.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku' => 'IPHONE-SHARED-SKU',
        ]);
    }
}
