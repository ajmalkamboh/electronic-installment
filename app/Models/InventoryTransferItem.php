<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transfer_id',
        'product_id',
        'serialized_item_id',
        'quantity',
        'status',
        'received_condition',
        'item_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serializedItem(): BelongsTo
    {
        return $this->belongsTo(SerializedItem::class);
    }

    public function getConditionBadgeAttribute(): string
    {
        return match ($this->received_condition) {
            'good' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Good</span>',
            'damaged' => '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Damaged</span>',
            'missing' => '<span class="badge bg-dark"><i class="bi bi-question-circle me-1"></i>Missing</span>',
            default => '<span class="badge bg-secondary">Unchecked</span>',
        };
    }
}
