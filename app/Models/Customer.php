<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use Auditable, BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'ulid',
        'cnic',
        'full_name',
        'father_or_husband_name',
        'gender',
        'mobile_primary',
        'mobile_secondary',
        'whatsapp_number',
        'email',
        'present_address',
        'permanent_address',
        'residence_type',
        'residence_tenure_years',
        'monthly_household_income',
        'utility_bill_ref_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'residence_tenure_years' => 'integer',
            'monthly_household_income' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Customer $customer) {
            if (empty($customer->ulid)) {
                $customer->ulid = (string) Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creditProfile(): HasOne
    {
        return $this->hasOne(CustomerCreditProfile::class);
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(Guarantor::class);
    }

    public function references(): HasMany
    {
        return $this->hasMany(PersonalReference::class, 'customer_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CustomerVerification::class)->orderBy('verified_at', 'desc');
    }

    public function verification(): HasOne
    {
        return $this->hasOne(CustomerVerification::class)->latestOfMany();
    }

    public function creditAssessments(): HasMany
    {
        return $this->hasMany(CreditAssessment::class)->orderBy('assessed_at', 'desc');
    }

    public function latestCreditAssessment(): HasOne
    {
        return $this->hasOne(CreditAssessment::class)->latestOfMany('assessed_at');
    }

    public function creditApprovals(): HasMany
    {
        return $this->hasMany(CreditApproval::class)->orderBy('decided_at', 'desc');
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(InstallmentAgreement::class)->orderBy('created_at', 'desc');
    }

    public function activeAgreements(): HasMany
    {
        return $this->agreements()->where('status', 'active');
    }

    public function isVerified(): bool
    {
        return $this->status === 'active' || $this->verifications()->where('outcome', 'approved')->exists();
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted';
    }

    public function isCreditApproved(): bool
    {
        return $this->latestCreditAssessment?->status === 'approved'
            || $this->creditApprovals()->whereIn('decision', ['approved', 'conditional'])->exists();
    }

    public function getCreditScoreAttribute(): int
    {
        return $this->creditProfile?->credit_score ?? 50;
    }

    public function getMaxAuthorizedCreditAttribute(): float
    {
        return (float) ($this->creditProfile?->max_authorized_credit ?? 150000.00);
    }
}
