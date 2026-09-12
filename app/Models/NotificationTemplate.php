<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'channel',
        'subject',
        'body',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getChannelBadgeAttribute(): string
    {
        return match ($this->channel) {
            'sms' => '<span class="badge bg-primary"><i class="bi bi-chat-text me-1"></i>SMS</span>',
            'whatsapp' => '<span class="badge bg-success"><i class="bi bi-whatsapp me-1"></i>WhatsApp</span>',
            'both' => '<span class="badge bg-info text-dark"><i class="bi bi-broadcast me-1"></i>SMS & WhatsApp</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->channel) . '</span>',
        };
    }
}
