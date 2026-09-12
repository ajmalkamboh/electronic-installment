<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'saas_plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'grace_days',
        'amount_paid',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'grace_days' => 'integer',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaaSPlan::class, 'saas_plan_id');
    }

    public function isActive(): bool
    {
        if ($this->status === 'active') {
            return $this->ends_at === null || $this->ends_at->isFuture();
        }

        if ($this->status === 'trial') {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        return false;
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due' || ($this->status === 'active' && $this->ends_at && $this->ends_at->isPast());
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isInGracePeriod(): bool
    {
        $expiryDate = $this->ends_at ?? $this->trial_ends_at;

        if (! $expiryDate || $expiryDate->isFuture()) {
            return false;
        }

        $graceEnd = (clone $expiryDate)->addDays($this->grace_days ?? 7);

        return Carbon::now()->lessThanOrEqualTo($graceEnd);
    }

    public function daysRemaining(): int
    {
        $targetDate = $this->isTrial() ? $this->trial_ends_at : $this->ends_at;

        if (! $targetDate) {
            return 999;
        }

        if ($targetDate->isPast()) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($targetDate, false);
    }

    public function hasFeature(string $feature): bool
    {
        return $this->plan?->hasFeature($feature) ?? false;
    }
}
