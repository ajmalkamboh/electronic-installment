<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'installment_schedule_id',
        'amount_allocated',
        'late_fee_component',
        'principal_component',
        'markup_component',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
            'late_fee_component' => 'decimal:2',
            'principal_component' => 'decimal:2',
            'markup_component' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }
}
