@extends('layouts.app')

@section('title', 'Message Templates Manager')

@section('content')
<div class="container-fluid py-3">
    <!-- Header with Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h1 class="h3 fw-bold mb-0">Automated Message Templates</h1>
            </div>
            <p class="text-muted mb-0">
                Manage customizable SMS and WhatsApp message formats with dynamic variable tokens
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

    <!-- Variable Tokens Guidance Card -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                <div>
                    <strong class="d-block text-dark">Dynamic Merge Variables</strong>
                    <span class="small text-muted">Use these tokens in your message bodies; our dispatch engine automatically substitutes them with real contract &amp; receipt data:</span>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge bg-white text-dark border font-monospace">{customer_name}</span>
                        <span class="badge bg-white text-dark border font-monospace">{company_name}</span>
                        <span class="badge bg-white text-dark border font-monospace">{agreement_number}</span>
                        <span class="badge bg-white text-dark border font-monospace">{product_name}</span>
                        <span class="badge bg-white text-dark border font-monospace">{amount}</span>
                        <span class="badge bg-white text-dark border font-monospace">{payment_date}</span>
                        <span class="badge bg-white text-dark border font-monospace">{receipt_number}</span>
                        <span class="badge bg-white text-dark border font-monospace">{remaining_balance}</span>
                        <span class="badge bg-white text-dark border font-monospace">{due_date}</span>
                        <span class="badge bg-white text-dark border font-monospace">{due_amount}</span>
                        <span class="badge bg-white text-dark border font-monospace">{days_overdue}</span>
                        <span class="badge bg-white text-dark border font-monospace">{late_fee}</span>
                        <span class="badge bg-white text-dark border font-monospace">{guarantor_name}</span>
                        <span class="badge bg-white text-dark border font-monospace">{monthly_installment}</span>
                        <span class="badge bg-white text-dark border font-monospace">{branch_name}</span>
                        <span class="badge bg-white text-dark border font-monospace">{branch_phone}</span>
                        <span class="badge bg-white text-dark border font-monospace">{noc_number}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Cards Grid -->
    <div class="row g-4">
        @foreach($templates as $template)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100 {{ $template->is_active ? '' : 'opacity-75 bg-light' }}">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-muted border font-monospace small mb-1">{{ $template->code }}</span>
                            <h6 class="fw-bold mb-0 text-dark">{{ $template->name }}</h6>
                        </div>
                        {!! $template->channel_badge !!}
                    </div>
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            @if($template->subject)
                                <div class="small text-muted mb-2">
                                    <strong>Subject:</strong> {{ $template->subject }}
                                </div>
                            @endif
                            <div class="p-3 bg-light rounded border text-muted small font-monospace text-break" style="min-height: 110px; line-height: 1.6;">
                                {{ $template->body }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top">
                            <div>
                                @if($template->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check-circle me-1"></i>Active Trigger
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-slash-circle me-1"></i>Disabled
                                    </span>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $template->id }}">
                                <i class="bi bi-pencil-square me-1"></i>Customize
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Template Modal -->
            <div class="modal fade" id="editModal{{ $template->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow">
                        <form method="POST" action="{{ route('notifications.templates.update', $template) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header bg-light">
                                <h5 class="modal-title fw-bold">
                                    <i class="bi bi-pencil-square text-primary me-2"></i>Edit Template: {{ $template->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Template Display Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Target Channel <span class="text-danger">*</span></label>
                                        <select name="channel" class="form-select">
                                            <option value="both" {{ $template->channel === 'both' ? 'selected' : '' }}>SMS &amp; WhatsApp</option>
                                            <option value="sms" {{ $template->channel === 'sms' ? 'selected' : '' }}>SMS Only</option>
                                            <option value="whatsapp" {{ $template->channel === 'whatsapp' ? 'selected' : '' }}>WhatsApp Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Automation Status <span class="text-danger">*</span></label>
                                        <select name="is_active" class="form-select">
                                            <option value="1" {{ $template->is_active ? 'selected' : '' }}>Active (Enabled)</option>
                                            <option value="0" {{ ! $template->is_active ? 'selected' : '' }}>Disabled (Paused)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Email / WhatsApp Subject (Optional)</label>
                                    <input type="text" name="subject" class="form-control" value="{{ $template->subject }}">
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0">Message Content Body <span class="text-danger">*</span></label>
                                        <span class="small text-muted">Supports English &amp; Urdu unicode</span>
                                    </div>
                                    <textarea name="body" class="form-control font-monospace" rows="5" required>{{ $template->body }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Save Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
