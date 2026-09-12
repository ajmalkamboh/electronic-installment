<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentSchedule extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'markup_amount',
        'total_amount',
        'paid_amount',
        'remaining_balance',
        'late_fee_amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'due_date' => 'date',
            'principal_amount' => 'decimal:2',
            'markup_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function waivers(): HasMany
    {
        return $this->hasMany(LateFeeWaiver::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid';
    }

    public function isOverdue(?\Carbon\Carbon $asOfDate = null): bool
    {
        $checkDate = $asOfDate ?? now();
        return $this->status === 'overdue' || (! $this->isPaid() && $this->due_date->lt($checkDate->toDateString()));
    }

    public function daysOverdue(?\Carbon\Carbon $asOfDate = null): int
    {
        if ($this->isPaid()) {
            return 0;
        }

        $checkDate = $asOfDate ?? now();
        if ($this->due_date->gte($checkDate->toDateString())) {
            return 0;
        }

        return (int) $this->due_date->diffInDays($checkDate->toDateString());
    }

    public function isGracePeriodActive(?\Carbon\Carbon $asOfDate = null, int $gracePeriodDays = 5): bool
    {
        $days = $this->daysOverdue($asOfDate);
        return $days > 0 && $days <= $gracePeriodDays;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Paid</span>',
            'partially_paid' => '<span class="badge bg-info text-dark"><i class="bi bi-pie-chart me-1"></i>Partially Paid</span>',
            'due' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Due</span>',
            'overdue' => '<span class="badge bg-danger"><i class="bi bi-clock-history me-1"></i>Overdue</span>',
            default => '<span class="badge bg-light text-dark border">Pending</span>',
        };
    }
}
