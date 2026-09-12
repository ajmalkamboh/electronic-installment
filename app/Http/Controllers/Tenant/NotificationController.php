<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\InstallmentAgreement;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Models\NotificationTemplate;
use App\Services\Notification\NotificationService;
use App\Services\Notification\PhoneNumberNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Notification Outbox & Communications Hub.
     */
    public function index(Request $request)
    {
        $company = Auth::user()->company;
        $this->notificationService->provisionDefaultTemplates($company);
        $setting = $this->notificationService->getSetting($company);

        $search = $request->get('search');
        $channel = $request->get('channel');
        $status = $request->get('status');
        $date = $request->get('date');

        $query = NotificationLog::where('company_id', $company->id)
            ->with(['branch', 'customer', 'agreement', 'createdBy'])
            ->latest('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%");
            });
        }

        if ($channel) {
            $query->where('channel', $channel);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $logs = $query->paginate(20)->withQueryString();

        // High-level statistics
        $totalSent = NotificationLog::where('company_id', $company->id)->count();
        $totalDelivered = NotificationLog::where('company_id', $company->id)->where('status', 'delivered')->count();
        $totalFailed = NotificationLog::where('company_id', $company->id)->where('status', 'failed')->count();
        $deliveryRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 100.0;

        $branches = Branch::where('company_id', $company->id)->get();
        $customers = Customer::where('company_id', $company->id)->orderBy('full_name')->take(50)->get();

        return view('tenant.notifications.index', compact(
            'logs',
            'setting',
            'totalSent',
            'totalDelivered',
            'totalFailed',
            'deliveryRate',
            'branches',
            'customers',
            'search',
            'channel',
            'status',
            'date'
        ));
    }

    /**
     * Message Templates Manager.
     */
    public function templates(Request $request)
    {
        $company = Auth::user()->company;
        $this->notificationService->provisionDefaultTemplates($company);

        $templates = NotificationTemplate::where('company_id', $company->id)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        return view('tenant.notifications.templates', compact('templates'));
    }

    /**
     * Update an existing message template.
     */
    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        if ($template->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'channel' => 'required|in:sms,whatsapp,both',
            'subject' => 'nullable|string|max:191',
            'body' => 'required|string|max:1000',
            'is_active' => 'required|boolean',
        ]);

        $template->update($validated);

        return redirect()->route('notifications.templates')->with('success', "Template '{$template->name}' updated successfully.");
    }

    /**
     * Notification Gateway Settings.
     */
    public function settings()
    {
        $company = Auth::user()->company;
        $setting = $this->notificationService->getSetting($company);

        return view('tenant.notifications.settings', compact('setting'));
    }

    /**
     * Update Notification Gateway credentials & automation triggers.
     */
    public function updateSettings(Request $request)
    {
        $company = Auth::user()->company;
        $setting = $this->notificationService->getSetting($company);

        $validated = $request->validate([
            'sms_driver' => 'required|in:log,generic_http,twilio',
            'sms_sender_id' => 'nullable|string|max:20',
            'sms_api_key' => 'nullable|string|max:255',
            'sms_api_secret' => 'nullable|string|max:255',
            'sms_endpoint_url' => 'nullable|url|max:255',
            'whatsapp_driver' => 'required|in:log,meta_cloud,twilio',
            'whatsapp_phone_number_id' => 'nullable|string|max:100',
            'whatsapp_access_token' => 'nullable|string|max:500',
            'whatsapp_business_account_id' => 'nullable|string|max:100',
            'auto_receipt_sms' => 'boolean',
            'auto_receipt_whatsapp' => 'boolean',
            'auto_welcome_sms' => 'boolean',
            'auto_reminder_days_before' => 'required|integer|min:1|max:14',
            'auto_overdue_sms' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['auto_receipt_sms'] = $request->has('auto_receipt_sms');
        $validated['auto_receipt_whatsapp'] = $request->has('auto_receipt_whatsapp');
        $validated['auto_welcome_sms'] = $request->has('auto_welcome_sms');
        $validated['auto_overdue_sms'] = $request->has('auto_overdue_sms');
        $validated['is_active'] = $request->has('is_active');

        $setting->update($validated);

        return redirect()->route('notifications.settings')->with('success', 'Notification Gateway configuration updated successfully.');
    }

    /**
     * Send Custom / Ad-Hoc SMS or WhatsApp Notification.
     */
    public function sendCustom(Request $request)
    {
        $company = Auth::user()->company;

        $validated = $request->validate([
            'recipient_phone' => 'required|string|max:30',
            'recipient_name' => 'nullable|string|max:120',
            'channel' => 'required|in:sms,whatsapp',
            'message' => 'required|string|max:1000',
            'customer_id' => 'nullable|exists:customers,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $customer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $branchId = $request->branch_id ?: Auth::user()->branch_id;

        $log = $this->notificationService->send(
            $company,
            $validated['channel'],
            $validated['recipient_phone'],
            $validated['message'],
            'custom_broadcast',
            $validated['recipient_name'] ?? $customer?->full_name,
            $branchId,
            $customer?->id,
            null,
            null,
            Auth::id()
        );

        if ($log->status === 'failed') {
            return redirect()->back()->with('error', "Notification dispatch failed: {$log->error_message}");
        }

        return redirect()->route('notifications.index')->with('success', "Notification dispatched successfully to {$log->recipient_phone} via " . strtoupper($log->channel) . ".");
    }

    /**
     * Retry a failed notification.
     */
    public function retry(NotificationLog $notification)
    {
        if ($notification->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized company access.');
        }

        $this->notificationService->retryNotification($notification);

        if ($notification->status === 'delivered') {
            return redirect()->back()->with('success', "Notification #{$notification->id} retried successfully.");
        }

        return redirect()->back()->with('error', "Retry failed: {$notification->error_message}");
    }
}
