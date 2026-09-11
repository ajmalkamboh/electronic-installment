<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVerification extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'verified_by_user_id',
        'verification_type',
        'residence_confirmed',
        'workplace_confirmed',
        'investigator_notes',
        'outcome',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'residence_confirmed' => 'boolean',
            'workplace_confirmed' => 'boolean',
            'verified_at' => 'datetime',
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

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
