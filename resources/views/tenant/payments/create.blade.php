<x-app-layout title="Record Customer Payment">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Record Installment Payment</h1>
      <p class="text-muted mb-0">Accept customer payments, apply automated waterfall allocation, and issue an official receipt.</p>
    </div>
    <div>
      <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Payments Ledger
      </a>
    </div>
  </div>

  @if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('payments.store') }}" method="POST">
    @csrf
    <div class="row g-4">
      <!-- Left Column: Payment Parameters -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-person-check text-primary me-2"></i>1. Select Installment Agreement
            </h5>
            <p class="text-muted small mb-0">Search and pick the customer's active financing agreement.</p>
          </div>
          <div class="card-body p-4">
            <div class="mb-3">
              <label for="installment_agreement_id" class="form-label fw-semibold text-dark">Active Agreement <span class="text-danger">*</span></label>
              <select name="installment_agreement_id" id="installment_agreement_id" class="form-select @error('installment_agreement_id') is-invalid @enderror" required onchange="this.form.action='{{ route('payments.create') }}'; this.form.method='GET'; this.form.submit();">
                <option value="">-- Select Active Agreement --</option>
                @foreach($activeAgreements as $agr)
                  <option value="{{ $agr->id }}"
                          {{ (old('installment_agreement_id', $selectedAgreement?->id) == $agr->id) ? 'selected' : '' }}>
                    {{ $agr->account_number }} &bull; {{ $agr->customer->full_name }} ({{ $agr->product->brand }} {{ $agr->product->model_name }})
                  </option>
                @endforeach
              </select>
              @error('installment_agreement_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-cash-stack text-primary me-2"></i>2. Transaction Details
            </h5>
            <p class="text-muted small mb-0">Specify the tendered amount and payment channel.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="amount" class="form-label fw-semibold text-dark">Payment Amount (PKR) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="any" name="amount" id="amount"
                         class="form-control form-control-lg fw-bold text-success @error('amount') is-invalid @enderror"
                         value="{{ old('amount', $selectedAgreement?->nextDueSchedule()?->remaining_balance ?? $selectedAgreement?->installment_amount) }}"
                         placeholder="e.g. 8500" required>
                </div>
                @error('amount')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="payment_method" class="form-label fw-semibold text-dark">Payment Channel <span class="text-danger">*</span></label>
                <select name="payment_method" id="payment_method" class="form-select form-select-lg @error('payment_method') is-invalid @enderror" required>
                  <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash at Showroom Drawer</option>
                  <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Direct Bank Deposit</option>
                  <option value="raast" {{ old('payment_method') === 'raast' ? 'selected' : '' }}>Raast Instant P2P</option>
                  <option value="easypaisa" {{ old('payment_method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa Mobile Wallet</option>
                  <option value="jazzcash" {{ old('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash Mobile Wallet</option>
                  <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Bank Cheque</option>
                </select>
                @error('payment_method')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="reference_number" class="form-label fw-semibold text-dark">Transaction / Cheque / Slip Ref</label>
                <input type="text" name="reference_number" id="reference_number"
                       class="form-control @error('reference_number') is-invalid @enderror"
                       value="{{ old('reference_number') }}"
                       placeholder="e.g. TXN-998271 or Chq # 40182">
                @error('reference_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="notes" class="form-label fw-semibold text-dark">Cashier Remarks</label>
                <input type="text" name="notes" id="notes" class="form-control"
                       value="{{ old('notes') }}"
                       placeholder="Optional cashier remarks">
              </div>
            </div>

            <div class="d-grid gap-2 mt-4 pt-3 border-top">
              <button type="submit" class="btn btn-primary btn-lg fw-bold" {{ !$selectedAgreement ? 'disabled' : '' }}>
                <i class="bi bi-check2-circle me-1"></i>Process & Allocate Payment
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Agreement Account Snapshot -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm position-sticky" style="top: 80px;">
          <div class="card-header bg-light py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>Agreement Summary
            </h5>
          </div>
          <div class="card-body p-4">
            @if($selectedAgreement)
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0 font-monospace">{{ $selectedAgreement->account_number }}</h5>
                {!! $selectedAgreement->status_badge !!}
              </div>

              <div class="p-3 bg-light rounded border mb-3">
                <div class="fw-semibold text-dark">{{ $selectedAgreement->customer->full_name }}</div>
                <div class="small text-muted font-monospace">{{ $selectedAgreement->customer->cnic }} &bull; {{ $selectedAgreement->customer->mobile_primary }}</div>
                <div class="small text-muted mt-1">{{ $selectedAgreement->product->brand }} {{ $selectedAgreement->product->model_name }}</div>
              </div>

              <ul class="list-group list-group-flush small mb-3">
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                  <span class="text-muted">Total Financed Value:</span>
                  <strong class="text-dark">Rs. {{ number_format($selectedAgreement->total_financed) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                  <span class="text-muted">Outstanding Remaining Balance:</span>
                  <strong class="text-danger fs-6">Rs. {{ number_format($selectedAgreement->remaining_balance) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                  <span class="text-muted">Standard Monthly Installment:</span>
                  <strong class="text-dark">Rs. {{ number_format($selectedAgreement->installment_amount) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                  <span class="text-muted">Installments Satisfied:</span>
                  <strong class="text-success">{{ $selectedAgreement->paid_installments }} / {{ $selectedAgreement->total_installments }}</strong>
                </li>
              </ul>

              @php
                $nextSched = $selectedAgreement->nextDueSchedule();
              @endphp

              @if($nextSched)
                <div class="alert alert-warning py-2 px-3 small mb-0">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong>Installment #{{ $nextSched->installment_number }} Due:</strong>
                    <span class="badge bg-warning text-dark">{{ $nextSched->due_date->format('d M, Y') }}</span>
                  </div>
                  <div>Amount Due: <strong class="text-dark">Rs. {{ number_format($nextSched->remaining_balance) }}</strong></div>
                  <button type="button" class="btn btn-sm btn-outline-dark mt-2 py-0" onclick="document.getElementById('amount').value='{{ $nextSched->remaining_balance }}'">
                    Fill Exact Due (Rs. {{ number_format($nextSched->remaining_balance) }})
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-primary mt-2 py-0" onclick="document.getElementById('amount').value='{{ $selectedAgreement->remaining_balance }}'">
                    Fill Full Payoff (Rs. {{ number_format($selectedAgreement->remaining_balance) }})
                  </button>
                </div>
              @endif
            @else
              <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-left-circle fs-1 d-block mb-2 text-primary"></i>
                <p class="mb-0">Select an active installment agreement on the left to view customer balance and next due installment.</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </form>
</x-app-layout>
