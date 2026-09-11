<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'customer_id',
        'cashier_id',
        'collector_id',
        'ulid',
        'payment_number',
        'amount',
        'payment_method',
        'reference_number',
        'payment_date',
        'status',
        'late_fee_paid',
        'principal_paid',
        'markup_paid',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'late_fee_paid' => 'decimal:2',
            'principal_paid' => 'decimal:2',
            'markup_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->ulid)) {
                $payment->ulid = (string) Str::ulid();
            }
        });
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getMethodBadgeAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-cash me-1"></i>Cash</span>',
            'bank_transfer' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-bank me-1"></i>Bank Transfer</span>',
            'raast' => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-lightning-charge me-1"></i>Raast</span>',
            'easypaisa' => '<span class="badge bg-success text-white"><i class="bi bi-phone me-1"></i>Easypaisa</span>',
            'jazzcash' => '<span class="badge bg-danger text-white"><i class="bi bi-phone me-1"></i>JazzCash</span>',
            'cheque' => '<span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-card-checklist me-1"></i>Cheque</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->payment_method) . '</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'acknowledged' => '<span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Acknowledged</span>',
            'submitted' => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Submitted</span>',
            'reversed' => '<span class="badge bg-danger"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversed</span>',
            'rejected' => '<span class="badge bg-dark"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }
}
