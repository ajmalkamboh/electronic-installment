<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecoveryCase extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'customer_id',
        'case_number',
        'stage',
        'status',
        'days_past_due',
        'total_overdue_amount',
        'total_late_fees',
        'assigned_officer_id',
        'escalated_by_id',
        'warning_notice_at',
        'warning_notice_ref',
        'legal_notice_at',
        'legal_notice_ref',
        'repossession_authorized_at',
        'repossession_authorized_by_id',
        'repossessed_at',
        'repossessed_by_id',
        'repossessed_condition',
        'repossession_notes',
        'written_off_at',
        'written_off_by_id',
        'written_off_amount',
        'written_off_reason',
        'settled_at',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'days_past_due' => 'integer',
            'total_overdue_amount' => 'decimal:2',
            'total_late_fees' => 'decimal:2',
            'written_off_amount' => 'decimal:2',
            'warning_notice_at' => 'datetime',
            'legal_notice_at' => 'datetime',
            'repossession_authorized_at' => 'datetime',
            'repossessed_at' => 'datetime',
            'written_off_at' => 'datetime',
            'settled_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by_id');
    }

    public function repossessionAuthorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repossession_authorized_by_id');
    }

    public function repossessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'repossessed_by_id');
    }

    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(RecoveryNotice::class)->latest('issued_at');
    }

    public function getStageBadgeAttribute(): string
    {
        return match ($this->stage) {
            'grace_period' => '<span class="badge bg-secondary"><i class="bi bi-clock me-1"></i>Grace Period (1-5d)</span>',
            'overdue_reminder' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Reminder (6-14d)</span>',
            'tele_collection' => '<span class="badge bg-info text-dark"><i class="bi bi-telephone me-1"></i>Tele-Collection (15-29d)</span>',
            'field_recovery' => '<span class="badge bg-primary"><i class="bi bi-geo-alt me-1"></i>Field Recovery (30-59d)</span>',
            'legal_notice' => '<span class="badge bg-danger"><i class="bi bi-shield-exclamation me-1"></i>Legal Notice (60-89d)</span>',
            'repossession_pending' => '<span class="badge bg-dark text-warning"><i class="bi bi-truck me-1"></i>Repossession (90d+)</span>',
            'repossessed' => '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Repossessed</span>',
            'written_off' => '<span class="badge bg-dark"><i class="bi bi-file-earmark-x me-1"></i>Written Off</span>',
            'resolved' => '<span class="badge bg-success"><i class="bi bi-check2-all me-1"></i>Resolved</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->stage) . '</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => '<span class="badge bg-info text-dark">Open</span>',
            'in_progress' => '<span class="badge bg-primary">In Progress</span>',
            'escalated' => '<span class="badge bg-danger">Escalated</span>',
            'repossessed' => '<span class="badge bg-warning text-dark">Repossessed</span>',
            'written_off' => '<span class="badge bg-secondary">Written Off</span>',
            'settled' => '<span class="badge bg-success">Settled</span>',
            'closed' => '<span class="badge bg-light text-dark border">Closed</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }
}
