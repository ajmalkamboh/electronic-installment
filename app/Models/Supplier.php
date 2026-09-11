<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'ulid',
        'name',
        'contact_person',
        'phone',
        'email',
        'ntn_number',
        'address',
        'city',
        'balance_payable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance_payable' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Supplier $supplier) {
            if (empty($supplier->ulid)) {
                $supplier->ulid = (string) Str::ulid();
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function serializedItems(): HasMany
    {
        return $this->hasMany(SerializedItem::class);
    }
}
