<x-app-layout title="Installment Contracts & Agreements">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Installment Contracts & Agreements</h1>
      <p class="text-muted mb-0">Manage customer retail financing agreements, underwriting state machine, down payments, and hardware handovers.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('pricing.calculator') }}" class="btn btn-outline-primary">
        <i class="bi bi-calculator me-1"></i>Quotation Simulator
      </a>
      <a href="{{ route('agreements.create') }}" class="btn btn-primary">
        <i class="bi bi-file-earmark-plus me-1"></i>New Agreement
      </a>
    </div>
  </div>

  <!-- Metric Counter Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index') }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ !request('status') ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">All Contracts</span>
          <strong class="fs-4 text-dark">{{ $counts['all'] }}</strong>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index', ['status' => 'draft']) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('status') === 'draft' ? 'border-secondary border-2' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">Draft</span>
          <strong class="fs-4 text-secondary">{{ $counts['draft'] }}</strong>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index', ['status' => 'under_review']) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('status') === 'under_review' ? 'border-warning border-2' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">Under Review</span>
          <strong class="fs-4 text-warning">{{ $counts['under_review'] }}</strong>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index', ['status' => 'approved']) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('status') === 'approved' ? 'border-info border-2' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">Approved</span>
          <strong class="fs-4 text-info">{{ $counts['approved'] }}</strong>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index', ['status' => 'active']) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('status') === 'active' ? 'border-success border-2' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">Active (Repayment)</span>
          <strong class="fs-4 text-success">{{ $counts['active'] }}</strong>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <a href="{{ route('agreements.index', ['status' => 'completed']) }}" class="card border-0 shadow-sm text-decoration-none h-100 {{ request('status') === 'completed' ? 'border-primary border-2' : '' }}">
        <div class="card-body p-3 text-center">
          <span class="text-muted small d-block">Completed</span>
          <strong class="fs-4 text-primary">{{ $counts['completed'] }}</strong>
        </div>
      </a>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('agreements.index') }}" method="GET" class="row g-3 align-items-center">
        @if(request('status'))
          <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0"
                   placeholder="Search by account #, customer, CNIC, or IMEI/Serial..."
                   value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-4">
          <select name="branch_id" class="form-select">
            <option value="">All Showroom Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }} ({{ $b->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
          <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Agreements Table Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">Account Number</th>
              <th>Customer</th>
              <th>Merchandise / IMEI</th>
              <th>Showroom</th>
              <th>Financing Terms</th>
              <th>Down Payment</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($agreements as $agr)
              <tr>
                <td class="ps-4">
                  <a href="{{ route('agreements.show', $agr->id) }}" class="fw-bold text-primary font-monospace text-decoration-none">
                    {{ $agr->account_number }}
                  </a>
                  <div class="text-muted small">
                    {{ $agr->created_at->format('d M, Y') }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $agr->customer->full_name }}</div>
                  <div class="text-muted small">
                    <span class="font-monospace">{{ $agr->customer->cnic }}</span>
                    &bull; {{ $agr->customer->mobile_primary }}
                  </div>
                </td>
                <td>
                  <div class="fw-semibold text-dark">{{ $agr->product->brand }} {{ $agr->product->model_name }}</div>
                  @if($agr->serializedItem)
                    <span class="badge bg-light text-dark border font-monospace small">
                      {{ $agr->serializedItem->identifier_label }}
                    </span>
                  @else
                    <span class="text-muted small">Standard Non-serialized</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-shop me-1 text-primary"></i>{{ $agr->branch->code }}
                  </span>
                </td>
                <td>
                  <div class="fw-semibold text-dark">Rs. {{ number_format($agr->installment_amount) }}/mo</div>
                  <div class="text-muted small">
                    {{ $agr->tenure_months }}M &bull; Total: Rs. {{ number_format($agr->total_payable) }}
                  </div>
                </td>
                <td>
                  @if($agr->isDownPaymentSatisfied())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="bi bi-check-circle me-1"></i>Rs. {{ number_format($agr->down_payment_paid) }} Paid
                    </span>
                  @else
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                      Pending (Rs. {{ number_format($agr->down_payment_deficit) }} due)
                    </span>
                  @endif
                </td>
                <td>
                  {!! $agr->status_badge !!}
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group">
                    <a href="{{ route('agreements.show', $agr->id) }}" class="btn btn-sm btn-outline-primary" title="View Dossier">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('agreements.print', $agr->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Contract">
                      <i class="bi bi-printer"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-file-earmark-text fs-1 d-block mb-3 text-secondary"></i>
                  <h5 class="fw-bold">No Installment Agreements Found</h5>
                  <p class="mb-3">Draft a new installment sales contract for a showroom customer.</p>
                  <a href="{{ route('agreements.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Create New Agreement
                  </a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($agreements->hasPages())
      <div class="card-footer bg-transparent border-0 p-3">
        {{ $agreements->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
