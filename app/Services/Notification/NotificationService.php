<?php

namespace App\Services\Notification;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentSchedule;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\User;
use App\Services\Notification\Contracts\NotificationDriverInterface;
use App\Services\Notification\Drivers\GenericHttpSmsDriver;
use App\Services\Notification\Drivers\LogNotificationDriver;
use App\Services\Notification\Drivers\MetaWhatsAppDriver;
use App\Services\Notification\Drivers\TwilioDriver;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Standard Pakistani Showroom Notification Templates.
     */
    public static array $defaultTemplates = [
        [
            'code' => 'welcome_agreement',
            'name' => 'Agreement Booking & Activation Welcome',
            'channel' => 'both',
            'subject' => 'Agreement Activation Welcome',
            'body' => "Dear {customer_name}, welcome to {company_name}! Your installment contract #{agreement_number} for {product_name} is activated. Monthly Installment: PKR {monthly_installment}, due on {due_day} of each month. Queries: {branch_phone}.",
        ],
        [
            'code' => 'payment_receipt',
            'name' => 'Payment Collection Receipt Confirmation',
            'channel' => 'both',
            'subject' => 'Payment Receipt Confirmation',
            'body' => "Dear {customer_name}, payment of PKR {amount} received on {payment_date} for Agreement #{agreement_number}. Receipt #{receipt_number}. Remaining Balance: PKR {remaining_balance}. Thank you, {company_name}.",
        ],
        [
            'code' => 'due_reminder',
            'name' => 'Upcoming Installment Due Date Reminder',
            'channel' => 'both',
            'subject' => 'Installment Due Reminder',
            'body' => "Dear {customer_name}, installment #{installment_number} of PKR {due_amount} for #{agreement_number} is due on {due_date}. Please deposit at {branch_name} ({branch_phone}) on time to prevent late surcharges.",
        ],
        [
            'code' => 'overdue_alert',
            'name' => 'Delinquent Installment Overdue Alert',
            'channel' => 'both',
            'subject' => 'Overdue Installment Urgent Notice',
            'body' => "URGENT: Dear {customer_name}, your installment of PKR {due_amount} for #{agreement_number} was due on {due_date} and is {days_overdue} days overdue. Late surcharge: PKR {late_fee}. Please clear at {branch_name} immediately.",
        ],
        [
            'code' => 'guarantor_notice',
            'name' => 'Guarantor Registration Notice',
            'channel' => 'sms',
            'subject' => 'Guarantor Registration Notice',
            'body' => "Dear {guarantor_name}, you have been verified as legal guarantor for {customer_name} under Installment Agreement #{agreement_number} ({product_name}) at {company_name}. Monthly installment: PKR {monthly_installment}.",
        ],
        [
            'code' => 'noc_clearance',
            'name' => 'Account Clearance & NOC Confirmation',
            'channel' => 'both',
            'subject' => 'Account Clearance NOC',
            'body' => "Congratulations {customer_name}! Your Installment Agreement #{agreement_number} at {company_name} is fully settled. NOC #{noc_number} has been generated. Please collect your clearance certificate from {branch_name}.",
        ],
    ];

    /**
     * Ensure default notification setting exists for a company.
     */
    public function getSetting(Company $company): NotificationSetting
    {
        return NotificationSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'sms_driver' => 'log',
                'sms_sender_id' => substr(preg_replace('/[^A-Za-z0-9]/', '', $company->name), 0, 11) ?: 'SHOWROOM',
                'whatsapp_driver' => 'log',
                'auto_receipt_sms' => true,
                'auto_receipt_whatsapp' => false,
                'auto_welcome_sms' => true,
                'auto_reminder_days_before' => 3,
                'auto_overdue_sms' => true,
                'is_active' => true,
            ]
        );
    }

    /**
     * Idempotently provision the 6 default templates for a company.
     */
    public function provisionDefaultTemplates(Company $company): void
    {
        foreach (self::$defaultTemplates as $tpl) {
            NotificationTemplate::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $tpl['code'],
                ],
                [
                    'name' => $tpl['name'],
                    'channel' => $tpl['channel'],
                    'subject' => $tpl['subject'],
                    'body' => $tpl['body'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Replace dynamic variable tokens in template text.
     */
    public function parseVariables(string $template, array $data): string
    {
        $search = [];
        $replace = [];

        foreach ($data as $key => $value) {
            $search[] = '{' . $key . '}';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $template);
    }

    /**
     * Resolve the driver implementation for SMS or WhatsApp based on settings.
     */
    public function resolveDriver(NotificationSetting $setting, string $channel): NotificationDriverInterface
    {
        $driverType = $channel === 'whatsapp' ? $setting->whatsapp_driver : $setting->sms_driver;

        return match ($driverType) {
            'generic_http' => app(GenericHttpSmsDriver::class),
            'twilio' => app(TwilioDriver::class),
            'meta_cloud' => app(MetaWhatsAppDriver::class),
            default => app(LogNotificationDriver::class),
        };
    }

    /**
     * Build driver configuration options.
     */
    public function buildDriverOptions(NotificationSetting $setting, string $channel): array
    {
        if ($channel === 'whatsapp') {
            if ($setting->whatsapp_driver === 'meta_cloud') {
                return [
                    'phone_number_id' => $setting->whatsapp_phone_number_id,
                    'access_token' => $setting->whatsapp_access_token,
                ];
            }
            if ($setting->whatsapp_driver === 'twilio') {
                return [
                    'sid' => $setting->sms_api_key,
                    'token' => $setting->sms_api_secret,
                    'from' => $setting->whatsapp_phone_number_id,
                    'channel' => 'whatsapp',
                ];
            }
        } else {
            if ($setting->sms_driver === 'generic_http') {
                return [
                    'endpoint_url' => $setting->sms_endpoint_url,
                    'api_key' => $setting->sms_api_key,
                    'api_secret' => $setting->sms_api_secret,
                    'sender_id' => $setting->sms_sender_id,
                ];
            }
            if ($setting->sms_driver === 'twilio') {
                return [
                    'sid' => $setting->sms_api_key,
                    'token' => $setting->sms_api_secret,
                    'from' => $setting->sms_sender_id,
                    'channel' => 'sms',
                ];
            }
        }

        return ['channel' => $channel];
    }

    /**
     * Send a single notification and log it.
     */
    public function send(
        Company $company,
        string $channel,
        string $phone,
        string $content,
        ?string $templateCode = null,
        ?string $recipientName = null,
        ?int $branchId = null,
        ?int $customerId = null,
        ?int $agreementId = null,
        ?int $paymentId = null,
        ?int $userId = null
    ): NotificationLog {
        $setting = $this->getSetting($company);

        // Normalize phone number
        $normalizedPhone = PhoneNumberNormalizer::toE164($phone);
        $rawPhone = PhoneNumberNormalizer::toInternational($phone);

        // Create initial queued log record
        $log = NotificationLog::create([
            'company_id' => $company->id,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'installment_agreement_id' => $agreementId,
            'payment_id' => $paymentId,
            'channel' => $channel,
            'recipient_phone' => $normalizedPhone,
            'recipient_name' => $recipientName,
            'template_code' => $templateCode,
            'content' => $content,
            'status' => 'queued',
            'provider' => $channel === 'whatsapp' ? $setting->whatsapp_driver : $setting->sms_driver,
            'created_by' => $userId,
        ]);

        if (! $setting->is_active) {
            $log->update([
                'status' => 'failed',
                'error_message' => 'Notification gateway is currently inactive in tenant settings.',
            ]);
            return $log;
        }

        $driver = $this->resolveDriver($setting, $channel);
        $options = $this->buildDriverOptions($setting, $channel);

        // For Generic HTTP Pakistani SMS gateway, provide 923...; for Twilio/Meta provide +923...
        $destination = ($setting->sms_driver === 'generic_http' && $channel === 'sms') ? $rawPhone : $normalizedPhone;

        $result = $driver->send($destination, $content, $options);

        if ($result['success']) {
            $log->update([
                'status' => 'delivered',
                'provider_reference' => $result['reference'],
                'provider_response' => $result['response'],
                'sent_at' => now(),
            ]);
        } else {
            $log->update([
                'status' => 'failed',
                'error_message' => $result['error'],
                'provider_response' => $result['response'],
            ]);
        }

        return $log;
    }

    /**
     * Send instant Payment Collection Receipt Notification.
     */
    public function sendPaymentReceipt(Payment $payment, array $channels = ['sms']): array
    {
        $company = $payment->company;
        $setting = $this->getSetting($company);

        if (! $setting->is_active) {
            return [];
        }

        $this->provisionDefaultTemplates($company);
        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'payment_receipt')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return [];
        }

        $agreement = $payment->agreement;
        $customer = $payment->customer ?? $agreement->customer;

        if (! $customer || ! $customer->mobile_primary) {
            return [];
        }

        $tokens = [
            'customer_name' => $customer->full_name,
            'company_name' => $company->name,
            'amount' => number_format($payment->amount, 2),
            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-M-Y') : date('d-M-Y'),
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'receipt_number' => $payment->receipt_number ?? $payment->payment_number,
            'remaining_balance' => number_format($agreement->remaining_balance, 2),
            'branch_name' => $payment->branch?->name ?? $company->name,
            'branch_phone' => $payment->branch?->phone ?? $company->phone ?? 'Showroom Support',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);
        $logs = [];

        foreach ($channels as $ch) {
            if ($ch === 'sms' && ! $setting->auto_receipt_sms) {
                continue;
            }
            if ($ch === 'whatsapp' && ! $setting->auto_receipt_whatsapp) {
                continue;
            }

            $logs[] = $this->send(
                $company,
                $ch,
                $customer->mobile_primary,
                $rendered,
                'payment_receipt',
                $customer->full_name,
                $payment->branch_id,
                $customer->id,
                $agreement->id,
                $payment->id
            );
        }

        return $logs;
    }

    /**
     * Send Agreement Booking & Activation Welcome Notification.
     */
    public function sendAgreementWelcome(InstallmentAgreement $agreement, array $channels = ['sms']): array
    {
        $company = $agreement->company;
        $setting = $this->getSetting($company);

        if (! $setting->is_active || ! $setting->auto_welcome_sms) {
            return [];
        }

        $this->provisionDefaultTemplates($company);
        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'welcome_agreement')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return [];
        }

        $customer = $agreement->customer;
        if (! $customer || ! $customer->mobile_primary) {
            return [];
        }

        $tokens = [
            'customer_name' => $customer->full_name,
            'company_name' => $company->name,
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'product_name' => $agreement->product?->name ?? 'Merchandise',
            'monthly_installment' => number_format($agreement->monthly_installment_amount, 2),
            'due_day' => $agreement->first_due_date ? Carbon::parse($agreement->first_due_date)->format('jS') : '5th',
            'branch_name' => $agreement->branch?->name ?? $company->name,
            'branch_phone' => $agreement->branch?->phone ?? $company->phone ?? 'Showroom Support',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);
        $logs = [];

        foreach ($channels as $ch) {
            $logs[] = $this->send(
                $company,
                $ch,
                $customer->mobile_primary,
                $rendered,
                'welcome_agreement',
                $customer->full_name,
                $agreement->branch_id,
                $customer->id,
                $agreement->id
            );
        }

        return $logs;
    }

    /**
     * Send Guarantor Undertaking Notification.
     */
    public function sendGuarantorNotice(InstallmentAgreement $agreement, Guarantor $guarantor): ?NotificationLog
    {
        $company = $agreement->company;
        $this->provisionDefaultTemplates($company);

        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'guarantor_notice')
            ->where('is_active', true)
            ->first();

        if (! $template || ! $guarantor->mobile) {
            return null;
        }

        $customer = $agreement->customer;
        $tokens = [
            'guarantor_name' => $guarantor->full_name,
            'customer_name' => $customer?->full_name ?? 'the applicant',
            'company_name' => $company->name,
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'product_name' => $agreement->product?->name ?? 'Electronics',
            'monthly_installment' => number_format($agreement->monthly_installment_amount, 2),
            'branch_phone' => $agreement->branch?->phone ?? $company->phone ?? 'Showroom Support',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);

        return $this->send(
            $company,
            'sms',
            $guarantor->mobile,
            $rendered,
            'guarantor_notice',
            $guarantor->full_name,
            $agreement->branch_id,
            $customer?->id,
            $agreement->id
        );
    }

    /**
     * Send Upcoming Installment Due Reminder.
     * Prevents sending duplicate reminders on the same day for the same schedule.
     */
    public function sendDueReminder(InstallmentSchedule $schedule, string $channel = 'sms'): ?NotificationLog
    {
        $agreement = $schedule->agreement;
        $company = $agreement->company;
        $setting = $this->getSetting($company);

        if (! $setting->is_active) {
            return null;
        }

        // Avoid duplicate reminder for this schedule on the same day
        $alreadySentToday = NotificationLog::where('company_id', $company->id)
            ->where('installment_agreement_id', $agreement->id)
            ->where('template_code', 'due_reminder')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($alreadySentToday) {
            return null;
        }

        $this->provisionDefaultTemplates($company);
        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'due_reminder')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $customer = $agreement->customer;
        if (! $customer || ! $customer->mobile_primary) {
            return null;
        }

        $tokens = [
            'customer_name' => $customer->full_name,
            'company_name' => $company->name,
            'installment_number' => $schedule->installment_number,
            'due_amount' => number_format($schedule->remaining_balance ?? $schedule->total_amount, 2),
            'due_date' => Carbon::parse($schedule->due_date)->format('d-M-Y'),
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'branch_name' => $agreement->branch?->name ?? $company->name,
            'branch_phone' => $agreement->branch?->phone ?? $company->phone ?? 'Showroom Support',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);

        return $this->send(
            $company,
            $channel,
            $customer->mobile_primary,
            $rendered,
            'due_reminder',
            $customer->full_name,
            $agreement->branch_id,
            $customer->id,
            $agreement->id
        );
    }

    /**
     * Send Delinquent Installment Overdue Alert.
     */
    public function sendOverdueAlert(InstallmentSchedule $schedule, string $channel = 'sms'): ?NotificationLog
    {
        $agreement = $schedule->agreement;
        $company = $agreement->company;
        $setting = $this->getSetting($company);

        if (! $setting->is_active || ! $setting->auto_overdue_sms) {
            return null;
        }

        // Avoid duplicate overdue alert on the same calendar day for this agreement
        $alreadySentToday = NotificationLog::where('company_id', $company->id)
            ->where('installment_agreement_id', $agreement->id)
            ->where('template_code', 'overdue_alert')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($alreadySentToday) {
            return null;
        }

        $this->provisionDefaultTemplates($company);
        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'overdue_alert')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $customer = $agreement->customer;
        if (! $customer || ! $customer->mobile_primary) {
            return null;
        }

        $dueDate = Carbon::parse($schedule->due_date);
        $daysOverdue = max(0, (int) $dueDate->diffInDays(Carbon::today(), false));

        $tokens = [
            'customer_name' => $customer->full_name,
            'company_name' => $company->name,
            'due_amount' => number_format($schedule->remaining_balance ?? $schedule->total_amount, 2),
            'due_date' => $dueDate->format('d-M-Y'),
            'days_overdue' => $daysOverdue,
            'late_fee' => number_format($schedule->late_fee_amount ?? 0, 2),
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'branch_name' => $agreement->branch?->name ?? $company->name,
            'branch_phone' => $agreement->branch?->phone ?? $company->phone ?? 'Showroom Recovery',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);

        return $this->send(
            $company,
            $channel,
            $customer->mobile_primary,
            $rendered,
            'overdue_alert',
            $customer->full_name,
            $agreement->branch_id,
            $customer->id,
            $agreement->id
        );
    }

    /**
     * Send NOC Clearance Certificate Notification.
     */
    public function sendNocClearance(InstallmentAgreement $agreement, string $nocNumber, string $channel = 'sms'): ?NotificationLog
    {
        $company = $agreement->company;
        $this->provisionDefaultTemplates($company);

        $template = NotificationTemplate::where('company_id', $company->id)
            ->where('code', 'noc_clearance')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $customer = $agreement->customer;
        if (! $customer || ! $customer->mobile_primary) {
            return null;
        }

        $tokens = [
            'customer_name' => $customer->full_name,
            'company_name' => $company->name,
            'agreement_number' => $agreement->agreement_number ?? $agreement->account_number,
            'noc_number' => $nocNumber,
            'branch_name' => $agreement->branch?->name ?? $company->name,
            'branch_phone' => $agreement->branch?->phone ?? $company->phone ?? 'Showroom Support',
        ];

        $rendered = $this->parseVariables($template->body, $tokens);

        return $this->send(
            $company,
            $channel,
            $customer->mobile_primary,
            $rendered,
            'noc_clearance',
            $customer->full_name,
            $agreement->branch_id,
            $customer->id,
            $agreement->id
        );
    }

    /**
     * Retry a failed notification log.
     */
    public function retryNotification(NotificationLog $log): NotificationLog
    {
        $company = $log->company;
        $setting = $this->getSetting($company);

        $driver = $this->resolveDriver($setting, $log->channel);
        $options = $this->buildDriverOptions($setting, $log->channel);

        $rawPhone = PhoneNumberNormalizer::toInternational($log->recipient_phone);
        $destination = ($setting->sms_driver === 'generic_http' && $log->channel === 'sms') ? $rawPhone : $log->recipient_phone;

        $result = $driver->send($destination, $log->content, $options);

        if ($result['success']) {
            $log->update([
                'status' => 'delivered',
                'provider_reference' => $result['reference'],
                'provider_response' => $result['response'],
                'error_message' => null,
                'sent_at' => now(),
            ]);
        } else {
            $log->update([
                'status' => 'failed',
                'error_message' => $result['error'],
                'provider_response' => $result['response'],
            ]);
        }

        return $log;
    }
}
