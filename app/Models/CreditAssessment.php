<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CreditAssessment extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'assessed_by_user_id',
        'ulid',
        'monthly_income',
        'existing_debt_obligations',
        'proposed_installment_limit',
        'calculated_dti_percentage',
        'score',
        'risk_tier',
        'recommended_limit',
        'recommendation',
        'conditions_summary',
        'assessment_notes',
        'status',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
            'existing_debt_obligations' => 'decimal:2',
            'proposed_installment_limit' => 'decimal:2',
            'calculated_dti_percentage' => 'decimal:2',
            'score' => 'integer',
            'recommended_limit' => 'decimal:2',
            'assessed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CreditAssessment $assessment) {
            if (empty($assessment->ulid)) {
                $assessment->ulid = (string) Str::ulid();
            }
            if (empty($assessment->assessed_at)) {
                $assessment->assessed_at = now();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by_user_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(CreditApproval::class)->orderBy('decided_at', 'desc');
    }

    public function latestApproval(): HasOne
    {
        return $this->hasOne(CreditApproval::class)->latestOfMany('decided_at');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function dtiExceedsCap(): bool
    {
        return (float) $this->calculated_dti_percentage > 40.0;
    }
}
