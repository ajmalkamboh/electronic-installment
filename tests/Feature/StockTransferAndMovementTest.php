<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTransferAndMovementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branchLahore;
    protected Branch $branchIslamabad;
    protected User $admin;
    protected Product $product;
    protected SerializedItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Falcon Electronics',
            'slug' => 'falcon-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branchLahore = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Showroom',
            'code' => 'LHR-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->branchIslamabad = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Islamabad Showroom',
            'code' => 'ISB-01',
            'is_main' => false,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchLahore->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Falcon Admin',
            'email' => 'admin@falcon.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'LED Televisions',
            'slug' => 'led-tvs',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Sony',
            'model_name' => 'Bravia 65 Inch 4K Google TV',
            'sku' => 'SNY-BR65-4K',
            'base_cash_price' => 295000.00,
            'min_down_payment_pct' => 20.00,
            'is_serialized' => true,
        ]);

        // Setup stock in Lahore branch
        $this->item = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchLahore->id,
            'product_id' => $this->product->id,
            'ulid' => (string) Str::ulid(),
            'serial_number' => 'SONY-TV-SN-99881',
            'status' => 'in_stock',
            'purchase_cost' => 240000.00,
            'received_at' => now(),
        ]);

        BranchInventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchLahore->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 1,
            'quantity_reserved' => 0,
            'quantity_available' => 1,
        ]);
    }

    public function test_can_transfer_serialized_unit_between_showrooms(): void
    {
        $payload = [
            'destination_branch_id' => $this->branchIslamabad->id,
            'notes' => 'Transferred for VIP client showroom booking.',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('inventory.transfer.store', $this->item), $payload);

        $response->assertRedirect(route('inventory.serialized'));

        // Unit showroom location changed
        $this->assertEquals($this->branchIslamabad->id, $this->item->fresh()->branch_id);

        // Source branch inventory decremented
        $sourceInv = BranchInventory::where('branch_id', $this->branchLahore->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(0, $sourceInv->quantity_on_hand);
        $this->assertEquals(0, $sourceInv->quantity_available);

        // Destination branch inventory incremented
        $destInv = BranchInventory::where('branch_id', $this->branchIslamabad->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertNotNull($destInv);
        $this->assertEquals(1, $destInv->quantity_on_hand);
        $this->assertEquals(1, $destInv->quantity_available);

        // Both transfer out and transfer in logged in stock movements
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $this->company->id,
            'serialized_item_id' => $this->item->id,
            'movement_type' => 'branch_transfer_out',
            'source_branch_id' => $this->branchLahore->id,
            'destination_branch_id' => $this->branchIslamabad->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $this->company->id,
            'serialized_item_id' => $this->item->id,
            'movement_type' => 'branch_transfer_in',
            'source_branch_id' => $this->branchLahore->id,
            'destination_branch_id' => $this->branchIslamabad->id,
        ]);
    }

    public function test_cannot_transfer_to_another_company_branch(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Retailers',
            'slug' => 'foreign-retailers',
            'status' => 'active',
        ]);

        $foreignBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Branch',
            'code' => 'FRG-01',
            'status' => 'active',
        ]);

        $payload = [
            'destination_branch_id' => $foreignBranch->id,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('inventory.transfer.store', $this->item), $payload);

        $response->assertSessionHasErrors('destination_branch_id');
        $this->assertEquals($this->branchLahore->id, $this->item->fresh()->branch_id);
    }
}
