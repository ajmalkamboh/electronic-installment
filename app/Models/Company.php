<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'slug',
        'legal_name',
        'ntn_strn',
        'phone',
        'email',
        'city',
        'address',
        'currency',
        'logo',
        'receipt_header',
        'receipt_footer',
        'terms_conditions',
        'grace_period_days',
        'late_fee_type',
        'late_fee_amount',
        'max_penalty_cap',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'grace_period_days' => 'integer',
            'late_fee_amount' => 'decimal:2',
            'max_penalty_cap' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . substr((string) Str::ulid(), -6);
            }
        });
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
