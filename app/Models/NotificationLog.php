<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'installment_agreement_id',
        'payment_id',
        'channel',
        'recipient_phone',
        'recipient_name',
        'template_code',
        'content',
        'status',
        'provider',
        'provider_reference',
        'provider_response',
        'error_message',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'delivered' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all me-1"></i>Delivered</span>',
            'sent' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-check2 me-1"></i>Sent</span>',
            'queued' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Queued</span>',
            'failed' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Failed</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getChannelBadgeAttribute(): string
    {
        return match ($this->channel) {
            'sms' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-chat-text-fill me-1"></i>SMS</span>',
            'whatsapp' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-whatsapp me-1"></i>WhatsApp</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->channel) . '</span>',
        };
    }
}
