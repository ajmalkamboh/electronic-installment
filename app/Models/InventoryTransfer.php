<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryTransfer extends Model
{
    use Auditable, BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'source_branch_id',
        'destination_branch_id',
        'ulid',
        'transfer_number',
        'status',
        'created_by_id',
        'approved_by_id',
        'dispatched_by_id',
        'received_by_id',
        'driver_name',
        'driver_cnic',
        'driver_phone',
        'vehicle_number',
        'transport_company',
        'gate_pass_number',
        'gate_pass_generated_at',
        'gate_pass_notes',
        'total_items_count',
        'total_received_count',
        'dispatched_at',
        'received_at',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'gate_pass_generated_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'total_items_count' => 'integer',
            'total_received_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InventoryTransfer $transfer) {
            if (empty($transfer->ulid)) {
                $transfer->ulid = (string) Str::ulid();
            }
        });
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransferItem::class, 'inventory_transfer_id');
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'requested'], true);
    }

    public function canBeDispatched(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'dispatched';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'requested', 'approved'], true);
    }

    public function isGatePassReady(): bool
    {
        return in_array($this->status, ['dispatched', 'received'], true) && ! empty($this->gate_pass_number);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge bg-secondary"><i class="bi bi-pencil-square me-1"></i>Draft</span>',
            'requested' => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Requested</span>',
            'approved' => '<span class="badge bg-info text-dark"><i class="bi bi-hand-thumbs-up me-1"></i>Approved</span>',
            'dispatched' => '<span class="badge bg-primary"><i class="bi bi-truck me-1"></i>In Transit</span>',
            'received' => '<span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Received</span>',
            'rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>',
            'cancelled' => '<span class="badge bg-dark"><i class="bi bi-slash-circle me-1"></i>Cancelled</span>',
            default => '<span class="badge bg-secondary">'.ucfirst($this->status).'</span>',
        };
    }
}
