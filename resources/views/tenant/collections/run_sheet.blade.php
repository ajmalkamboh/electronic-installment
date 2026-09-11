<x-app-layout title="Daily Collection Run Sheet - {{ $selectedOfficer->name }}">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">Daily Collection Run Sheet</h1>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6">
          <i class="bi bi-person me-1"></i>{{ $selectedOfficer->name }}
        </span>
      </div>
      <p class="text-muted mb-0">
        Showroom: <strong>{{ $currentBranch?->name ?? 'All Showrooms' }}</strong> &bull;
        Date: <strong>{{ Carbon\Carbon::parse($date)->format('d F, Y (l)') }}</strong>
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('collections.run-sheet.print', ['officer_id' => $selectedOfficer->id, 'date' => $date]) }}" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Run Sheet
      </a>
      <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-1"></i>Back to Command Center
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

  <!-- Officer Filter & Date Switcher -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('collections.run-sheet') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
          <label for="officer_id" class="form-label small fw-semibold text-muted mb-1">Collection Officer</label>
          <select name="officer_id" id="officer_id" class="form-select" onchange="this.form.submit()">
            @foreach($officers as $off)
              <option value="{{ $off->id }}" {{ $selectedOfficer->id === $off->id ? 'selected' : '' }}>
                {{ $off->name }} ({{ $off->role }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label for="date" class="form-label small fw-semibold text-muted mb-1">Itinerary Date</label>
          <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <a href="{{ route('collections.run-sheet') }}" class="btn btn-outline-secondary w-100">
            Reset to Today
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- Run Sheet Items List -->
  @if($runSheet->isNotEmpty())
    <div class="row g-4">
      @foreach($runSheet as $index => $assignment)
        @php
          $agr = $assignment->agreement;
          $cust = $agr->customer;
          $unpaidSchedules = $agr->schedules;
          $overdueSchedules = $unpaidSchedules->filter(fn($s) => $s->isOverdue());
          $totalOverdueAmount = $overdueSchedules->sum('remaining_balance');
          $nextDue = $unpaidSchedules->first();
          $targetAmount = $totalOverdueAmount > 0 ? $totalOverdueAmount : ($nextDue?->remaining_balance ?? $agr->installment_amount);
        @endphp
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm h-100 {{ $assignment->priority === 'urgent' ? 'border-start border-danger border-4' : '' }}">
            <div class="card-body p-4">
              <!-- Top Row: Account & Priority -->
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <span class="badge bg-light text-dark border font-monospace me-1">#{{ $index + 1 }}</span>
                  <a href="{{ route('agreements.show', $agr->id) }}" class="fw-bold font-monospace text-primary text-decoration-none fs-6">
                    {{ $agr->account_number }}
                  </a>
                </div>
                <div>
                  {!! $assignment->priority_badge !!}
                  {!! $agr->status_badge !!}
                </div>
              </div>

              <!-- Customer Info -->
              <h5 class="fw-bold text-dark mb-1">{{ $cust->full_name }}</h5>
              <div class="text-muted small mb-2">
                <i class="bi bi-card-text me-1"></i>CNIC: <span class="font-monospace">{{ $cust->cnic }}</span>
              </div>

              <!-- Contact & Location Box -->
              <div class="bg-light p-3 rounded border mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-bold text-dark font-monospace">
                    <i class="bi bi-telephone-fill text-primary me-1"></i>{{ $cust->mobile_primary }}
                  </div>
                  <div class="d-flex gap-1">
                    <a href="tel:{{ $cust->mobile_primary }}" class="btn btn-sm btn-outline-primary" title="Call Customer">
                      <i class="bi bi-telephone"></i> Call
                    </a>
                    @php
                      $cleanPhone = preg_replace('/[^0-9]/', '', $cust->mobile_primary);
                      if (str_starts_with($cleanPhone, '0')) {
                        $cleanPhone = '92' . substr($cleanPhone, 1);
                      }
                    @endphp
                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="btn btn-sm btn-outline-success" title="WhatsApp">
                      <i class="bi bi-whatsapp"></i>
                    </a>
                  </div>
                </div>
                <div class="small text-muted">
                  <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $cust->present_address }}
                </div>
              </div>

              <!-- Financed Merchandise & Financial Target -->
              <div class="row g-2 mb-3 small">
                <div class="col-6">
                  <span class="text-muted d-block">Financed Device:</span>
                  <strong class="text-dark">{{ $agr->product->brand }} {{ $agr->product->model_name }}</strong>
                </div>
                <div class="col-6">
                  <span class="text-muted d-block">Installment Target:</span>
                  <strong class="text-danger fs-6">Rs. {{ number_format($targetAmount) }}</strong>
                </div>
                <div class="col-6">
                  <span class="text-muted d-block">Contract Balance:</span>
                  <span>Rs. {{ number_format($agr->remaining_balance) }}</span>
                </div>
                <div class="col-6">
                  <span class="text-muted d-block">Overdue Count:</span>
                  <span class="{{ $overdueSchedules->count() > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                    {{ $overdueSchedules->count() }} Missed Dues
                  </span>
                </div>
              </div>

              <!-- Previous Interaction Note -->
              @if($agr->latestCollectionLog)
                <div class="p-2 bg-warning-subtle rounded border border-warning-subtle small mb-3">
                  <div class="d-flex justify-content-between">
                    <span class="fw-bold text-dark">Last Interaction ({{ $agr->latestCollectionLog->visit_date->format('d M') }}):</span>
                    {!! $agr->latestCollectionLog->interaction_status_badge !!}
                  </div>
                  @if($agr->latestCollectionLog->notes)
                    <div class="text-muted mt-1 fst-italic">"{{ $agr->latestCollectionLog->notes }}"</div>
                  @endif
                  @if($agr->latestCollectionLog->promise_to_pay_date)
                    <div class="text-danger fw-bold mt-1">
                      PTP Promised: Rs. {{ number_format($agr->latestCollectionLog->promised_amount ?? 0) }} by {{ $agr->latestCollectionLog->promise_to_pay_date->format('d M, Y') }}
                    </div>
                  @endif
                </div>
              @endif

              @if($assignment->notes)
                <div class="small text-muted mb-3">
                  <strong>Assignment Instructions:</strong> {{ $assignment->notes }}
                </div>
              @endif

              <!-- Field Action Buttons -->
              <div class="d-flex gap-2 pt-2 border-top">
                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1"
                        data-bs-toggle="modal" data-bs-target="#visitLogModal"
                        data-agreement-id="{{ $agr->id }}"
                        data-account-number="{{ $agr->account_number }}"
                        data-customer-name="{{ $cust->full_name }}"
                        data-target-amount="{{ $targetAmount }}">
                  <i class="bi bi-pencil-square me-1"></i>Log Visit / PTP
                </button>
                <button type="button" class="btn btn-success btn-sm flex-grow-1"
                        data-bs-toggle="modal" data-bs-target="#fieldPaymentModal"
                        data-agreement-id="{{ $agr->id }}"
                        data-account-number="{{ $agr->account_number }}"
                        data-customer-name="{{ $cust->full_name }}"
                        data-target-amount="{{ $targetAmount }}">
                  <i class="bi bi-cash me-1"></i>Collect Cash
                </button>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="card border-0 shadow-sm py-5 text-center">
      <div class="card-body">
        <i class="bi bi-clipboard2-check fs-1 text-muted d-block mb-3"></i>
        <h4 class="fw-bold">No Active Accounts in Run Sheet</h4>
        <p class="text-muted mb-3">There are no active collection assignments allocated to {{ $selectedOfficer->name }} for this date.</p>
        <a href="{{ route('collections.assignments') }}" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i>Allocate Accounts
        </a>
      </div>
    </div>
  @endif

  <!-- Modal: Log Field Visit / PTP -->
  <div class="modal fade" id="visitLogModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('collections.logs.store') }}" method="POST" class="modal-content">
        @csrf
        <input type="hidden" name="installment_agreement_id" id="log_agreement_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Log Customer Field Visit / Interaction</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2 small mb-3">
            Account: <strong id="log_account_num">-</strong> &bull; Customer: <strong id="log_customer_name">-</strong>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label for="interaction_type" class="form-label fw-semibold small">Interaction Type <span class="text-danger">*</span></label>
              <select name="interaction_type" id="interaction_type" class="form-select" required>
                <option value="field_visit">In-Person Field Visit</option>
                <option value="phone_call">Telephonic Follow-Up</option>
                <option value="guarantor_contact">Guarantor Visit / Call</option>
                <option value="showroom_visit">Customer Showroom Visit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="interaction_status" class="form-label fw-semibold small">Interaction Outcome <span class="text-danger">*</span></label>
              <select name="interaction_status" id="interaction_status" class="form-select" required onchange="togglePtpFields(this.value)">
                <option value="met_customer">Met Customer (Discussed)</option>
                <option value="customer_absent">Customer Absent / Door Locked</option>
                <option value="promise_to_pay" selected>Promise to Pay (PTP)</option>
                <option value="refused_to_pay">Refused to Pay (Hostile/Delinquent)</option>
                <option value="dispute_raised">Dispute Raised on Product/Markup</option>
              </select>
            </div>
          </div>

          <!-- PTP Specific Fields -->
          <div id="ptpFieldsContainer" class="p-3 bg-light rounded border mb-3">
            <h6 class="small fw-bold text-dark mb-2">Promise to Pay (PTP) Commitment</h6>
            <div class="row g-2">
              <div class="col-md-6">
                <label for="promise_to_pay_date" class="form-label small fw-semibold">Promised Date</label>
                <input type="date" name="promise_to_pay_date" id="promise_to_pay_date" class="form-control"
                       min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
              </div>
              <div class="col-md-6">
                <label for="promised_amount" class="form-label small fw-semibold">Promised Amount (PKR)</label>
                <input type="number" name="promised_amount" id="promised_amount" class="form-control" placeholder="PKR">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="location_notes" class="form-label fw-semibold small">GPS / Location Check-in Remarks</label>
            <input type="text" name="location_notes" id="location_notes" class="form-control"
                   placeholder="e.g. Visited house #14, Street 2, verified electricity meter.">
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label fw-semibold small">Recovery Visit Detailed Notes</label>
            <textarea name="notes" id="notes" rows="3" class="form-control"
                      placeholder="e.g. Customer salary delayed; promised to clear installment on 15th."></textarea>
          </div>

          <div class="mb-3">
            <label for="follow_up_date" class="form-label fw-semibold small">Next Follow-Up Date</label>
            <input type="date" name="follow_up_date" id="follow_up_date" class="form-control" min="{{ date('Y-m-d') }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Field Log</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Collect Field Cash Payment -->
  <div class="modal fade" id="fieldPaymentModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('collections.payments.store') }}" method="POST" class="modal-content">
        @csrf
        <input type="hidden" name="installment_agreement_id" id="pay_agreement_id">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold">Record Field Cash Collection</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-warning py-2 small mb-3">
            <i class="bi bi-shield-exclamation me-1"></i>
            Dual-Custody Notice: Cash collected will be recorded in <strong>submitted</strong> status under your custody until physically handed over to the Showroom Cashier.
          </div>
          <div class="alert alert-light border small py-2 mb-3">
            Account: <strong id="pay_account_num">-</strong> &bull; Customer: <strong id="pay_customer_name">-</strong>
          </div>

          <div class="mb-3">
            <label for="pay_amount" class="form-label fw-semibold">Cash Amount Collected (PKR) <span class="text-danger">*</span></label>
            <input type="number" name="amount" id="pay_amount" class="form-control form-control-lg fw-bold text-success" required>
          </div>

          <div class="mb-3">
            <label for="pay_ref" class="form-label fw-semibold">Paper Receipt # / Manual Voucher Ref</label>
            <input type="text" name="reference_number" id="pay_ref" class="form-control" placeholder="e.g. BOOK-04-RCPT-88">
          </div>

          <div class="mb-3">
            <label for="pay_notes" class="form-label fw-semibold">Cashier Handover Notes</label>
            <textarea name="notes" id="pay_notes" rows="2" class="form-control" placeholder="e.g. Received 17,600 in 5000/1000 currency notes."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Record Collection (Submitted)</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function togglePtpFields(status) {
      const container = document.getElementById('ptpFieldsContainer');
      if (status === 'promise_to_pay') {
        container.style.display = 'block';
      } else {
        container.style.display = 'none';
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      const visitModal = document.getElementById('visitLogModal');
      if (visitModal) {
        visitModal.addEventListener('show.bs.modal', function (event) {
          const btn = event.relatedTarget;
          document.getElementById('log_agreement_id').value = btn.getAttribute('data-agreement-id');
          document.getElementById('log_account_num').textContent = btn.getAttribute('data-account-number');
          document.getElementById('log_customer_name').textContent = btn.getAttribute('data-customer-name');
          document.getElementById('promised_amount').value = btn.getAttribute('data-target-amount');
        });
      }

      const payModal = document.getElementById('fieldPaymentModal');
      if (payModal) {
        payModal.addEventListener('show.bs.modal', function (event) {
          const btn = event.relatedTarget;
          document.getElementById('pay_agreement_id').value = btn.getAttribute('data-agreement-id');
          document.getElementById('pay_account_num').textContent = btn.getAttribute('data-account-number');
          document.getElementById('pay_customer_name').textContent = btn.getAttribute('data-customer-name');
          document.getElementById('pay_amount').value = btn.getAttribute('data-target-amount');
        });
      }
    });
  </script>
</x-app-layout>
