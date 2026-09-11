<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditProfile extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'customer_id',
        'company_id',
        'credit_score',
        'max_authorized_credit',
        'active_agreements_count',
        'completed_agreements_count',
        'total_dpd_days',
        'blacklisted_reason',
        'blacklisted_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_score' => 'integer',
            'max_authorized_credit' => 'decimal:2',
            'active_agreements_count' => 'integer',
            'completed_agreements_count' => 'integer',
            'total_dpd_days' => 'integer',
            'blacklisted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isBlacklisted(): bool
    {
        return !is_null($this->blacklisted_at) || $this->credit_score === 0;
    }
}
