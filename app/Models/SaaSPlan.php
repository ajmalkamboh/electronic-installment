<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SaaSPlan extends Model
{
    use HasFactory;

    protected $table = 'saas_plans';

    protected $fillable = [
        'ulid',
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'max_users',
        'max_branches',
        'max_active_agreements',
        'max_monthly_transactions',
        'features',
        'is_active',
        'trial_days',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_users' => 'integer',
            'max_branches' => 'integer',
            'max_active_agreements' => 'integer',
            'max_monthly_transactions' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
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
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'saas_plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price_monthly');
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        return in_array($feature, $features, true) || in_array('*', $features, true);
    }

    public function canHaveUsers(int $count): bool
    {
        return $count < $this->max_users;
    }

    public function canHaveBranches(int $count): bool
    {
        return $count < $this->max_branches;
    }

    public function canHaveAgreements(int $count): bool
    {
        return $count < $this->max_active_agreements;
    }

    public function canHaveTransactions(int $count): bool
    {
        return $count < $this->max_monthly_transactions;
    }
}
