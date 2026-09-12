<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SerializedItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'product_id',
        'supplier_id',
        'ulid',
        'imei_1',
        'imei_2',
        'serial_number',
        'asset_tag',
        'color',
        'status',
        'purchase_cost',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_cost' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SerializedItem $item) {
            if (empty($item->ulid)) {
                $item->ulid = (string) Str::ulid();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(InstallmentAgreement::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'in_stock';
    }

    public function isReserved(): bool
    {
        return $this->status === 'reserved';
    }

    public function isDisbursed(): bool
    {
        return $this->status === 'disbursed';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function getIdentifierLabelAttribute(): string
    {
        if ($this->imei_1) {
            return "IMEI: {$this->imei_1}" . ($this->imei_2 ? " / {$this->imei_2}" : '');
        }

        if ($this->serial_number) {
            return "S/N: {$this->serial_number}";
        }

        return "Asset: {$this->asset_tag}";
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'in_stock' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>In Stock</span>',
            'in_transit' => '<span class="badge bg-primary"><i class="bi bi-truck me-1"></i>In Transit</span>',
            'reserved' => '<span class="badge bg-warning text-dark"><i class="bi bi-bookmark-check me-1"></i>Reserved</span>',
            'allocated' => '<span class="badge bg-info text-dark"><i class="bi bi-box-seam me-1"></i>Allocated</span>',
            'disbursed' => '<span class="badge bg-secondary"><i class="bi bi-person-check me-1"></i>Disbursed</span>',
            'repossessed' => '<span class="badge bg-danger"><i class="bi bi-arrow-return-left me-1"></i>Repossessed</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
