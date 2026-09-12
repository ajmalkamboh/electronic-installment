<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'sms_driver',
        'sms_sender_id',
        'sms_api_key',
        'sms_api_secret',
        'sms_endpoint_url',
        'whatsapp_driver',
        'whatsapp_phone_number_id',
        'whatsapp_access_token',
        'whatsapp_business_account_id',
        'auto_receipt_sms',
        'auto_receipt_whatsapp',
        'auto_welcome_sms',
        'auto_reminder_days_before',
        'auto_overdue_sms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'auto_receipt_sms' => 'boolean',
            'auto_receipt_whatsapp' => 'boolean',
            'auto_welcome_sms' => 'boolean',
            'auto_reminder_days_before' => 'integer',
            'auto_overdue_sms' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
