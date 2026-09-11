<x-app-layout title="Field Cash Drawer Handovers">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Field Cash Drawer Handover Reconciliation</h1>
      <p class="text-muted mb-0">Dual-custody settlement: Verify and acknowledge physical cash collected in the field by recovery officers into the showroom cash drawer.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Command Center
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Unsettled Cash Summary Card -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card border-0 shadow-sm bg-primary text-white">
        <div class="card-body p-4 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-white-50 text-uppercase small fw-bold d-block">Unsettled Field Cash in Custody</span>
            <h2 class="display-6 fw-bold mb-0">Rs. {{ number_format($totalUnsettled) }}</h2>
            <small class="text-white-50">{{ $pendingHandovers->count() }} field payments awaiting cashier acknowledgment</small>
          </div>
          <div class="fs-1 text-white-50">
            <i class="bi bi-safe"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card border-0 shadow-sm bg-light h-100">
        <div class="card-body p-4">
          <h6 class="fw-bold text-dark mb-2">
            <i class="bi bi-shield-check text-success me-2"></i>Dual-Custody Cashier Protocol (FR-11.3)
          </h6>
          <p class="small text-muted mb-0">
            Physical cash collected by field recovery officers remains in <strong>submitted</strong> status until counted and accepted into the branch cash drawer.
            Once acknowledged, the payment status shifts to <strong>acknowledged</strong>, attributing the receiving cashier and balancing the showroom drawer ledger.
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Submitted Field Payments Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0 text-dark">
        <i class="bi bi-cash-coin text-success me-2"></i>Pending Field Collections
      </h5>
      <span class="badge bg-warning text-dark">{{ $pendingHandovers->count() }} Pending Handovers</span>
    </div>
    <div class="card-body p-0 mt-3">
      @if($pendingHandovers->isNotEmpty())
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
              <tr>
                <th class="ps-4">Receipt / Payment #</th>
                <th>Collection Officer</th>
                <th>Customer & Phone</th>
                <th>Agreement #</th>
                <th>Amount Collected</th>
                <th>Field Ref #</th>
                <th>Collected Time</th>
                <th class="pe-4 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pendingHandovers as $payment)
                <tr>
                  <td class="ps-4 font-monospace fw-bold">
                    <a href="{{ route('payments.show', $payment->id) }}" class="text-decoration-none">
                      {{ $payment->payment_number }}
                    </a>
                  </td>
                  <td>
                    <div class="fw-bold text-dark">{{ $payment->collector?->name ?? 'Unknown Officer' }}</div>
                    <small class="text-muted">{{ $payment->collector?->email }}</small>
                  </td>
                  <td>
                    <div class="text-dark">{{ $payment->customer->full_name }}</div>
                    <small class="text-muted font-monospace">{{ $payment->customer->mobile_primary }}</small>
                  </td>
                  <td class="font-monospace small">
                    <a href="{{ route('agreements.show', $payment->installment_agreement_id) }}">
                      {{ $payment->agreement->account_number }}
                    </a>
                  </td>
                  <td class="fw-bold text-success fs-6">
                    Rs. {{ number_format($payment->amount) }}
                  </td>
                  <td class="font-monospace small">
                    {{ $payment->reference_number ?? '-' }}
                  </td>
                  <td class="small text-muted">
                    {{ $payment->created_at->format('d M, Y h:i A') }}
                  </td>
                  <td class="pe-4 text-end">
                    <form action="{{ route('collections.handovers.acknowledge') }}" method="POST" class="d-inline">
                      @csrf
                      <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                      <button type="submit" class="btn btn-sm btn-success" title="Accept Cash into Drawer">
                        <i class="bi bi-check2-circle me-1"></i>Acknowledge
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-5 text-muted">
          <i class="bi bi-check2-all text-success fs-1 d-block mb-3"></i>
          <h5 class="fw-bold">All Field Cash Settled</h5>
          <p class="mb-0">There are no pending submitted field collections awaiting cashier drawer handover.</p>
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
