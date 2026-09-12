<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'ulid',
        'name',
        'code',
        'city',
        'address',
        'phone',
        'email',
        'is_main',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'source_branch_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class, 'destination_branch_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
