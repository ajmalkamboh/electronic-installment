<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecoveryNotice extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'recovery_case_id',
        'installment_agreement_id',
        'notice_number',
        'notice_type',
        'recipient_type',
        'recipient_name',
        'recipient_contact',
        'recipient_address',
        'overdue_amount',
        'late_fees_amount',
        'total_demand_amount',
        'demand_deadline',
        'issued_at',
        'issued_by_id',
        'delivery_channel',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'overdue_amount' => 'decimal:2',
            'late_fees_amount' => 'decimal:2',
            'total_demand_amount' => 'decimal:2',
            'demand_deadline' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recoveryCase(): BelongsTo
    {
        return $this->belongsTo(RecoveryCase::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->notice_type) {
            'reminder_notice' => '<span class="badge bg-secondary">Friendly Reminder</span>',
            'formal_overdue_notice' => '<span class="badge bg-warning text-dark">Formal Overdue Notice</span>',
            'guarantor_notice' => '<span class="badge bg-info text-dark">Guarantor Demand</span>',
            'final_demand_notice' => '<span class="badge bg-danger">Final Demand Notice</span>',
            'legal_notice' => '<span class="badge bg-dark text-danger"><i class="bi bi-shield-shaded me-1"></i>Legal Notice</span>',
            'repossession_warrant' => '<span class="badge bg-danger"><i class="bi bi-truck me-1"></i>Repossession Warrant</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->notice_type) . '</span>',
        };
    }
}
