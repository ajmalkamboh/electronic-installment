<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\Product;
use App\Models\SerializedItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Receive wholesale stock intake into a showroom branch.
     *
     * @param  array<int, array{imei_1?: string|null, imei_2?: string|null, serial_number?: string|null, asset_tag?: string|null, color?: string|null, purchase_cost?: float|null}>  $unitsData
     * @return array<SerializedItem>
     */
    public function receiveStock(
        Branch $branch,
        Product $product,
        array $unitsData,
        ?Supplier $supplier,
        ?float $purchaseCost,
        User $user,
        ?string $reference = null
    ): array {
        if ($branch->company_id !== $product->company_id) {
            throw new \InvalidArgumentException('Branch and product must belong to the same company tenant.');
        }

        $companyId = $branch->company_id;

        return DB::transaction(function () use ($branch, $product, $unitsData, $supplier, $purchaseCost, $user, $reference, $companyId) {
            $createdItems = [];

            // Duplicate checks within company
            foreach ($unitsData as $unit) {
                if (! empty($unit['imei_1'])) {
                    $exists = SerializedItem::where('company_id', $companyId)
                        ->where('imei_1', trim($unit['imei_1']))
                        ->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            'imei_1' => "IMEI 1 '{$unit['imei_1']}' already exists in inventory for this company.",
                        ]);
                    }
                }

                if (! empty($unit['serial_number'])) {
                    $exists = SerializedItem::where('company_id', $companyId)
                        ->where('serial_number', trim($unit['serial_number']))
                        ->exists();
                    if ($exists) {
                        throw ValidationException::withMessages([
                            'serial_number' => "Serial Number '{$unit['serial_number']}' already exists in inventory for this company.",
                        ]);
                    }
                }

                $cost = isset($unit['purchase_cost']) && $unit['purchase_cost'] > 0
                    ? (float) $unit['purchase_cost']
                    : $purchaseCost;

                $item = SerializedItem::create([
                    'company_id' => $companyId,
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'supplier_id' => $supplier?->id,
                    'imei_1' => ! empty($unit['imei_1']) ? trim($unit['imei_1']) : null,
                    'imei_2' => ! empty($unit['imei_2']) ? trim($unit['imei_2']) : null,
                    'serial_number' => ! empty($unit['serial_number']) ? trim($unit['serial_number']) : null,
                    'asset_tag' => ! empty($unit['asset_tag']) ? trim($unit['asset_tag']) : null,
                    'color' => ! empty($unit['color']) ? trim($unit['color']) : null,
                    'status' => 'in_stock',
                    'purchase_cost' => $cost,
                    'received_at' => now(),
                ]);

                // Create stock movement record for each item
                StockMovement::create([
                    'company_id' => $companyId,
                    'product_id' => $product->id,
                    'serialized_item_id' => $item->id,
                    'source_branch_id' => null,
                    'destination_branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'movement_type' => 'purchase_receipt',
                    'quantity' => 1,
                    'reference_number' => $reference,
                    'notes' => 'Wholesale stock intake received at ' . $branch->name,
                ]);

                $createdItems[] = $item;
            }

            // Synchronize branch inventory counts
            $inventory = BranchInventory::firstOrCreate(
                ['branch_id' => $branch->id, 'product_id' => $product->id],
                ['company_id' => $companyId, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'quantity_available' => 0]
            );

            $quantityAdded = count($createdItems);
            $inventory->increment('quantity_on_hand', $quantityAdded);
            $inventory->quantity_available = $inventory->quantity_on_hand - $inventory->quantity_reserved;
            $inventory->save();

            return $createdItems;
        });
    }

    /**
     * Transfer a physical serialized unit between showroom branches.
     */
    public function transferSerializedItem(
        SerializedItem $item,
        Branch $destinationBranch,
        User $user,
        ?string $notes = null
    ): SerializedItem {
        if ($item->company_id !== $destinationBranch->company_id) {
            throw new \InvalidArgumentException('Destination branch must belong to the same company tenant.');
        }

        if ($item->branch_id === $destinationBranch->id) {
            throw new \InvalidArgumentException('Source and destination branches cannot be the same showroom.');
        }

        if ($item->status !== 'in_stock') {
            throw new \IllegalStateException("Only units in 'in_stock' status can be transferred. Current status: {$item->status}.");
        }

        $sourceBranch = $item->branch;

        return DB::transaction(function () use ($item, $sourceBranch, $destinationBranch, $user, $notes) {
            // 1. Decrement source showroom inventory
            $sourceInv = BranchInventory::where('branch_id', $sourceBranch->id)
                ->where('product_id', $item->product_id)
                ->first();
            if ($sourceInv && $sourceInv->quantity_on_hand > 0) {
                $sourceInv->decrement('quantity_on_hand');
                $sourceInv->quantity_available = max(0, $sourceInv->quantity_on_hand - $sourceInv->quantity_reserved);
                $sourceInv->save();
            }

            // 2. Increment destination showroom inventory
            $destInv = BranchInventory::firstOrCreate(
                ['branch_id' => $destinationBranch->id, 'product_id' => $item->product_id],
                ['company_id' => $item->company_id, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'quantity_available' => 0]
            );
            $destInv->increment('quantity_on_hand');
            $destInv->quantity_available = $destInv->quantity_on_hand - $destInv->quantity_reserved;
            $destInv->save();

            // 3. Update item showroom location
            $item->update(['branch_id' => $destinationBranch->id]);

            // 4. Log transfer out and transfer in movements
            StockMovement::create([
                'company_id' => $item->company_id,
                'product_id' => $item->product_id,
                'serialized_item_id' => $item->id,
                'source_branch_id' => $sourceBranch->id,
                'destination_branch_id' => $destinationBranch->id,
                'user_id' => $user->id,
                'movement_type' => 'branch_transfer_out',
                'quantity' => 1,
                'reference_number' => 'TRF-' . strtoupper(substr($item->ulid, -8)),
                'notes' => $notes ?? "Showroom transfer from {$sourceBranch->name} to {$destinationBranch->name}",
            ]);

            StockMovement::create([
                'company_id' => $item->company_id,
                'product_id' => $item->product_id,
                'serialized_item_id' => $item->id,
                'source_branch_id' => $sourceBranch->id,
                'destination_branch_id' => $destinationBranch->id,
                'user_id' => $user->id,
                'movement_type' => 'branch_transfer_in',
                'quantity' => 1,
                'reference_number' => 'TRF-' . strtoupper(substr($item->ulid, -8)),
                'notes' => $notes ?? "Received transfer from {$sourceBranch->name} at {$destinationBranch->name}",
            ]);

            return $item->fresh();
        });
    }

    /**
     * Recompute aggregated ground-truth inventory counts for a product at a branch.
     */
    public function syncBranchStock(Branch $branch, Product $product): BranchInventory
    {
        $onHandCount = SerializedItem::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereIn('status', ['in_stock', 'reserved', 'allocated'])
            ->count();

        $reservedCount = SerializedItem::where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereIn('status', ['reserved', 'allocated'])
            ->count();

        $inventory = BranchInventory::firstOrCreate(
            ['branch_id' => $branch->id, 'product_id' => $product->id],
            ['company_id' => $branch->company_id, 'quantity_on_hand' => 0, 'quantity_reserved' => 0, 'quantity_available' => 0]
        );

        $inventory->update([
            'quantity_on_hand' => $onHandCount,
            'quantity_reserved' => $reservedCount,
            'quantity_available' => max(0, $onHandCount - $reservedCount),
        ]);

        return $inventory->fresh();
    }
}
