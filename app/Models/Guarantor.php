<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Guarantor extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'ulid',
        'cnic',
        'full_name',
        'relationship',
        'occupation',
        'employer_name',
        'monthly_income',
        'mobile',
        'address',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
            'is_verified' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Guarantor $guarantor) {
            if (empty($guarantor->ulid)) {
                $guarantor->ulid = (string) Str::ulid();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
