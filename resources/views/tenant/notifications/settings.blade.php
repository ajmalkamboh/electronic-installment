@extends('layouts.app')

@section('title', 'Notification Gateway Settings')

@section('content')
<div class="container-fluid py-3">
    <!-- Header with Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">Gateway &amp; Automation Configuration</h1>
            </div>
            <p class="text-muted mb-0">
                Configure SMS aggregators, WhatsApp Cloud API, sender masking &amp; automated notification triggers
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-chat-dots me-1"></i>View Outbox Log
            </a>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('notifications.settings.update') }}">
        @csrf

        <div class="row g-4 mb-4">
            <!-- Left Column: Drivers & Gateways -->
            <div class="col-lg-7">
                <!-- Master Gateway Switch -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Master Notification Engine</h5>
                            <span class="text-muted small">Enable or temporarily suspend all automated &amp; ad-hoc SMS / WhatsApp dispatches.</span>
                        </div>
                        <div class="form-check form-switch fs-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="masterActiveSwitch" value="1" {{ $setting->is_active ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <!-- SMS Gateway Configuration Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-chat-text-fill text-primary fs-5"></i>
                            <h5 class="fw-bold mb-0">SMS Gateway Configuration</h5>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pakistani Bulk SMS</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Active SMS Driver <span class="text-danger">*</span></label>
                            <select name="sms_driver" class="form-select" onchange="toggleSmsFields(this.value)">
                                <option value="log" {{ $setting->sms_driver === 'log' ? 'selected' : '' }}>Simulation / Log Driver (Zero-Cost Local Testing)</option>
                                <option value="generic_http" {{ $setting->sms_driver === 'generic_http' ? 'selected' : '' }}>Generic HTTP Gateway (Pakistani Aggregators: Jazz/Zong/Telenor/SMSCountry)</option>
                                <option value="twilio" {{ $setting->sms_driver === 'twilio' ? 'selected' : '' }}>Twilio International SMS</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Sender ID / Brand Masking (Alphanumeric)</label>
                            <input type="text" name="sms_sender_id" class="form-control" placeholder="e.g. KAMBOH-ELEC" value="{{ $setting->sms_sender_id }}" maxlength="20">
                            <small class="form-text text-muted">Authorized 11-character PTA brand mask assigned by your telco aggregator.</small>
                        </div>

                        <div id="smsHttpFields">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">SMS Gateway API Endpoint URL</label>
                                <input type="url" name="sms_endpoint_url" class="form-control" placeholder="https://api.smsaggregator.pk/v1/send" value="{{ $setting->sms_endpoint_url }}">
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">API Key / Username</label>
                                    <input type="text" name="sms_api_key" class="form-control" value="{{ $setting->sms_api_key }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">API Secret / Password / Token</label>
                                    <input type="password" name="sms_api_secret" class="form-control" value="{{ $setting->sms_api_secret }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Cloud API Configuration Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-whatsapp text-success fs-5"></i>
                            <h5 class="fw-bold mb-0">WhatsApp Business Cloud API</h5>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Meta Verified</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Active WhatsApp Driver <span class="text-danger">*</span></label>
                            <select name="whatsapp_driver" class="form-select">
                                <option value="log" {{ $setting->whatsapp_driver === 'log' ? 'selected' : '' }}>Simulation / Log Driver (Zero-Cost Local Testing)</option>
                                <option value="meta_cloud" {{ $setting->whatsapp_driver === 'meta_cloud' ? 'selected' : '' }}>Meta WhatsApp Cloud API (Graph API v19+)</option>
                                <option value="twilio" {{ $setting->whatsapp_driver === 'twilio' ? 'selected' : '' }}>Twilio for WhatsApp</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Phone Number ID</label>
                                <input type="text" name="whatsapp_phone_number_id" class="form-control" placeholder="e.g. 1048291048123" value="{{ $setting->whatsapp_phone_number_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">WhatsApp Business Account ID</label>
                                <input type="text" name="whatsapp_business_account_id" class="form-control" placeholder="e.g. 1928374829123" value="{{ $setting->whatsapp_business_account_id }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">System User Permanent Access Token</label>
                            <input type="password" name="whatsapp_access_token" class="form-control" placeholder="EAAB..." value="{{ $setting->whatsapp_access_token }}">
                            <small class="form-text text-muted">Generated from Meta Business Manager with <code>whatsapp_business_messaging</code> permission.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Automated Event Triggers -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-robot text-primary fs-5"></i>
                            <h5 class="fw-bold mb-0">Automated Event Triggers</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="list-group list-group-flush">
                            <!-- Trigger 1: Payment Receipt SMS -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Instant Payment Receipt SMS</h6>
                                    <small class="text-muted d-block">Dispatch confirmation SMS with receipt number &amp; remaining balance immediately upon receiving installment cash.</small>
                                </div>
                                <div class="form-check form-switch fs-5 ms-3">
                                    <input class="form-check-input" type="checkbox" name="auto_receipt_sms" value="1" {{ $setting->auto_receipt_sms ? 'checked' : '' }}>
                                </div>
                            </div>

                            <!-- Trigger 2: Payment Receipt WhatsApp -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Payment Receipt WhatsApp</h6>
                                    <small class="text-muted d-block">Send duplicate receipt confirmation directly to customer's WhatsApp number.</small>
                                </div>
                                <div class="form-check form-switch fs-5 ms-3">
                                    <input class="form-check-input" type="checkbox" name="auto_receipt_whatsapp" value="1" {{ $setting->auto_receipt_whatsapp ? 'checked' : '' }}>
                                </div>
                            </div>

                            <!-- Trigger 3: Agreement Welcome SMS -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Contract Booking Welcome SMS</h6>
                                    <small class="text-muted d-block">Notify customer upon agreement activation with installment amount &amp; monthly due day.</small>
                                </div>
                                <div class="form-check form-switch fs-5 ms-3">
                                    <input class="form-check-input" type="checkbox" name="auto_welcome_sms" value="1" {{ $setting->auto_welcome_sms ? 'checked' : '' }}>
                                </div>
                            </div>

                            <!-- Trigger 4: Due Date Reminder Days -->
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0">Upcoming Due Reminder Notice</h6>
                                    <span class="badge bg-primary">{{ $setting->auto_reminder_days_before }} Days Prior</span>
                                </div>
                                <small class="text-muted d-block mb-2">How many days before the installment due date to dispatch the friendly reminder:</small>
                                <div class="input-group input-group-sm" style="max-width: 200px;">
                                    <input type="number" name="auto_reminder_days_before" class="form-control text-center" min="1" max="14" value="{{ $setting->auto_reminder_days_before }}" required>
                                    <span class="input-group-text">days before</span>
                                </div>
                            </div>

                            <!-- Trigger 5: Overdue Delinquency Alert -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Overdue Delinquency Warnings</h6>
                                    <small class="text-muted d-block">Dispatch progressive delinquency alerts at 1-day, 7-day, and 15-day overdue milestones.</small>
                                </div>
                                <div class="form-check form-switch fs-5 ms-3">
                                    <input class="form-check-input" type="checkbox" name="auto_overdue_sms" value="1" {{ $setting->auto_overdue_sms ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Action Card -->
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body p-3">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="bi bi-check-circle me-1"></i>Save Gateway Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
