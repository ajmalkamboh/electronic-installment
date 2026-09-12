<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\InventoryTransfer;
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

class TransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;
    protected User $userA;
    protected User $userB;
    protected ProductCategory $categoryA;
    protected Product $productA;
    protected SerializedItem $serialUnitA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Company A
        $this->companyA = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Kamboh Electronics Main',
            'slug' => 'kamboh-main',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branchA1 = Branch::create([
            'company_id' => $this->companyA->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Ferozepur Road Showroom',
            'code' => 'FZR-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Ferozepur Road, Lahore',
            'city' => 'Lahore',
            'phone' => '04235889900',
        ]);

        $this->branchA2 = Branch::create([
            'company_id' => $this->companyA->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Gulberg Showroom',
            'code' => 'GLB-02',
            'is_main' => false,
            'status' => 'active',
            'address' => 'Main Boulevard, Gulberg, Lahore',
            'city' => 'Lahore',
            'phone' => '04235771122',
        ]);

        $role = Role::where('name', 'company_admin')->firstOrFail();
        $this->userA = User::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'name' => 'Ajmal Kamboh',
            'email' => 'admin@kamboh.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // Company B
        $this->companyB = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor Electronics',
            'slug' => 'competitor-elec',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branchB1 = Branch::create([
            'company_id' => $this->companyB->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Mall Road Showroom',
            'code' => 'MLL-01',
            'is_main' => true,
            'status' => 'active',
            'address' => 'Mall Road, Lahore',
            'city' => 'Lahore',
        ]);

        $this->userB = User::create([
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'name' => 'Rival Manager',
            'email' => 'rival@competitor.com',
            'password' => Hash::make('Secret123!'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->categoryA = ProductCategory::create([
            'company_id' => $this->companyA->id,
            'name' => 'Air Conditioners',
            'code' => 'AC-01',
            'slug' => 'air-conditioners',
            'status' => 'active',
        ]);

        $supplierA = Supplier::create([
            'company_id' => $this->companyA->id,
            'name' => 'Haier Pakistan',
            'status' => 'active',
        ]);

        $this->productA = Product::create([
            'company_id' => $this->companyA->id,
            'category_id' => $this->categoryA->id,
            'supplier_id' => $supplierA->id,
            'brand' => 'Haier',
            'model_name' => 'Haier 1.5 Ton Inverter',
            'sku' => 'HAC-15T-INV',
            'base_cash_price' => 125000,
            'min_down_payment_pct' => 20,
            'is_serialized' => true,
            'is_active' => true,
        ]);

        $this->serialUnitA = SerializedItem::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'serial_number' => 'HR-INV-998877',
            'status' => 'in_stock',
            'purchase_cost' => 105000,
        ]);

        BranchInventory::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'product_id' => $this->productA->id,
            'quantity_on_hand' => 1,
            'quantity_available' => 1,
            'quantity_reserved' => 0,
        ]);
    }

    public function test_guest_is_redirected_to_login_on_all_transfer_routes(): void
    {
        $this->get(route('transfers.index'))->assertRedirect(route('login'));
        $this->get(route('transfers.create'))->assertRedirect(route('login'));
        $this->post(route('transfers.store'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_transfers_index(): void
    {
        $response = $this->actingAs($this->userA)->get(route('transfers.index'));

        $response->assertOk();
        $response->assertSee('Showroom Stock Transfers &amp; Gate Passes', false);
        $response->assertSee('Active Transfers');
        $response->assertSee('In-Transit Appliances');
    }

    public function test_authenticated_user_can_view_create_transfer_form(): void
    {
        $response = $this->actingAs($this->userA)->get(route('transfers.create'));

        $response->assertOk();
        $response->assertSee('Create Showroom Stock Transfer Order');
        $response->assertSee('Ferozepur Road Showroom');
        $response->assertSee('Gulberg Showroom');
        $response->assertSee('HR-INV-998877');
    }

    public function test_user_can_store_transfer_request(): void
    {
        $payload = [
            'source_branch_id' => $this->branchA1->id,
            'destination_branch_id' => $this->branchA2->id,
            'notes' => 'Customer reserved unit at Gulberg showroom',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'serialized_item_id' => $this->serialUnitA->id,
                    'quantity' => 1,
                    'notes' => 'Factory box sealed',
                ],
            ],
        ];

        $response = $this->actingAs($this->userA)->post(route('transfers.store'), $payload);

        $transfer = InventoryTransfer::first();
        $this->assertNotNull($transfer);
        $this->assertEquals('requested', $transfer->status);
        $this->assertEquals(1, $transfer->total_items_count);

        $response->assertRedirect(route('transfers.show', $transfer));
        $response->assertSessionHas('success');
    }

    public function test_user_can_approve_transfer(): void
    {
        $transfer = app(\App\Services\Inventory\TransferService::class)->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );

        $response = $this->actingAs($this->userA)->post(route('transfers.approve', $transfer));

        $response->assertRedirect(route('transfers.show', $transfer));
        $this->assertEquals('approved', $transfer->fresh()->status);
    }

    public function test_user_can_dispatch_transfer_with_driver_and_vehicle(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transfer = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );
        $service->approveTransfer($transfer, $this->userA->id);

        $dispatchPayload = [
            'driver_name' => 'Muhammad Rashid',
            'driver_cnic' => '35201-7788990-1',
            'driver_phone' => '03009988776',
            'vehicle_number' => 'LEA-24-9182',
            'transport_company' => 'Showroom Transit Vehicle',
            'gate_pass_notes' => 'Driver verified with national identity card',
        ];

        $response = $this->actingAs($this->userA)->post(route('transfers.dispatch', $transfer), $dispatchPayload);

        $response->assertRedirect(route('transfers.show', $transfer));
        $transfer->refresh();

        $this->assertEquals('dispatched', $transfer->status);
        $this->assertNotNull($transfer->gate_pass_number);
        $this->assertStringStartsWith('GP-', $transfer->gate_pass_number);
        $this->assertEquals('Muhammad Rashid', $transfer->driver_name);
        $this->assertEquals('LEA-24-9182', $transfer->vehicle_number);

        // Hardware unit set to in_transit
        $this->assertEquals('in_transit', $this->serialUnitA->fresh()->status);
    }

    public function test_user_can_receive_transfer_and_inspect_items(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transfer = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );
        $service->approveTransfer($transfer, $this->userA->id);
        $service->dispatchTransfer($transfer, [
            'driver_name' => 'Rashid Khan',
            'driver_cnic' => '35201-1122334-1',
            'driver_phone' => '03001122334',
            'vehicle_number' => 'LHE-20-5544',
        ], $this->userA->id);

        $transferItem = $transfer->items()->first();

        $receivePayload = [
            'items' => [
                $transferItem->id => [
                    'condition' => 'good',
                    'notes' => 'Carton seal checked and approved by storekeeper',
                ],
            ],
        ];

        $response = $this->actingAs($this->userA)->post(route('transfers.receive', $transfer), $receivePayload);

        $response->assertRedirect(route('transfers.show', $transfer));
        $transfer->refresh();

        $this->assertEquals('received', $transfer->status);
        $this->assertEquals(1, $transfer->total_received_count);

        // Serialized unit now belongs to Branch A2 and is in_stock
        $this->assertEquals($this->branchA2->id, $this->serialUnitA->fresh()->branch_id);
        $this->assertEquals('in_stock', $this->serialUnitA->fresh()->status);
    }

    public function test_user_can_view_printable_security_gate_pass(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transfer = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );
        $service->approveTransfer($transfer, $this->userA->id);
        $service->dispatchTransfer($transfer, [
            'driver_name' => 'Muhammad Rashid',
            'driver_cnic' => '35201-7788990-1',
            'driver_phone' => '03009988776',
            'vehicle_number' => 'LEA-24-9182',
        ], $this->userA->id);

        $response = $this->actingAs($this->userA)->get(route('transfers.gate-pass', $transfer));

        $response->assertOk();
        $response->assertSee('OUTWARD SECURITY GATE PASS / INTER-BRANCH DELIVERY CHALLAN');
        $response->assertSee($transfer->gate_pass_number);
        $response->assertSee('Muhammad Rashid');
        $response->assertSee('35201-7788990-1');
        $response->assertSee('LEA-24-9182');
        $response->assertSee('HR-INV-998877');
        $response->assertSee('Security Guard (Showroom Exit Check)');
    }

    public function test_gate_pass_forbidden_for_undispatched_transfer(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transfer = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );

        $response = $this->actingAs($this->userA)->get(route('transfers.gate-pass', $transfer));

        $response->assertStatus(400);
    }

    public function test_user_can_cancel_transfer(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transfer = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );

        $response = $this->actingAs($this->userA)->post(route('transfers.cancel', $transfer), [
            'rejection_reason' => 'Customer decided to buy in cash at origin showroom',
        ]);

        $response->assertRedirect(route('transfers.show', $transfer));
        $this->assertEquals('cancelled', $transfer->fresh()->status);
    }

    public function test_tenant_isolation_prevents_viewing_other_company_transfers(): void
    {
        $service = app(\App\Services\Inventory\TransferService::class);
        $transferA = $service->createTransfer(
            $this->companyA,
            $this->branchA1->id,
            $this->branchA2->id,
            [['product_id' => $this->productA->id, 'serialized_item_id' => $this->serialUnitA->id]],
            $this->userA->id
        );

        // User B from Company B attempts to view Company A's transfer
        $response = $this->actingAs($this->userB)->get(route('transfers.show', $transferA));
        $response->assertForbidden();

        // User B index does not list Company A's transfer
        $indexResponse = $this->actingAs($this->userB)->get(route('transfers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee($transferA->transfer_number);
    }
}
