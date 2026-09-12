<x-app-layout title="SaaS Subscription Plans - Super Admin">
  <!-- Header Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">SaaS Subscription Plans</h1>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $plans->count() }} Tiers</span>
      </div>
      <p class="text-muted mb-0">Define tenant pricing plans &bull; Enforce quota limits &bull; Toggle feature flags</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Command Center
      </a>
      <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Create New Tier
      </a>
    </div>
  </div>

  <!-- Plan Tiers Grid -->
  <div class="row g-4 mb-4">
    @forelse($plans as $plan)
      <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 {{ !$plan->is_active ? 'opacity-75' : '' }}">
          <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-primary">{{ $plan->name }}</h5>
            @if($plan->is_active)
              <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
            @else
              <span class="badge bg-secondary-subtle text-muted border">Disabled</span>
            @endif
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3" style="min-height: 40px;">{{ $plan->description ?? 'Standard subscription tier.' }}</p>

            <div class="mb-3">
              <span class="h3 fw-bold text-dark">PKR {{ number_format($plan->price_monthly) }}</span>
              <span class="text-muted small">/ month</span>
              <div class="text-muted small">PKR {{ number_format($plan->price_yearly) }} / year</div>
            </div>

            <div class="p-2 bg-light rounded small mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span>Staff Seats:</span>
                <strong class="text-dark">{{ $plan->max_users }} users</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Showrooms:</span>
                <strong class="text-dark">{{ $plan->max_branches }} branch{{ $plan->max_branches > 1 ? 'es' : '' }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Active Contracts:</span>
                <strong class="text-dark">{{ number_format($plan->max_active_agreements) }} max</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span>Transactions/mo:</span>
                <strong class="text-dark">{{ number_format($plan->max_monthly_transactions) }} receipts</strong>
              </div>
            </div>

            <h6 class="fw-bold small text-uppercase text-muted mb-2">Features Included</h6>
            <ul class="list-unstyled small mb-4">
              @php
                $features = $plan->features ?? [];
              @endphp
              <li class="mb-1 text-muted">
                <i class="bi bi-check2 text-success me-1"></i>Thermal &amp; A4 Documents
              </li>
              <li class="mb-1 text-muted">
                <i class="bi {{ in_array('sms_notifications', $features) || in_array('*', $features) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-1"></i>SMS Gateway Alerts
              </li>
              <li class="mb-1 text-muted">
                <i class="bi {{ in_array('whatsapp_notifications', $features) || in_array('*', $features) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-1"></i>WhatsApp Cloud
              </li>
              <li class="mb-1 text-muted">
                <i class="bi {{ in_array('general_ledger', $features) || in_array('*', $features) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-1"></i>Double-Entry General Ledger
              </li>
              <li class="mb-1 text-muted">
                <i class="bi {{ in_array('advanced_analytics', $features) || in_array('*', $features) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-1"></i>Aging PAR 30/60/90
              </li>
            </ul>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <span class="badge bg-info-subtle text-info border border-info-subtle">
                {{ $plan->subscriptions_count }} Active Subscribers
              </span>
              <div class="d-flex gap-1">
                <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit Plan">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" action="{{ route('admin.plans.toggle-active', $plan) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} py-1 px-2" title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="bi {{ $plan->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5 text-muted">
        No plans defined yet. Click "Create New Tier" to initialize standard pricing plans.
      </div>
    @endforelse
  </div>
</x-app-layout>
