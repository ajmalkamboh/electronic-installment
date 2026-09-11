<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryStockReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Product $product;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Diamond Electronics',
            'slug' => 'diamond-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Showroom',
            'code' => 'MAIN-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Inventory Manager',
            'email' => 'manager@diamond.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Air Conditioners',
            'slug' => 'air-conditioners',
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Haier Commercial Corp',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'supplier_id' => $this->supplier->id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Haier',
            'model_name' => '1.5 Ton T3 DC Inverter',
            'sku' => 'HAI-15T3-INV',
            'base_cash_price' => 195000.00,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
        ]);
    }

    public function test_can_receive_serialized_stock_and_update_inventory(): void
    {
        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'supplier_id' => $this->supplier->id,
            'purchase_cost' => 165000.00,
            'reference_number' => 'PO-99120',
            'bulk_imei_input' => "HAIER-SN-001\nHAIER-SN-002\nHAIER-SN-003",
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('inventory.receipt.store'), $payload);

        $response->assertRedirect(route('inventory.index', ['branch_id' => $this->branch->id]));

        // Check Serialized items created
        $this->assertDatabaseHas('serialized_items', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'serial_number' => 'HAIER-SN-001',
            'status' => 'in_stock',
        ]);
        $this->assertDatabaseHas('serialized_items', [
            'company_id' => $this->company->id,
            'serial_number' => 'HAIER-SN-003',
        ]);

        // Check BranchInventory aggregated counts
        $inv = BranchInventory::where('branch_id', $this->branch->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertNotNull($inv);
        $this->assertEquals(3, $inv->quantity_on_hand);
        $this->assertEquals(3, $inv->quantity_available);
        $this->assertEquals(0, $inv->quantity_reserved);

        // Check StockMovement created
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'destination_branch_id' => $this->branch->id,
            'movement_type' => 'purchase_receipt',
            'reference_number' => 'PO-99120',
        ]);
    }

    public function test_fast_bulk_scanner_parses_dual_imei_smartphones(): void
    {
        $phone = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->product->category_id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Xiaomi',
            'model_name' => 'Redmi Note 14 Pro',
            'sku' => 'XIA-RN14P',
            'base_cash_price' => 75000.00,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $phone->id,
            'purchase_cost' => 62000.00,
            'bulk_imei_input' => "863492051234561,863492051234562\n863492051234563,863492051234564",
        ];

        $this->actingAs($this->admin)
            ->post(route('inventory.receipt.store'), $payload);

        $this->assertDatabaseHas('serialized_items', [
            'company_id' => $this->company->id,
            'product_id' => $phone->id,
            'imei_1' => '863492051234561',
            'imei_2' => '863492051234562',
        ]);

        $this->assertDatabaseHas('serialized_items', [
            'company_id' => $this->company->id,
            'product_id' => $phone->id,
            'imei_1' => '863492051234563',
            'imei_2' => '863492051234564',
        ]);
    }

    public function test_rejects_duplicate_imei_within_same_company(): void
    {
        SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'ulid' => (string) Str::ulid(),
            'serial_number' => 'DUP-SERIAL-100',
            'status' => 'in_stock',
        ]);

        $payload = [
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'bulk_imei_input' => "DUP-SERIAL-100", // Already exists
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('inventory.receipt.store'), $payload);

        $response->assertSessionHasErrors('serial_number');
    }
}
