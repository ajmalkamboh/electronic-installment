<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditApproval extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'credit_assessment_id',
        'customer_id',
        'approved_by_user_id',
        'approval_level',
        'decision',
        'authorized_credit_limit',
        'conditions_imposed',
        'approval_notes',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_credit_limit' => 'decimal:2',
            'decided_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CreditApproval $approval) {
            if (empty($approval->decided_at)) {
                $approval->decided_at = now();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creditAssessment(): BelongsTo
    {
        return $this->belongsTo(CreditAssessment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isApproved(): bool
    {
        return in_array($this->decision, ['approved', 'conditional']);
    }
}
