<x-app-layout title="Installment Plans">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Installment Plans & Financing Templates</h1>
      <p class="text-muted mb-0">Configure standard duration tenures, markup models, and down payment policies for showroom sales.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('pricing.calculator') }}" class="btn btn-outline-primary">
        <i class="bi bi-calculator me-1"></i>Quotation Simulator
      </a>
      <a href="{{ route('plans.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Plan Template
      </a>
    </div>
  </div>

  <!-- Plans Grid -->
  <div class="row g-4">
    @forelse($plans as $plan)
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100 {{ $plan->is_active ? '' : 'opacity-75 bg-light' }}">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <span class="badge bg-primary-subtle text-primary fw-bold text-uppercase fs-6 mb-1">
                  {{ $plan->tenure_months }} Months
                </span>
                <h5 class="fw-bold text-dark mb-0">{{ $plan->name }}</h5>
                <small class="text-muted font-monospace">{{ $plan->slug }}</small>
              </div>
              <span class="badge {{ $plan->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $plan->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <p class="text-muted small mb-3">
              {{ $plan->description ?: 'Standard retail electronic financing plan.' }}
            </p>

            <ul class="list-group list-group-flush small mb-3">
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Markup Model:</span>
                <strong class="text-dark text-capitalize">{{ str_replace('_', ' ', $plan->markup_calculation_model) }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Default Annual Rate:</span>
                <strong class="text-primary fs-6">{{ $plan->default_markup_rate_pct }}%</strong>
              </li>
              @if($plan->fixed_markup_amount)
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                  <span class="text-muted">Fixed Markup Amount:</span>
                  <strong class="text-dark">Rs. {{ number_format($plan->fixed_markup_amount) }}</strong>
                </li>
              @endif
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Min Down Payment:</span>
                <span class="badge bg-light text-dark border">{{ $plan->min_down_payment_pct }}%</span>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Payment Frequency:</span>
                <span class="text-dark text-capitalize fw-semibold">{{ $plan->installment_frequency }}</span>
              </li>
            </ul>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <form action="{{ route('plans.toggle', $plan) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                  <i class="bi {{ $plan->is_active ? 'bi-pause-circle' : 'bi-play-circle' }} me-1"></i>
                  {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                </button>
              </form>

              <div class="d-flex gap-1">
                <a href="{{ route('plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <form action="{{ route('plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Remove this installment plan?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-credit-card-2-front fs-1 d-block mb-2 text-secondary"></i>
        No installment plans created yet. Click "New Plan Template" to define tenure options.
      </div>
    @endforelse
  </div>
</x-app-layout>
