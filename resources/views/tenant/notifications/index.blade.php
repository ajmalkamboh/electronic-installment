@extends('layouts.app')

@section('title', 'Notification Outbox & Communications Hub')

@section('content')
<div class="container-fluid py-3">
    <!-- Header with Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold mb-0">SMS &amp; WhatsApp Communications Hub</h1>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Phase 15</span>
            </div>
            <p class="text-muted mb-0">
                Automated customer receipts, payment reminders, overdue alerts &amp; custom broadcasting
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                <i class="bi bi-send-plus me-1"></i>Compose Notification
            </button>
            <a href="{{ route('notifications.templates') }}" class="btn btn-outline-secondary">
                <i class="bi bi-card-text me-1"></i>Message Templates
            </a>
            <a href="{{ route('notifications.settings') }}" class="btn btn-outline-secondary">
                <i class="bi bi-sliders2-vertical me-1"></i>Gateway Settings
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

    <!-- Gateway Status Banner if Inactive -->
    @if(! $setting->is_active)
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-pause-circle fs-4 me-3 text-warning"></i>
                <div>
                    <strong class="d-block">Notifications Gateway is Paused</strong>
                    <span class="small text-muted">Outgoing automated messages and reminders are currently held. Activate in Gateway Settings to resume dispatches.</span>
                </div>
            </div>
            <a href="{{ route('notifications.settings') }}" class="btn btn-sm btn-warning text-dark fw-bold">Configure Gateway</a>
        </div>
    @endif

    <!-- High-level Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Total Dispatched</span>
                        <div class="avatar bg-primary-subtle text-primary rounded p-2"><i class="bi bi-chat-dots-fill"></i></div>
                    </div>
                    <h3 class="fw-bold mb-0">{{ number_format($totalSent) }}</h3>
                    <span class="text-muted small">All outbound communications</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Delivered</span>
                        <div class="avatar bg-success-subtle text-success rounded p-2"><i class="bi bi-check2-all"></i></div>
                    </div>
                    <h3 class="fw-bold text-success mb-0">{{ number_format($totalDelivered) }}</h3>
                    <span class="text-muted small">Confirmed gateway receipts</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Failed</span>
                        <div class="avatar bg-danger-subtle text-danger rounded p-2"><i class="bi bi-x-circle-fill"></i></div>
                    </div>
                    <h3 class="fw-bold text-danger mb-0">{{ number_format($totalFailed) }}</h3>
                    <span class="text-muted small">Network / phone errors</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small fw-semibold text-uppercase">Success Rate</span>
                        <div class="avatar bg-info-subtle text-info rounded p-2"><i class="bi bi-pie-chart-fill"></i></div>
                    </div>
                    <h3 class="fw-bold text-info mb-0">{{ $deliveryRate }}%</h3>
                    <span class="text-muted small">Active driver: <strong>{{ strtoupper($setting->sms_driver) }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('notifications.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search phone, recipient, or text.." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="channel" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Channels</option>
                        <option value="sms" {{ $channel === 'sms' ? 'selected' : '' }}>SMS Only</option>
                        <option value="whatsapp" {{ $channel === 'whatsapp' ? 'selected' : '' }}>WhatsApp Only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="queued" {{ $status === 'queued' ? 'selected' : '' }}>Queued</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                    @if($search || $channel || $status || $date)
                        <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Outbox Log Table -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Outbox Audit Log</h5>
            <span class="badge bg-light text-dark border">{{ $logs->total() }} Log Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">#ID</th>
                        <th style="width: 100px;">Channel</th>
                        <th style="width: 170px;">Recipient</th>
                        <th style="width: 140px;">Type / Template</th>
                        <th>Message Content</th>
                        <th style="width: 120px;">Provider</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 130px;">Dispatched At</th>
                        <th style="width: 80px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="font-monospace text-muted">{{ $log->id }}</td>
                            <td>{!! $log->channel_badge !!}</td>
                            <td>
                                <div class="fw-bold font-monospace text-dark">{{ $log->recipient_phone }}</div>
                                @if($log->recipient_name)
                                    <small class="text-muted d-block text-truncate" style="max-width: 160px;">{{ $log->recipient_name }}</small>
                                @endif
                                @if($log->customer)
                                    <span class="badge bg-light text-primary border" style="font-size: 10px;">Customer #{{ $log->customer->customer_number }}</span>
                                @endif
                            </td>
                            <td>
                                @if($log->template_code)
                                    <span class="badge bg-light text-dark border">{{ str_replace('_', ' ', ucfirst($log->template_code)) }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">Ad-Hoc</span>
                                @endif
                                @if($log->agreement)
                                    <div class="small text-muted font-monospace mt-1">Agr #{{ $log->agreement->agreement_number ?? $log->agreement->account_number }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-break" style="max-width: 420px;">
                                    {{ $log->content }}
                                </div>
                                @if($log->error_message)
                                    <div class="small text-danger mt-1">
                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $log->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace text-uppercase">{{ $log->provider }}</span>
                                @if($log->provider_reference)
                                    <div class="small text-muted font-monospace text-truncate" style="max-width: 110px;" title="{{ $log->provider_reference }}">
                                        {{ $log->provider_reference }}
                                    </div>
                                @endif
                            </td>
                            <td>{!! $log->status_badge !!}</td>
                            <td>
                                @if($log->sent_at)
                                    <span class="fw-semibold">{{ $log->sent_at->format('d-M H:i') }}</span>
                                    <small class="text-muted d-block">{{ $log->sent_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">{{ $log->created_at->format('d-M H:i') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($log->status === 'failed')
                                    <form method="POST" action="{{ route('notifications.retry', $log) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Retry Failed Dispatch">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary"></i>
                                <strong>No outbound notifications found matching your search.</strong>
                                <p class="small text-muted mb-0">Use the "Compose Notification" button to dispatch custom SMS or WhatsApp messages.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Compose Notification Modal -->
<div class="modal fade" id="composeModal" tabindex="-1" aria-labelledby="composeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('notifications.send') }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="composeModalLabel">
                        <i class="bi bi-send-plus me-1"></i>Compose Custom Notification
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Communication Channel <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="channel" id="channelSms" value="sms" checked>
                                <label class="form-check-label" for="channelSms">
                                    <i class="bi bi-chat-text-fill text-primary me-1"></i>SMS Gateway
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="channel" id="channelWhatsapp" value="whatsapp">
                                <label class="form-check-label" for="channelWhatsapp">
                                    <i class="bi bi-whatsapp text-success me-1"></i>WhatsApp Business
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Recipient Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><i class="bi bi-phone"></i></span>
                            <input type="text" name="recipient_phone" class="form-control font-monospace" placeholder="03001234567 or +923001234567" required>
                        </div>
                        <small class="form-text text-muted">Supports all Pakistani cellular networks (Jazz, Telenor, Zong, Ufone, SCO).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Recipient Full Name</label>
                        <input type="text" name="recipient_name" class="form-control" placeholder="e.g. Tariq Mehmood">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">Message Content <span class="text-danger">*</span></label>
                            <span class="small text-muted" id="charCounter">0 / 160 characters</span>
                        </div>
                        <textarea name="message" id="messageBox" class="form-control" rows="4" placeholder="Enter message text in English or Urdu.." required oninput="updateCounter(this)"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-send me-1"></i>Dispatch Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateCounter(textarea) {
    const len = textarea.value.length;
    const parts = Math.ceil(len / 160) || 1;
    document.getElementById('charCounter').textContent = `${len} chars (${parts} SMS part${parts > 1 ? 's' : ''})`;
}
</script>
@endsection
