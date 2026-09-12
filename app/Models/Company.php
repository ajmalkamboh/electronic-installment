<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
                $model->slug = Str::slug($model->name).'-'.substr((string) Str::ulid(), -6);
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

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function notificationSetting(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function notificationTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function inventoryTransfers(): HasMany
    {
        return $this->hasMany(InventoryTransfer::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(InstallmentAgreement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscription ?? $this->subscriptions()->latest('id')->first();
    }

    public function currentPlan(): ?SaaSPlan
    {
        return $this->currentSubscription()?->plan;
    }

    public function hasFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        return $plan ? $plan->hasFeature($feature) : false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || $this->status === 'trial';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function activeUsersCount(): int
    {
        return $this->users()->where('status', 'active')->count();
    }

    public function activeBranchesCount(): int
    {
        return $this->branches()->where('status', 'active')->count();
    }

    public function activeAgreementsCount(): int
    {
        return $this->agreements()->whereIn('status', ['active', 'approved', 'disbursed', 'defaulted'])->count();
    }

    public function currentMonthTransactionsCount(): int
    {
        return $this->payments()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function canCreateUser(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return true;
        }

        return $this->activeUsersCount() < $plan->max_users;
    }

    public function canCreateBranch(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return true;
        }

        return $this->activeBranchesCount() < $plan->max_branches;
    }

    public function canCreateAgreement(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return true;
        }

        return $this->activeAgreementsCount() < $plan->max_active_agreements;
    }

    public function canRecordTransaction(): bool
    {
        $plan = $this->currentPlan();
        if (! $plan) {
            return true;
        }

        return $this->currentMonthTransactionsCount() < $plan->max_monthly_transactions;
    }

    /**
     * @return array<string, array{used: int, limit: int, percentage: int, is_maxed: bool}>
     */
    public function getQuotaUsage(): array
    {
        $plan = $this->currentPlan();

        $usersLimit = $plan ? $plan->max_users : 999;
        $branchesLimit = $plan ? $plan->max_branches : 999;
        $agreementsLimit = $plan ? $plan->max_active_agreements : 9999;
        $transactionsLimit = $plan ? $plan->max_monthly_transactions : 99999;

        $usersUsed = $this->activeUsersCount();
        $branchesUsed = $this->activeBranchesCount();
        $agreementsUsed = $this->activeAgreementsCount();
        $transactionsUsed = $this->currentMonthTransactionsCount();

        return [
            'users' => [
                'used' => $usersUsed,
                'limit' => $usersLimit,
                'percentage' => $usersLimit > 0 ? min(100, (int) round(($usersUsed / $usersLimit) * 100)) : 0,
                'is_maxed' => $usersUsed >= $usersLimit,
            ],
            'branches' => [
                'used' => $branchesUsed,
                'limit' => $branchesLimit,
                'percentage' => $branchesLimit > 0 ? min(100, (int) round(($branchesUsed / $branchesLimit) * 100)) : 0,
                'is_maxed' => $branchesUsed >= $branchesLimit,
            ],
            'agreements' => [
                'used' => $agreementsUsed,
                'limit' => $agreementsLimit,
                'percentage' => $agreementsLimit > 0 ? min(100, (int) round(($agreementsUsed / $agreementsLimit) * 100)) : 0,
                'is_maxed' => $agreementsUsed >= $agreementsLimit,
            ],
            'transactions' => [
                'used' => $transactionsUsed,
                'limit' => $transactionsLimit,
                'percentage' => $transactionsLimit > 0 ? min(100, (int) round(($transactionsUsed / $transactionsLimit) * 100)) : 0,
                'is_maxed' => $transactionsUsed >= $transactionsLimit,
            ],
        ];
    }
}
