<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionAssignment extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'installment_agreement_id',
        'collection_officer_id',
        'assigned_by_id',
        'assigned_date',
        'status',
        'priority',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(InstallmentAgreement::class, 'installment_agreement_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function collectionOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collection_officer_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByOfficer(Builder $query, int $officerId): Builder
    {
        return $query->where('collection_officer_id', $officerId);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-clock-history me-1"></i>Active</span>',
            'completed' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-check2-all me-1"></i>Completed</span>',
            'reassigned' => '<span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-arrow-left-right me-1"></i>Reassigned</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => '<span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>',
            'high' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>High</span>',
            default => '<span class="badge bg-light text-dark border">Normal</span>',
        };
    }
}
