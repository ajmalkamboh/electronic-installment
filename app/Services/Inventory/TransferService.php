<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Company;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferService
{
    /**
     * Generate sequential Transfer Order Number: TRF-YYYYMM-XXXX
     */
    public function generateTransferNumber(int $companyId): string
    {
        $prefix = 'TRF-' . date('Ym') . '-';
        $lastTransfer = InventoryTransfer::where('company_id', $companyId)
            ->where('transfer_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastTransfer) {
            $parts = explode('-', $lastTransfer->transfer_number);
            $nextSeq = (int) end($parts) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate sequential Security Gate Pass Number: GP-YYYYMM-XXXX
     */
    public function generateGatePassNumber(int $companyId): string
    {
        $prefix = 'GP-' . date('Ym') . '-';
        $lastPass = InventoryTransfer::where('company_id', $companyId)
            ->where('gate_pass_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastPass && $lastPass->gate_pass_number) {
            $parts = explode('-', $lastPass->gate_pass_number);
            $nextSeq = (int) end($parts) + 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new Multi-Branch Transfer Request.
     */
    public function createTransfer(
        Company $company,
        int $sourceBranchId,
        int $destinationBranchId,
        array $itemsData,
        int $userId,
        ?string $notes = null,
        string $status = 'requested'
    ): InventoryTransfer {
        if ($sourceBranchId === $destinationBranchId) {
            throw new InvalidArgumentException('Source showroom and destination showroom cannot be identical.');
        }

        if (empty($itemsData)) {
            throw new InvalidArgumentException('Transfer order must include at least one hardware item.');
        }

        return DB::transaction(function () use ($company, $sourceBranchId, $destinationBranchId, $itemsData, $userId, $notes, $status) {
            $transferNumber = $this->generateTransferNumber($company->id);

            $transfer = InventoryTransfer::create([
                'company_id' => $company->id,
                'source_branch_id' => $sourceBranchId,
                'destination_branch_id' => $destinationBranchId,
                'transfer_number' => $transferNumber,
                'status' => in_array($status, ['draft', 'requested'], true) ? $status : 'requested',
                'created_by_id' => $userId,
                'notes' => $notes,
                'total_items_count' => count($itemsData),
            ]);

            foreach ($itemsData as $item) {
                $productId = $item['product_id'];
                $serializedItemId = $item['serialized_item_id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 1);

                if ($serializedItemId) {
                    $serialUnit = SerializedItem::where('company_id', $company->id)
                        ->where('branch_id', $sourceBranchId)
                        ->where('id', $serializedItemId)
                        ->first();

                    if (!$serialUnit) {
                        throw new InvalidArgumentException("Hardware item ID {$serializedItemId} does not exist at the source showroom.");
                    }

                    if ($serialUnit->status !== 'in_stock') {
                        throw new InvalidArgumentException("Item {$serialUnit->identifier_label} is not available for transfer (Status: {$serialUnit->status}).");
                    }

                    $productId = $serialUnit->product_id;
                    $quantity = 1;
                }

                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'product_id' => $productId,
                    'serialized_item_id' => $serializedItemId,
                    'quantity' => $quantity,
                    'status' => 'pending',
                    'item_notes' => $item['notes'] ?? null,
                ]);
            }

            return $transfer->load(['items.product', 'items.serializedItem', 'sourceBranch', 'destinationBranch', 'creator']);
        });
    }

    /**
     * Approve Transfer Order.
     */
    public function approveTransfer(InventoryTransfer $transfer, int $userId): InventoryTransfer
    {
        if (!$transfer->canBeApproved()) {
            throw new InvalidArgumentException("Transfer {$transfer->transfer_number} cannot be approved in status '{$transfer->status}'.");
        }

        $transfer->update([
            'status' => 'approved',
            'approved_by_id' => $userId,
        ]);

        return $transfer->fresh(['sourceBranch', 'destinationBranch', 'approver']);
    }

    /**
     * Dispatch Transfer Order, generate Outward Security Gate Pass, set items in-transit, and deduct source inventory.
     */
    public function dispatchTransfer(InventoryTransfer $transfer, array $logisticsData, int $userId): InventoryTransfer
    {
        if (!$transfer->canBeDispatched()) {
            throw new InvalidArgumentException("Transfer {$transfer->transfer_number} must be 'approved' before dispatch.");
        }

        return DB::transaction(function () use ($transfer, $logisticsData, $userId) {
            $gatePassNumber = $transfer->gate_pass_number ?? $this->generateGatePassNumber($transfer->company_id);

            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by_id' => $userId,
                'dispatched_at' => Carbon::now(),
                'driver_name' => $logisticsData['driver_name'] ?? null,
                'driver_cnic' => $logisticsData['driver_cnic'] ?? null,
                'driver_phone' => $logisticsData['driver_phone'] ?? null,
                'vehicle_number' => $logisticsData['vehicle_number'] ?? null,
                'transport_company' => $logisticsData['transport_company'] ?? 'Showroom Transit',
                'gate_pass_number' => $gatePassNumber,
                'gate_pass_generated_at' => Carbon::now(),
                'gate_pass_notes' => $logisticsData['gate_pass_notes'] ?? null,
            ]);

            foreach ($transfer->items as $transferItem) {
                $transferItem->update(['status' => 'dispatched']);

                if ($transferItem->serialized_item_id) {
                    $serialUnit = SerializedItem::find($transferItem->serialized_item_id);

                    if (!$serialUnit || $serialUnit->status !== 'in_stock') {
                        throw new InvalidArgumentException("Serialized item {$serialUnit?->identifier_label} is no longer in stock at origin showroom.");
                    }

                    // Set unit to in_transit
                    $serialUnit->update(['status' => 'in_transit']);

                    // Deduct from Source Branch Inventory
                    $sourceInv = BranchInventory::firstOrCreate(
                        ['branch_id' => $transfer->source_branch_id, 'product_id' => $transferItem->product_id],
                        ['company_id' => $transfer->company_id, 'quantity_on_hand' => 0, 'quantity_available' => 0, 'quantity_reserved' => 0]
                    );

                    $sourceInv->decrement('quantity_on_hand', 1);
                    $sourceInv->decrement('quantity_available', 1);

                    // Record Stock Movement Outward
                    StockMovement::create([
                        'company_id' => $transfer->company_id,
                        'product_id' => $transferItem->product_id,
                        'serialized_item_id' => $serialUnit->id,
                        'source_branch_id' => $transfer->source_branch_id,
                        'destination_branch_id' => $transfer->destination_branch_id,
                        'user_id' => $userId,
                        'movement_type' => 'branch_transfer_out',
                        'quantity' => 1,
                        'reference_number' => $transfer->transfer_number,
                        'notes' => "Dispatched on Gate Pass {$gatePassNumber} to {$transfer->destinationBranch->name}. Driver: {$transfer->driver_name} ({$transfer->vehicle_number})",
                    ]);
                } else {
                    // Non-serialized item stock deduction
                    $qty = $transferItem->quantity;
                    $sourceInv = BranchInventory::firstOrCreate(
                        ['branch_id' => $transfer->source_branch_id, 'product_id' => $transferItem->product_id],
                        ['company_id' => $transfer->company_id, 'quantity_on_hand' => 0, 'quantity_available' => 0, 'quantity_reserved' => 0]
                    );

                    $sourceInv->decrement('quantity_on_hand', $qty);
                    $sourceInv->decrement('quantity_available', $qty);

                    StockMovement::create([
                        'company_id' => $transfer->company_id,
                        'product_id' => $transferItem->product_id,
                        'source_branch_id' => $transfer->source_branch_id,
                        'destination_branch_id' => $transfer->destination_branch_id,
                        'user_id' => $userId,
                        'movement_type' => 'branch_transfer_out',
                        'quantity' => $qty,
                        'reference_number' => $transfer->transfer_number,
                        'notes' => "Bulk stock dispatched on Gate Pass {$gatePassNumber} to {$transfer->destinationBranch->name}",
                    ]);
                }
            }

            return $transfer->fresh(['items.product', 'items.serializedItem', 'sourceBranch', 'destinationBranch', 'dispatcher']);
        });
    }

    /**
     * Receive Transfer Order at Destination Showroom, inspect IMEIs/condition, and accept stock.
     */
    public function receiveTransfer(InventoryTransfer $transfer, array $itemsVerification, int $userId): InventoryTransfer
    {
        if (!$transfer->canBeReceived()) {
            throw new InvalidArgumentException("Transfer {$transfer->transfer_number} cannot be received in status '{$transfer->status}'.");
        }

        return DB::transaction(function () use ($transfer, $itemsVerification, $userId) {
            $receivedCount = 0;

            foreach ($transfer->items as $transferItem) {
                $verification = $itemsVerification[$transferItem->id] ?? [];
                $condition = $verification['condition'] ?? 'good';
                $notes = $verification['notes'] ?? null;

                $isMissing = ($condition === 'missing');
                $itemStatus = $isMissing ? 'rejected' : ($condition === 'damaged' ? 'damaged' : 'received');

                $transferItem->update([
                    'status' => $itemStatus,
                    'received_condition' => $condition,
                    'item_notes' => $notes,
                ]);

                if (!$isMissing) {
                    $receivedCount++;

                    if ($transferItem->serialized_item_id) {
                        $serialUnit = SerializedItem::find($transferItem->serialized_item_id);

                        // Reallocate branch ownership and restore in_stock status
                        $serialUnit->update([
                            'branch_id' => $transfer->destination_branch_id,
                            'status' => 'in_stock',
                        ]);

                        // Add to Destination Branch Inventory
                        $destInv = BranchInventory::firstOrCreate(
                            ['branch_id' => $transfer->destination_branch_id, 'product_id' => $transferItem->product_id],
                            ['company_id' => $transfer->company_id, 'quantity_on_hand' => 0, 'quantity_available' => 0, 'quantity_reserved' => 0]
                        );

                        $destInv->increment('quantity_on_hand', 1);
                        $destInv->increment('quantity_available', 1);

                        // Record Inward Stock Movement
                        StockMovement::create([
                            'company_id' => $transfer->company_id,
                            'product_id' => $transferItem->product_id,
                            'serialized_item_id' => $serialUnit->id,
                            'source_branch_id' => $transfer->source_branch_id,
                            'destination_branch_id' => $transfer->destination_branch_id,
                            'user_id' => $userId,
                            'movement_type' => 'branch_transfer_in',
                            'quantity' => 1,
                            'reference_number' => $transfer->transfer_number,
                            'notes' => "Received from {$transfer->sourceBranch->name} on Gate Pass {$transfer->gate_pass_number}. Condition: {$condition}",
                        ]);
                    } else {
                        // Bulk non-serialized increment
                        $qty = $transferItem->quantity;
                        $destInv = BranchInventory::firstOrCreate(
                            ['branch_id' => $transfer->destination_branch_id, 'product_id' => $transferItem->product_id],
                            ['company_id' => $transfer->company_id, 'quantity_on_hand' => 0, 'quantity_available' => 0, 'quantity_reserved' => 0]
                        );

                        $destInv->increment('quantity_on_hand', $qty);
                        $destInv->increment('quantity_available', $qty);

                        StockMovement::create([
                            'company_id' => $transfer->company_id,
                            'product_id' => $transferItem->product_id,
                            'source_branch_id' => $transfer->source_branch_id,
                            'destination_branch_id' => $transfer->destination_branch_id,
                            'user_id' => $userId,
                            'movement_type' => 'branch_transfer_in',
                            'quantity' => $qty,
                            'reference_number' => $transfer->transfer_number,
                            'notes' => "Bulk stock received from {$transfer->sourceBranch->name} on Gate Pass {$transfer->gate_pass_number}",
                        ]);
                    }
                }
            }

            $transfer->update([
                'status' => 'received',
                'received_by_id' => $userId,
                'received_at' => Carbon::now(),
                'total_received_count' => $receivedCount,
            ]);

            return $transfer->fresh(['items.product', 'items.serializedItem', 'sourceBranch', 'destinationBranch', 'receiver']);
        });
    }

    /**
     * Cancel a transfer order and roll back stock if necessary.
     */
    public function cancelTransfer(InventoryTransfer $transfer, int $userId, string $reason): InventoryTransfer
    {
        if (!$transfer->canBeCancelled() && $transfer->status !== 'dispatched') {
            throw new InvalidArgumentException("Transfer in status '{$transfer->status}' cannot be cancelled.");
        }

        return DB::transaction(function () use ($transfer, $userId, $reason) {
            // If already dispatched, revert stock movements and serialized items back to origin
            if ($transfer->status === 'dispatched') {
                foreach ($transfer->items as $item) {
                    if ($item->serialized_item_id) {
                        $serialUnit = SerializedItem::find($item->serialized_item_id);
                        if ($serialUnit && $serialUnit->status === 'in_transit') {
                            $serialUnit->update(['status' => 'in_stock']);

                            $sourceInv = BranchInventory::firstOrCreate(
                                ['branch_id' => $transfer->source_branch_id, 'product_id' => $item->product_id],
                                ['company_id' => $transfer->company_id, 'quantity_on_hand' => 0, 'quantity_available' => 0, 'quantity_reserved' => 0]
                            );
                            $sourceInv->increment('quantity_on_hand', 1);
                            $sourceInv->increment('quantity_available', 1);

                            StockMovement::create([
                                'company_id' => $transfer->company_id,
                                'product_id' => $item->product_id,
                                'serialized_item_id' => $serialUnit->id,
                                'source_branch_id' => $transfer->destination_branch_id,
                                'destination_branch_id' => $transfer->source_branch_id,
                                'user_id' => $userId,
                                'movement_type' => 'manual_adjustment',
                                'quantity' => 1,
                                'reference_number' => $transfer->transfer_number,
                                'notes' => "Transfer cancelled. Reverted stock back to {$transfer->sourceBranch->name}. Reason: {$reason}",
                            ]);
                        }
                    }
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'rejection_reason' => $reason,
            ]);

            return $transfer->fresh(['sourceBranch', 'destinationBranch']);
        });
    }

    /**
     * High-level operational metrics for transfers overview.
     */
    public function getTransferMetrics(int $companyId, ?int $branchId = null): array
    {
        $baseQuery = InventoryTransfer::where('company_id', $companyId)
            ->when($branchId, fn($q) => $q->where(function ($sq) use ($branchId) {
                $sq->where('source_branch_id', $branchId)
                   ->orWhere('destination_branch_id', $branchId);
            }));

        $activeTransfers = (clone $baseQuery)->whereIn('status', ['requested', 'approved', 'dispatched'])->count();
        $inTransitCount = (clone $baseQuery)->where('status', 'dispatched')->count();
        $pendingApproval = (clone $baseQuery)->where('status', 'requested')->count();
        $receivedThisMonth = (clone $baseQuery)
            ->where('status', 'received')
            ->whereBetween('received_at', [Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth()])
            ->count();

        $inTransitUnits = (int) (clone $baseQuery)
            ->where('status', 'dispatched')
            ->sum('total_items_count');

        return [
            'active_transfers' => $activeTransfers,
            'in_transit_count' => $inTransitCount,
            'in_transit_units' => $inTransitUnits,
            'pending_approval' => $pendingApproval,
            'received_this_month' => $receivedThisMonth,
        ];
    }
}
