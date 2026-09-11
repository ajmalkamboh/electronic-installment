<x-app-layout title="Payments & Installment Collections">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Payments & Installment Collections</h1>
      <p class="text-muted mb-0">Record customer counter payments, monitor multi-channel inflows, and review automated schedule allocations.</p>
    </div>
    <div>
      <a href="{{ route('payments.create') }}" class="btn btn-primary">
        <i class="bi bi-cash-stack me-1"></i>Record Customer Payment
      </a>
    </div>
  </div>

  <!-- Metric Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-success-subtle text-success p-3 fs-3">
            <i class="bi bi-cash"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Total Collections Recorded</span>
            <h3 class="fw-bold mb-0 text-dark">Rs. {{ number_format($totalCollected) }}</h3>
            <small class="text-muted">{{ $paymentCount }} Total Receipts</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-3">
            <i class="bi bi-calendar2-check"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Today's Inflow (All Channels)</span>
            <h3 class="fw-bold mb-0 text-primary">Rs. {{ number_format($todayCollected) }}</h3>
            <small class="text-muted">{{ date('d F, Y') }}</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-info-subtle text-info-emphasis p-3 fs-3">
            <i class="bi bi-receipt"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Waterfall Priority Applied</span>
            <h4 class="fw-bold mb-0 text-dark">Late Fee &rarr; Earliest &rarr; Current</h4>
            <small class="text-muted">Strict FR-10.2 Enforcement</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('payments.index') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Search receipt #, agreement, customer, or CNIC..."
                   value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="branch_id" class="form-select">
            <option value="">All Showrooms</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="payment_method" class="form-select">
            <option value="">All Methods</option>
            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            <option value="raast" {{ request('payment_method') === 'raast' ? 'selected' : '' }}>Raast</option>
            <option value="easypaisa" {{ request('payment_method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
            <option value="jazzcash" {{ request('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
            <option value="cheque" {{ request('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
          <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Payments Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">Receipt Number</th>
              <th>Customer</th>
              <th>Agreement #</th>
              <th>Merchandise</th>
              <th>Amount Tendered</th>
              <th>Payment Method</th>
              <th>Cashier</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($payments as $p)
              <tr>
                <td class="ps-4">
                  <a href="{{ route('payments.show', $p->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                    {{ $p->payment_number }}
                  </a>
                  <div class="text-muted small">
                    {{ $p->payment_date->format('d M, Y') }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $p->customer->full_name }}</div>
                  <small class="text-muted font-monospace">{{ $p->customer->cnic }}</small>
                </td>
                <td>
                  <a href="{{ route('agreements.show', $p->agreement->id) }}" class="text-dark fw-semibold font-monospace text-decoration-none">
                    {{ $p->agreement->account_number }}
                  </a>
                </td>
                <td>
                  <div class="small fw-semibold text-dark">{{ $p->agreement->product->brand }} {{ $p->agreement->product->model_name }}</div>
                  <small class="text-muted">{{ $p->branch->name }}</small>
                </td>
                <td>
                  <strong class="fs-6 text-success">Rs. {{ number_format($p->amount) }}</strong>
                  <div class="text-muted small">
                    P: {{ number_format($p->principal_paid) }} &bull; M: {{ number_format($p->markup_paid) }}
                  </div>
                </td>
                <td>
                  {!! $p->method_badge !!}
                  @if($p->reference_number)
                    <div class="text-muted font-monospace small mt-1">{{ $p->reference_number }}</div>
                  @endif
                </td>
                <td>
                  <span class="small text-dark">{{ $p->cashier->name }}</span>
                </td>
                <td>
                  {!! $p->status_badge !!}
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group">
                    <a href="{{ route('payments.show', $p->id) }}" class="btn btn-sm btn-outline-primary" title="View Receipt Dossier">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('payments.print', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Receipt">
                      <i class="bi bi-printer"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="bi bi-cash-coin fs-1 d-block mb-3 text-secondary"></i>
                  <h5 class="fw-bold">No Payments Recorded Yet</h5>
                  <p class="mb-3">Record incoming customer installment payments or down payments.</p>
                  <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Record First Payment
                  </a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($payments->hasPages())
      <div class="card-footer bg-transparent border-0 p-3">
        {{ $payments->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
