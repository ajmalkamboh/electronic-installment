<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'customer_id',
        'collection_officer_id',
        'visit_date',
        'interaction_type',
        'interaction_status',
        'promise_to_pay_date',
        'promised_amount',
        'ptp_status',
        'location_notes',
        'notes',
        'follow_up_date',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
            'promise_to_pay_date' => 'date',
            'promised_amount' => 'decimal:2',
            'follow_up_date' => 'date',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collection_officer_id');
    }

    public function isPtp(): bool
    {
        return $this->interaction_status === 'promise_to_pay' || !empty($this->promise_to_pay_date);
    }

    public function isHonored(): bool
    {
        return $this->ptp_status === 'honored';
    }

    public function isBroken(): bool
    {
        return $this->ptp_status === 'broken';
    }

    public function getInteractionTypeBadgeAttribute(): string
    {
        return match ($this->interaction_type) {
            'field_visit' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-geo-alt me-1"></i>Field Visit</span>',
            'phone_call' => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-telephone me-1"></i>Phone Call</span>',
            'showroom_visit' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-shop me-1"></i>Showroom Visit</span>',
            'guarantor_contact' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-people me-1"></i>Guarantor Contact</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->interaction_type) . '</span>',
        };
    }

    public function getInteractionStatusBadgeAttribute(): string
    {
        return match ($this->interaction_status) {
            'met_customer' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-person-check me-1"></i>Met Customer</span>',
            'customer_absent' => '<span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-person-x me-1"></i>Customer Absent</span>',
            'promise_to_pay' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-calendar-event me-1"></i>Promise to Pay</span>',
            'refused_to_pay' => '<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i>Refused to Pay</span>',
            'dispute_raised' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-exclamation-octagon me-1"></i>Dispute Raised</span>',
            'payment_collected' => '<span class="badge bg-success text-white"><i class="bi bi-cash-coin me-1"></i>Payment Collected</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->interaction_status) . '</span>',
        };
    }

    public function getPtpBadgeAttribute(): ?string
    {
        if (!$this->isPtp()) {
            return null;
        }

        return match ($this->ptp_status) {
            'honored' => '<span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>PTP Honored</span>',
            'broken' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>PTP Broken</span>',
            'cancelled' => '<span class="badge bg-secondary"><i class="bi bi-slash-circle me-1"></i>PTP Cancelled</span>',
            default => '<span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>PTP Pending</span>',
        };
    }
}
