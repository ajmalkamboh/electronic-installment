<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InstallmentAgreement extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'product_id',
        'serialized_item_id',
        'installment_plan_id',
        'credit_assessment_id',
        'creator_id',
        'approved_by_id',
        'disbursed_by_id',
        'ulid',
        'account_number',
        'status',
        'cash_price',
        'down_payment_amount',
        'down_payment_paid',
        'down_payment_receipt_ref',
        'down_payment_method',
        'financed_principal',
        'markup_rate_pct',
        'markup_amount',
        'total_financed',
        'total_payable',
        'installment_amount',
        'tenure_months',
        'installment_frequency',
        'total_installments',
        'paid_installments',
        'remaining_balance',
        'start_date',
        'first_due_date',
        'maturity_date',
        'submitted_at',
        'approved_at',
        'activated_at',
        'completed_at',
        'cancelled_at',
        'approval_notes',
        'handover_notes',
        'cancellation_reason',
        'terms_conditions_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'cash_price' => 'decimal:2',
            'down_payment_amount' => 'decimal:2',
            'down_payment_paid' => 'decimal:2',
            'financed_principal' => 'decimal:2',
            'markup_rate_pct' => 'decimal:2',
            'markup_amount' => 'decimal:2',
            'total_financed' => 'decimal:2',
            'total_payable' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'tenure_months' => 'integer',
            'total_installments' => 'integer',
            'paid_installments' => 'integer',
            'start_date' => 'date',
            'first_due_date' => 'date',
            'maturity_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InstallmentAgreement $agreement) {
            if (empty($agreement->ulid)) {
                $agreement->ulid = (string) Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serializedItem(): BelongsTo
    {
        return $this->belongsTo(SerializedItem::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function creditAssessment(): BelongsTo
    {
        return $this->belongsTo(CreditAssessment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by_id');
    }

    public function agreementGuarantors(): HasMany
    {
        return $this->hasMany(AgreementGuarantor::class);
    }

    public function guarantors(): BelongsToMany
    {
        return $this->belongsToMany(Guarantor::class, 'agreement_guarantors')
            ->withPivot('is_primary', 'relationship', 'verification_status')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class)->orderBy('installment_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function collectionAssignments(): HasMany
    {
        return $this->hasMany(CollectionAssignment::class)->latest();
    }

    public function activeAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CollectionAssignment::class)->where('status', 'active');
    }

    public function collectionLogs(): HasMany
    {
        return $this->hasMany(CollectionLog::class)->latest('visit_date');
    }

    public function latestCollectionLog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CollectionLog::class)->latestOfMany('visit_date');
    }

    public function nextDueSchedule(): ?InstallmentSchedule
    {
        return $this->schedules()
            ->whereIn('status', ['due', 'partially_paid', 'pending'])
            ->orderBy('installment_number')
            ->first();
    }

    public function overdueSchedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class)
            ->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['due', 'partially_paid'])
                    ->where('due_date', '<', now()->toDateString());
            })
            ->orderBy('installment_number');
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isDownPaymentSatisfied(): bool
    {
        return (float) $this->down_payment_paid >= (float) $this->down_payment_amount;
    }

    public function getDownPaymentDeficitAttribute(): float
    {
        return max(0.0, (float) $this->down_payment_amount - (float) $this->down_payment_paid);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'under_review' => '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Under Review</span>',
            'approved' => '<span class="badge bg-info text-dark"><i class="bi bi-check-circle me-1"></i>Approved</span>',
            'active' => '<span class="badge bg-success"><i class="bi bi-activity me-1"></i>Active</span>',
            'completed' => '<span class="badge bg-primary"><i class="bi bi-trophy me-1"></i>Completed</span>',
            'defaulted' => '<span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Defaulted</span>',
            'cancelled' => '<span class="badge bg-dark"><i class="bi bi-x-circle me-1"></i>Cancelled</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }
}
