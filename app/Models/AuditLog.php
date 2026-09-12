<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (! $model->created_at) {
                $model->created_at = now();
            }
            if (empty($model->attributes['action']) && ! empty($model->attributes['event'])) {
                $model->attributes['action'] = $model->attributes['event'];
                unset($model->attributes['event']);
            }
            if (empty($model->attributes['action'])) {
                $model->attributes['action'] = 'general';
            }
        });

        // Enforce immutability (NFR-02)
        static::updating(function () {
            throw new RuntimeException('AuditLog records are strictly immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('AuditLog records cannot be deleted.');
        });
    }

    public function getEventAttribute(): ?string
    {
        return $this->attributes['action'] ?? null;
    }

    public function setEventAttribute(?string $value): void
    {
        $this->attributes['action'] = $value;
    }

    public function getDiffAttribute(): array
    {
        return $this->getDiff();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get a human-readable entity name.
     */
    public function getEntityName(): string
    {
        return class_basename($this->auditable_type);
    }

    /**
     * Format changed attributes comparison.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getDiff(): array
    {
        $diff = [];
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($keys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if ($oldVal !== $newVal) {
                $diff[$key] = [
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        return $diff;
    }
}
