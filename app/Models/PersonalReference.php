<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalReference extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'references';

    protected $fillable = [
        'company_id',
        'customer_id',
        'full_name',
        'relationship',
        'mobile',
        'address',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
