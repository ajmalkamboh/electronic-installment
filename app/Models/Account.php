<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'type',
        'category',
        'normal_balance',
        'is_system',
        'is_active',
        'description',
        'current_balance',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'current_balance' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function journalEntryItems(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAssets($query)
    {
        return $query->where('type', 'asset');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('type', 'liability');
    }

    public function scopeEquity($query)
    {
        return $query->where('type', 'equity');
    }

    public function scopeRevenue($query)
    {
        return $query->where('type', 'revenue');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    // Helpers
    public function isDebit(): bool
    {
        return $this->normal_balance === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->normal_balance === 'credit';
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'asset' => '<span class="badge bg-primary">Asset</span>',
            'liability' => '<span class="badge bg-warning text-dark">Liability</span>',
            'equity' => '<span class="badge bg-info text-dark">Equity</span>',
            'revenue' => '<span class="badge bg-success">Revenue</span>',
            'expense' => '<span class="badge bg-danger">Expense</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'PKR ' . number_format($this->current_balance, 2);
    }
}
