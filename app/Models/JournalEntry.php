<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'entry_number',
        'entry_date',
        'reference_type',
        'reference_id',
        'description',
        'posted_by_id',
        'status',
        'total_debit',
        'total_credit',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.001;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'posted' => '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Posted</span>',
            'draft' => '<span class="badge bg-warning text-dark"><i class="bi bi-pencil me-1"></i>Draft</span>',
            'void' => '<span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Void</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
