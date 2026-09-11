<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InstallmentPlan extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'tenure_months',
        'markup_calculation_model',
        'default_markup_rate_pct',
        'fixed_markup_amount',
        'min_down_payment_pct',
        'installment_frequency',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tenure_months' => 'integer',
            'default_markup_rate_pct' => 'decimal:2',
            'fixed_markup_amount' => 'decimal:2',
            'min_down_payment_pct' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InstallmentPlan $plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByTenure(Builder $query, int $months): Builder
    {
        return $query->where('tenure_months', $months);
    }
}
