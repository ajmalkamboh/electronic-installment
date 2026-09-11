<x-app-layout title="Payment Receipt - {{ $payment->payment_number }}">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0 font-monospace">{{ $payment->payment_number }}</h1>
        {!! $payment->status_badge !!}
      </div>
      <p class="text-muted mb-0">
        Received on {{ $payment->payment_date->format('d M, Y') }} at <strong>{{ $payment->branch->name }}</strong> &bull;
        Cashier: {{ $payment->cashier->name }}
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('payments.print', $payment->id) }}" target="_blank" class="btn btn-primary">
        <i class="bi bi-printer me-1"></i>Print Official Receipt
      </a>
      <a href="{{ route('agreements.show', $payment->agreement->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-text me-1"></i>Agreement Dossier
      </a>
      <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        All Payments
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left Column: Receipt Summary & Allocation -->
    <div class="col-lg-8">
      <!-- Payment Particulars -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-receipt text-primary me-2"></i>Transaction Particulars
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <span class="text-muted small d-block">Amount Tendered:</span>
              <h3 class="fw-bold text-success mb-0">Rs. {{ number_format($payment->amount) }}</h3>
            </div>
            <div class="col-md-4">
              <span class="text-muted small d-block">Payment Method:</span>
              <div class="mt-1">{!! $payment->method_badge !!}</div>
              @if($payment->reference_number)
                <small class="text-muted font-monospace d-block mt-1">Ref: {{ $payment->reference_number }}</small>
              @endif
            </div>
            <div class="col-md-4">
              <span class="text-muted small d-block">Financial Breakdown:</span>
              <div class="small">
                Principal: <strong>Rs. {{ number_format($payment->principal_paid) }}</strong><br>
                Markup: <strong>Rs. {{ number_format($payment->markup_paid) }}</strong><br>
                Late Fee: <strong>Rs. {{ number_format($payment->late_fee_paid) }}</strong>
              </div>
            </div>
          </div>

          @if($payment->notes)
            <div class="mt-3 p-2 bg-light rounded small text-muted">
              <strong>Cashier Remarks:</strong> {{ $payment->notes }}
            </div>
          @endif
        </div>
      </div>

      <!-- Allocation Schedule Breakdown Table -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-diagram-3 text-primary me-2"></i>Automated Schedule Waterfall Allocations
          </h5>
          <p class="text-muted small mb-0">Exact distribution of tendered funds against chronological installment milestones.</p>
        </div>
        <div class="card-body p-0 mt-3">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted small text-uppercase">
                <tr>
                  <th class="ps-4">Installment #</th>
                  <th>Due Date</th>
                  <th>Principal Portion</th>
                  <th>Markup Portion</th>
                  <th>Total Allocated</th>
                  <th>Schedule Balance</th>
                  <th class="text-end pe-4">Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($payment->allocations as $alloc)
                  <tr>
                    <td class="ps-4 font-monospace fw-bold text-dark">
                      #{{ $alloc->schedule->installment_number }}
                    </td>
                    <td>
                      {{ $alloc->schedule->due_date->format('d M, Y') }}
                    </td>
                    <td>Rs. {{ number_format($alloc->principal_component) }}</td>
                    <td>Rs. {{ number_format($alloc->markup_component) }}</td>
                    <td>
                      <strong class="text-primary">Rs. {{ number_format($alloc->amount_allocated) }}</strong>
                    </td>
                    <td>
                      Rs. {{ number_format($alloc->schedule->remaining_balance) }}
                    </td>
                    <td class="text-end pe-4">
                      {!! $alloc->schedule->status_badge !!}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Customer & Agreement Info -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-3 px-4">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person me-2 text-primary"></i>Customer Particulars
          </h6>
        </div>
        <div class="card-body p-4">
          <h5 class="fw-bold text-dark mb-1">{{ $payment->customer->full_name }}</h5>
          <div class="text-muted small font-monospace mb-2">CNIC: {{ $payment->customer->cnic }}</div>
          <div class="text-muted small">Mobile: {{ $payment->customer->mobile_primary }}</div>
          <div class="text-muted small">{{ $payment->customer->present_address }}</div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3 px-4">
          <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-file-earmark-text me-2 text-primary"></i>Financing Agreement
          </h6>
        </div>
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="{{ route('agreements.show', $payment->agreement->id) }}" class="fw-bold font-monospace text-primary text-decoration-none">
              {{ $payment->agreement->account_number }}
            </a>
            {!! $payment->agreement->status_badge !!}
          </div>
          <div class="small fw-semibold text-dark mb-3">
            {{ $payment->agreement->product->brand }} {{ $payment->agreement->product->model_name }}
          </div>

          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Remaining Balance:</span>
              <strong class="text-danger">Rs. {{ number_format($payment->agreement->remaining_balance) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Installments Paid:</span>
              <strong class="text-success">{{ $payment->agreement->paid_installments }} / {{ $payment->agreement->total_installments }}</strong>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
