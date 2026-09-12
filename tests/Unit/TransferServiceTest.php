<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Inventory\TransferService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransferService $service;
    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected User $user;
    protected ProductCategory $category;
    protected Product $product;
    protected SerializedItem $serialUnit1;
    protected SerializedItem $serialUnit2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->service = app(TransferService::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Showroom',
            'slug' => 'kamboh-electronics-showroom',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Ferozepur Road, Lahore',
            'city' => 'Lahore',
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Gulberg Showroom',
            'code' => 'GLB-02',
            'is_main' => false,
            'status' => 'active',
            'address' => 'Gulberg Main Boulevard, Lahore',
            'city' => 'Lahore',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Air Conditioners',
            'code' => 'AC-01',
            'slug' => 'air-conditioners',
            'status' => 'active',
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Haier Pakistan',
            'phone' => '0423111222',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'brand' => 'Haier',
            'model_name' => 'Haier 1.5 Ton DC Inverter',
            'sku' => 'HAC-15T-INV',
            'base_cash_price' => 125000,
            'min_down_payment_pct' => 20,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        // Create 2 serialized units at Branch 1
        $this->serialUnit1 = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'serial_number' => 'HR-AC-2026-001',
            'status' => 'in_stock',
            'purchase_cost' => 105000,
        ]);

        $this->serialUnit2 = SerializedItem::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'serial_number' => 'HR-AC-2026-002',
            'status' => 'in_stock',
            'purchase_cost' => 105000,
        ]);

        // Branch 1 initial stock: 2 on hand
        BranchInventory::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => 2,
            'quantity_available' => 2,
            'quantity_reserved' => 0,
        ]);
    }

    public function test_create_transfer_creates_requested_order_with_items(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [
                ['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id],
                ['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit2->id],
            ],
            $this->user->id,
            'Urgent transfer for customer showroom booking'
        );

        $this->assertInstanceOf(InventoryTransfer::class, $transfer);
        $this->assertEquals('requested', $transfer->status);
        $this->assertStringStartsWith('TRF-', $transfer->transfer_number);
        $this->assertEquals(2, $transfer->total_items_count);
        $this->assertCount(2, $transfer->items);
        $this->assertEquals($this->branch1->id, $transfer->source_branch_id);
        $this->assertEquals($this->branch2->id, $transfer->destination_branch_id);
    }

    public function test_cannot_create_transfer_with_same_source_and_destination(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source showroom and destination showroom cannot be identical.');

        $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch1->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );
    }

    public function test_cannot_transfer_item_not_in_stock_at_source_branch(): void
    {
        $this->serialUnit1->update(['status' => 'reserved']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not available for transfer');

        $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );
    }

    public function test_approve_transfer_updates_status_and_approver(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $approved = $this->service->approveTransfer($transfer, $this->user->id);

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($this->user->id, $approved->approved_by_id);
    }

    public function test_dispatch_transfer_deducts_source_inventory_generates_gate_pass_and_sets_in_transit(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $this->service->approveTransfer($transfer, $this->user->id);

        $logistics = [
            'driver_name' => 'Muhammad Aslam',
            'driver_cnic' => '35201-1234567-1',
            'driver_phone' => '03009876543',
            'vehicle_number' => 'LEA-24-9182',
            'transport_company' => 'Showroom Transit Van',
            'gate_pass_notes' => 'Carton verified, security seal #0912',
        ];

        $dispatched = $this->service->dispatchTransfer($transfer, $logistics, $this->user->id);

        // 1. Transfer status & logistics
        $this->assertEquals('dispatched', $dispatched->status);
        $this->assertNotNull($dispatched->gate_pass_number);
        $this->assertStringStartsWith('GP-', $dispatched->gate_pass_number);
        $this->assertEquals('Muhammad Aslam', $dispatched->driver_name);
        $this->assertEquals('LEA-24-9182', $dispatched->vehicle_number);
        $this->assertNotNull($dispatched->dispatched_at);

        // 2. Serial unit status set to in_transit
        $this->serialUnit1->refresh();
        $this->assertEquals('in_transit', $this->serialUnit1->status);

        // 3. Source branch inventory decremented (was 2, now 1)
        $sourceInv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(1, $sourceInv->quantity_on_hand);
        $this->assertEquals(1, $sourceInv->quantity_available);

        // 4. StockMovement logged outward
        $movement = StockMovement::where('company_id', $this->company->id)
            ->where('movement_type', 'branch_transfer_out')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals($this->branch1->id, $movement->source_branch_id);
        $this->assertEquals($this->branch2->id, $movement->destination_branch_id);
        $this->assertEquals($this->serialUnit1->id, $movement->serialized_item_id);
    }

    public function test_receive_transfer_increments_destination_inventory_updates_branch_and_reverts_in_stock(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $this->service->approveTransfer($transfer, $this->user->id);

        $this->service->dispatchTransfer($transfer, [
            'driver_name' => 'Tariq Butt',
            'driver_cnic' => '35202-9988776-5',
            'driver_phone' => '03215554443',
            'vehicle_number' => 'ICT-22-4411',
        ], $this->user->id);

        $transferItem = $transfer->items()->first();

        $verification = [
            $transferItem->id => [
                'condition' => 'good',
                'notes' => 'Box received intact with warranty card',
            ],
        ];

        $received = $this->service->receiveTransfer($transfer, $verification, $this->user->id);

        // 1. Transfer status
        $this->assertEquals('received', $received->status);
        $this->assertEquals(1, $received->total_received_count);
        $this->assertNotNull($received->received_at);

        // 2. Serialized item branch updated to Branch 2, status restored to in_stock
        $this->serialUnit1->refresh();
        $this->assertEquals($this->branch2->id, $this->serialUnit1->branch_id);
        $this->assertEquals('in_stock', $this->serialUnit1->status);

        // 3. Destination Branch 2 inventory created/incremented to 1
        $destInv = BranchInventory::where('branch_id', $this->branch2->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertNotNull($destInv);
        $this->assertEquals(1, $destInv->quantity_on_hand);
        $this->assertEquals(1, $destInv->quantity_available);

        // 4. StockMovement logged inward
        $movementIn = StockMovement::where('company_id', $this->company->id)
            ->where('movement_type', 'branch_transfer_in')
            ->first();
        $this->assertNotNull($movementIn);
        $this->assertEquals($this->branch1->id, $movementIn->source_branch_id);
        $this->assertEquals($this->branch2->id, $movementIn->destination_branch_id);
    }

    public function test_cancel_transfer_before_dispatch_cancels_order(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $cancelled = $this->service->cancelTransfer($transfer, $this->user->id, 'Customer changed booking model');

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals('Customer changed booking model', $cancelled->rejection_reason);

        // Unit should still be in_stock at Branch 1
        $this->serialUnit1->refresh();
        $this->assertEquals('in_stock', $this->serialUnit1->status);
        $this->assertEquals($this->branch1->id, $this->serialUnit1->branch_id);
    }

    public function test_cancel_transfer_after_dispatch_reverts_stock_to_origin(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $this->service->approveTransfer($transfer, $this->user->id);
        $this->service->dispatchTransfer($transfer, [
            'driver_name' => 'Muhammad Aslam',
            'driver_cnic' => '35201-1234567-1',
            'driver_phone' => '03009876543',
            'vehicle_number' => 'LEA-24-9182',
        ], $this->user->id);

        $cancelled = $this->service->cancelTransfer($transfer, $this->user->id, 'Road closed due to flood, returned to source');

        $this->assertEquals('cancelled', $cancelled->status);

        // Serialized unit status reverted to in_stock
        $this->serialUnit1->refresh();
        $this->assertEquals('in_stock', $this->serialUnit1->status);

        // Branch 1 stock restored back to 2
        $sourceInv = BranchInventory::where('branch_id', $this->branch1->id)
            ->where('product_id', $this->product->id)
            ->first();
        $this->assertEquals(2, $sourceInv->quantity_on_hand);
    }

    public function test_cannot_receive_transfer_not_in_dispatched_status(): void
    {
        $transfer = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("cannot be received in status 'requested'");

        $this->service->receiveTransfer($transfer, [], $this->user->id);
    }

    public function test_get_transfer_metrics_aggregates_counts_accurately(): void
    {
        // 1 requested
        $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit1->id]],
            $this->user->id
        );

        // 1 dispatched (in transit)
        $t2 = $this->service->createTransfer(
            $this->company,
            $this->branch1->id,
            $this->branch2->id,
            [['product_id' => $this->product->id, 'serialized_item_id' => $this->serialUnit2->id]],
            $this->user->id
        );
        $this->service->approveTransfer($t2, $this->user->id);
        $this->service->dispatchTransfer($t2, [
            'driver_name' => 'Ali Raza',
            'driver_cnic' => '35201-1122334-1',
            'driver_phone' => '03001122334',
            'vehicle_number' => 'LHE-21-9988',
        ], $this->user->id);

        $metrics = $this->service->getTransferMetrics($this->company->id);

        $this->assertEquals(2, $metrics['active_transfers']);
        $this->assertEquals(1, $metrics['in_transit_count']);
        $this->assertEquals(1, $metrics['in_transit_units']);
        $this->assertEquals(1, $metrics['pending_approval']);
    }
}
