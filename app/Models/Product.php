<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'category_id',
        'supplier_id',
        'ulid',
        'brand',
        'model_name',
        'sku',
        'base_cash_price',
        'min_down_payment_pct',
        'is_serialized',
        'description',
        'specifications',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_cash_price' => 'decimal:2',
            'min_down_payment_pct' => 'decimal:2',
            'is_serialized' => 'boolean',
            'is_active' => 'boolean',
            'specifications' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->ulid)) {
                $product->ulid = (string) Str::ulid();
            }
            if (empty($product->sku)) {
                $brandPrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->brand), 0, 3));
                $modelPrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->model_name), 0, 4));
                $product->sku = "{$brandPrefix}-{$modelPrefix}-" . strtoupper(Str::random(4));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branchInventories(): HasMany
    {
        return $this->hasMany(BranchInventory::class);
    }

    public function serializedItems(): HasMany
    {
        return $this->hasMany(SerializedItem::class);
    }

    public function inStockSerializedItems(): HasMany
    {
        return $this->hasMany(SerializedItem::class)->where('status', 'in_stock');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model_name}";
    }

    public function getTotalStockOnHandAttribute(): int
    {
        return (int) $this->branchInventories()->sum('quantity_on_hand');
    }

    public function getTotalStockAvailableAttribute(): int
    {
        return (int) $this->branchInventories()->sum('quantity_available');
    }
}
