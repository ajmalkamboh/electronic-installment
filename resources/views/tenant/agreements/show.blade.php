<x-app-layout title="Agreement Dossier - {{ $agreement->account_number }}">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0 font-monospace">{{ $agreement->account_number }}</h1>
        {!! $agreement->status_badge !!}
      </div>
      <p class="text-muted mb-0">
        Showroom: <strong>{{ $agreement->branch->name }} ({{ $agreement->branch->code }})</strong> &bull;
        Created on {{ $agreement->created_at->format('d M, Y \a\t h:i A') }} by {{ $agreement->creator->name }}
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('documents.agreement', $agreement->id) }}" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-text me-1"></i>Documents &amp; Print Suite
      </a>
      <a href="{{ route('agreements.print', $agreement->id) }}" target="_blank" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Contract
      </a>

      @if($agreement->isDraft())
        <form action="{{ route('agreements.submit', $agreement->id) }}" method="POST" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-warning">
            <i class="bi bi-send me-1"></i>Submit for Review
          </button>
        </form>
      @endif

      @if($agreement->isUnderReview())
        <button type="button" class="btn btn-info text-dark" data-bs-toggle="modal" data-bs-target="#approveModal">
          <i class="bi bi-check-circle me-1"></i>Approve Agreement
        </button>
      @endif

      @if(in_array($agreement->status, ['draft', 'under_review', 'approved']))
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#downPaymentModal">
          <i class="bi bi-cash-stack me-1"></i>Record Down Payment
        </button>
      @endif

      @if($agreement->isApproved())
        @if($agreement->isDownPaymentSatisfied())
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#disburseModal">
            <i class="bi bi-box-seam me-1"></i>Disburse Merchandise
          </button>
        @else
          <button type="button" class="btn btn-secondary" disabled title="Down payment must be fully satisfied before disbursement">
            <i class="bi bi-lock me-1"></i>Disbursement Locked (Down Payment Pending)
          </button>
        @endif
      @endif

      @if(in_array($agreement->status, ['active', 'overdue', 'defaulted']))
        <a href="{{ route('payments.create', ['agreement_id' => $agreement->id]) }}" class="btn btn-success">
          <i class="bi bi-wallet2 me-1"></i>Collect Installment
        </a>
      @endif

      @if(!in_array($agreement->status, ['completed', 'cancelled']))
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
      @endif
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

  <!-- Lifecycle Progress Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <h6 class="text-muted text-uppercase small fw-bold mb-3">Agreement Lifecycle Progression</h6>
      <div class="position-relative m-3">
        <div class="progress" style="height: 4px;">
          @php
            $progressPct = match($agreement->status) {
              'draft' => 15,
              'under_review' => 35,
              'approved' => 60,
              'active' => 85,
              'completed' => 100,
              default => 0,
            };
          @endphp
          <div class="progress-bar {{ $agreement->isCancelled() ? 'bg-danger' : 'bg-primary' }}"
               role="progressbar" style="width: {{ $agreement->isCancelled() ? '100' : $progressPct }}%;"></div>
        </div>
        <div class="d-flex justify-content-between position-absolute top-50 start-0 translate-middle-y w-100">
          <button type="button" class="btn btn-sm {{ $agreement->status ? 'btn-primary' : 'btn-light' }} rounded-pill" style="width: 2rem; height:2rem; padding: 0;">1</button>
          <button type="button" class="btn btn-sm {{ in_array($agreement->status, ['under_review', 'approved', 'active', 'completed']) ? 'btn-primary' : 'btn-light' }} rounded-pill" style="width: 2rem; height:2rem; padding: 0;">2</button>
          <button type="button" class="btn btn-sm {{ in_array($agreement->status, ['approved', 'active', 'completed']) ? 'btn-primary' : 'btn-light' }} rounded-pill" style="width: 2rem; height:2rem; padding: 0;">3</button>
          <button type="button" class="btn btn-sm {{ in_array($agreement->status, ['active', 'completed']) ? 'btn-primary' : 'btn-light' }} rounded-pill" style="width: 2rem; height:2rem; padding: 0;">4</button>
          <button type="button" class="btn btn-sm {{ $agreement->status === 'completed' ? 'btn-success' : 'btn-light' }} rounded-pill" style="width: 2rem; height:2rem; padding: 0;">5</button>
        </div>
      </div>
      <div class="d-flex justify-content-between text-muted small mt-3 px-1">
        <span>Draft</span>
        <span>Under Review</span>
        <span>Approved</span>
        <span>Active (Disbursed)</span>
        <span>Completed</span>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Parties & Merchandise -->
    <div class="col-lg-7">
      <!-- Repayment Schedule Card -->
      @if($agreement->schedules->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-calendar-check text-primary me-2"></i>Repayment Schedule (Amortization Plan)
              </h5>
              <p class="text-muted small mb-0">Installment schedule, due dates, paid status and balance</p>
            </div>
            @if(in_array($agreement->status, ['active', 'overdue', 'defaulted']))
              <a href="{{ route('payments.create', ['agreement_id' => $agreement->id]) }}" class="btn btn-sm btn-success">
                <i class="bi bi-wallet2 me-1"></i>Collect Installment
              </a>
            @endif
          </div>
          <div class="card-body p-0 mt-3">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                  <tr>
                    <th class="ps-4">Inst #</th>
                    <th>Due Date</th>
                    <th>Principal</th>
                    <th>Markup</th>
                    <th>Total Due</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th class="pe-4 text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($agreement->schedules as $sched)
                    <tr class="{{ $sched->isOverdue() ? 'table-danger-subtle' : '' }}">
                      <td class="ps-4 fw-bold font-monospace">#{{ $sched->installment_number }}</td>
                      <td>
                        <div class="fw-semibold text-dark">{{ $sched->due_date->format('d M, Y') }}</div>
                        @if($sched->isOverdue())
                          <span class="badge bg-danger small">Overdue</span>
                        @endif
                      </td>
                      <td class="small">Rs. {{ number_format($sched->principal_amount) }}</td>
                      <td class="small text-muted">Rs. {{ number_format($sched->markup_amount) }}</td>
                      <td class="fw-bold text-dark">Rs. {{ number_format($sched->total_amount) }}</td>
                      <td class="text-success fw-semibold small">Rs. {{ number_format($sched->paid_amount) }}</td>
                      <td class="fw-bold {{ $sched->remaining_balance > 0 ? 'text-primary' : 'text-muted' }}">
                        Rs. {{ number_format($sched->remaining_balance) }}
                      </td>
                      <td>{!! $sched->status_badge !!}</td>
                      <td class="pe-4 text-end">
                        @if(!$sched->isPaid() && in_array($agreement->status, ['active', 'overdue', 'defaulted']))
                          <a href="{{ route('payments.create', ['agreement_id' => $agreement->id, 'amount' => $sched->remaining_balance]) }}"
                             class="btn btn-sm btn-outline-primary"
                             title="Record payment for this installment">
                            <i class="bi bi-cash me-1"></i>Pay
                          </a>
                        @elseif($sched->isPaid())
                          <span class="text-success small"><i class="bi bi-check2-circle me-1"></i>Settled</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <!-- Payment Receipts & Transaction Ledger -->
      @if($agreement->payments->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-receipt text-primary me-2"></i>Payment Receipts & Ledger
            </h5>
            <p class="text-muted small mb-0">Cashier collections & waterfall allocations recorded for this account</p>
          </div>
          <div class="card-body p-0 mt-3">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                  <tr>
                    <th class="ps-4">Receipt #</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Cashier</th>
                    <th>Status</th>
                    <th class="pe-4 text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($agreement->payments as $p)
                    <tr>
                      <td class="ps-4 fw-bold font-monospace">
                        <a href="{{ route('payments.show', $p->id) }}" class="text-primary text-decoration-none">
                          {{ $p->payment_number }}
                        </a>
                      </td>
                      <td>
                        <div class="text-dark">{{ $p->payment_date->format('d M, Y') }}</div>
                        <small class="text-muted">{{ $p->payment_date->format('h:i A') }}</small>
                      </td>
                      <td>{!! $p->method_badge !!}</td>
                      <td class="fw-bold text-success fs-6">Rs. {{ number_format($p->amount) }}</td>
                      <td>
                        <div class="small fw-semibold text-dark">{{ $p->cashier?->name ?? 'Cashier' }}</div>
                        <span class="text-muted small font-monospace">{{ $p->reference_number ?? '-' }}</span>
                      </td>
                      <td>{!! $p->status_badge !!}</td>
                      <td class="pe-4 text-end">
                        <a href="{{ route('payments.show', $p->id) }}" class="btn btn-sm btn-outline-primary me-1" title="View Dossier">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('payments.print', $p->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Receipt">
                          <i class="bi bi-printer"></i>
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif

      <!-- Customer Information -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person text-primary me-2"></i>Purchaser Particulars
          </h5>
          <a href="{{ route('customers.show', $agreement->customer->id) }}" class="btn btn-sm btn-outline-primary">
            Customer Profile
          </a>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <span class="text-muted small d-block">Full Legal Name:</span>
              <strong class="text-dark fs-6">{{ $agreement->customer->full_name }}</strong>
              <div class="text-muted small">S/O or W/O: {{ $agreement->customer->father_or_husband_name }}</div>
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">National CNIC:</span>
              <strong class="text-dark font-monospace fs-6">{{ $agreement->customer->cnic }}</strong>
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">Primary Contact:</span>
              <strong class="text-dark">{{ $agreement->customer->mobile_primary }}</strong>
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">Credit Rating:</span>
              <span class="badge bg-light text-dark border">
                Score: {{ $agreement->customer->credit_score }} / 100
              </span>
            </div>
            <div class="col-12">
              <span class="text-muted small d-block">Present Residence:</span>
              <div class="text-dark">{{ $agreement->customer->present_address }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Legal Guarantors -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-shield-check text-primary me-2"></i>Contract Guarantors
          </h5>
        </div>
        <div class="card-body p-4">
          @if($agreement->guarantors->isNotEmpty())
            <div class="list-group list-group-flush">
              @foreach($agreement->guarantors as $g)
                <div class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-start">
                  <div>
                    <div class="d-flex align-items-center gap-2">
                      <strong class="text-dark">{{ $g->full_name }}</strong>
                      @if($g->pivot->is_primary)
                        <span class="badge bg-primary-subtle text-primary small">Primary</span>
                      @endif
                    </div>
                    <div class="text-muted small mt-1">
                      Relationship: <strong>{{ $g->relationship }}</strong> &bull;
                      CNIC: <span class="font-monospace">{{ $g->cnic }}</span> &bull;
                      Mobile: {{ $g->mobile }}
                    </div>
                    <div class="text-muted small">{{ $g->address }}</div>
                  </div>
                  <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-patch-check me-1"></i>Verified
                  </span>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-muted mb-0 small">No guarantors explicitly bound to this agreement.</p>
          @endif
        </div>
      </div>

      <!-- Allocated Merchandise & Serial/IMEI -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-box-seam text-primary me-2"></i>Financed Merchandise & Hardware Unit
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="d-flex align-items-start justify-content-between mb-3">
            <div>
              <h5 class="fw-bold text-dark mb-1">{{ $agreement->product->brand }} {{ $agreement->product->model_name }}</h5>
              <span class="badge bg-light text-dark border">{{ $agreement->product->category?->name ?? 'General Merchandise' }}</span>
              <span class="text-muted small ms-2">SKU: {{ $agreement->product->sku }}</span>
            </div>
            @if($agreement->serializedItem)
              <span class="badge {{ $agreement->serializedItem->status === 'disbursed' ? 'bg-success' : 'bg-warning text-dark' }}">
                Hardware: {{ ucfirst($agreement->serializedItem->status) }}
              </span>
            @endif
          </div>

          @if($agreement->serializedItem)
            <div class="bg-light p-3 rounded border">
              <div class="row g-2 font-monospace small">
                @if($agreement->serializedItem->imei_1)
                  <div class="col-md-6">
                    <span class="text-muted d-block">Primary IMEI (SIM 1):</span>
                    <strong class="text-dark fs-6">{{ $agreement->serializedItem->imei_1 }}</strong>
                  </div>
                @endif
                @if($agreement->serializedItem->imei_2)
                  <div class="col-md-6">
                    <span class="text-muted d-block">Secondary IMEI (SIM 2):</span>
                    <strong class="text-dark fs-6">{{ $agreement->serializedItem->imei_2 }}</strong>
                  </div>
                @endif
                @if($agreement->serializedItem->serial_number)
                  <div class="col-md-6">
                    <span class="text-muted d-block">Factory Serial Number:</span>
                    <strong class="text-dark">{{ $agreement->serializedItem->serial_number }}</strong>
                  </div>
                @endif
              </div>
            </div>
          @else
            <div class="text-muted small">Standard non-serialized inventory product.</div>
          @endif

          @if($agreement->handover_notes)
            <div class="mt-3 p-3 bg-success-subtle rounded border border-success-subtle">
              <span class="text-success fw-bold d-block small">Handover Delivery Note:</span>
              <div class="text-dark small">{{ $agreement->handover_notes }}</div>
            </div>
          @endif
        </div>
      </div>

      <!-- Agreement Terms Snapshot -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-card-text text-primary me-2"></i>Legal Terms & Undertaking Snapshot
          </h5>
        </div>
        <div class="card-body p-4">
          <pre class="bg-light p-3 rounded border text-muted small mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $agreement->terms_conditions_snapshot }}</pre>
        </div>
      </div>
    </div>

    <!-- Right Column: Financial Breakdown & Milestones -->
    <div class="col-lg-5">
      <!-- Financial Breakdown Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3 px-4">
          <h5 class="fw-bold mb-0">
            <i class="bi bi-wallet2 me-2"></i>Locked Financial Terms
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="text-center py-2 mb-3 border-bottom">
            <span class="text-muted small d-block text-uppercase">Monthly Installment</span>
            <h2 class="display-6 fw-bold text-primary mb-0">Rs. {{ number_format($agreement->installment_amount) }}</h2>
            <small class="text-muted">{{ $agreement->tenure_months }} Equal Monthly Installments</small>
          </div>

          <ul class="list-group list-group-flush small mb-3">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Merchandise Retail Cash Price:</span>
              <strong class="text-dark">Rs. {{ number_format($agreement->cash_price) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Agreed Down Payment:</span>
              <strong class="text-dark">Rs. {{ number_format($agreement->down_payment_amount) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Financed Principal:</span>
              <strong class="text-dark">Rs. {{ number_format($agreement->financed_principal) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Annual Markup Rate:</span>
              <strong class="text-dark">{{ number_format($agreement->markup_rate_pct, 2) }}% (Flat)</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Total Markup Amount:</span>
              <strong class="text-success">Rs. {{ number_format($agreement->markup_amount) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Total Financed Balance:</span>
              <strong class="text-dark">Rs. {{ number_format($agreement->total_financed) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2 bg-light rounded px-2 mt-1">
              <span class="fw-bold text-dark">Total Agreement Value:</span>
              <strong class="fw-bold text-primary fs-6">Rs. {{ number_format($agreement->total_payable) }}</strong>
            </li>
          </ul>

          <!-- Down Payment Collection Status -->
          <div class="border rounded p-3 mb-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="small fw-bold text-dark">Down Payment Status</span>
              <span class="small fw-bold {{ $agreement->isDownPaymentSatisfied() ? 'text-success' : 'text-warning' }}">
                Rs. {{ number_format($agreement->down_payment_paid) }} / Rs. {{ number_format($agreement->down_payment_amount) }}
              </span>
            </div>
            @php
              $dpPct = $agreement->down_payment_amount > 0
                ? min(100, round(($agreement->down_payment_paid / $agreement->down_payment_amount) * 100))
                : 100;
            @endphp
            <div class="progress" style="height: 6px;">
              <div class="progress-bar {{ $agreement->isDownPaymentSatisfied() ? 'bg-success' : 'bg-warning' }}"
                   role="progressbar" style="width: {{ $dpPct }}%;"></div>
            </div>
            @if(!$agreement->isDownPaymentSatisfied())
              <small class="text-danger d-block mt-2">
                <i class="bi bi-exclamation-circle me-1"></i>Deficit of Rs. {{ number_format($agreement->down_payment_deficit) }} must be received before merchandise disbursement.
              </small>
            @else
              <small class="text-success d-block mt-2">
                <i class="bi bi-check-circle me-1"></i>Down payment satisfied. Receipt: {{ $agreement->down_payment_receipt_ref ?? 'Recorded' }}
              </small>
            @endif
          </div>

          <!-- Agreement Repayment Progress (when active/completed) -->
          @if(in_array($agreement->status, ['active', 'overdue', 'defaulted', 'completed']))
            <div class="border rounded p-3 mb-3 bg-light">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold text-dark">Remaining Financed Balance</span>
                <span class="small fw-bold text-primary">
                  Rs. {{ number_format($agreement->remaining_balance) }} / Rs. {{ number_format($agreement->total_financed) }}
                </span>
              </div>
              @php
                $repaidAmount = max(0, $agreement->total_financed - $agreement->remaining_balance);
                $repaidPct = $agreement->total_financed > 0
                  ? min(100, round(($repaidAmount / $agreement->total_financed) * 100))
                  : 0;
              @endphp
              <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $repaidPct }}%;"></div>
              </div>
              <div class="d-flex justify-content-between text-muted small mt-1">
                <span>{{ $repaidPct }}% Recovered</span>
                <span>{{ $agreement->paid_installments }} / {{ $agreement->total_installments }} Installments</span>
              </div>
            </div>
          @endif

          <!-- Schedule Milestones -->
          <div class="border-top pt-3">
            <h6 class="small fw-bold text-muted text-uppercase mb-2">Schedule Milestones</h6>
            <div class="row g-2 small">
              <div class="col-6">
                <span class="text-muted d-block">Start Date:</span>
                <strong>{{ $agreement->start_date->format('d M, Y') }}</strong>
              </div>
              <div class="col-6">
                <span class="text-muted d-block">First Due Date:</span>
                <strong>{{ $agreement->first_due_date->format('d M, Y') }}</strong>
              </div>
              <div class="col-6">
                <span class="text-muted d-block">Maturity Date:</span>
                <strong>{{ $agreement->maturity_date->format('d M, Y') }}</strong>
              </div>
              <div class="col-6">
                <span class="text-muted d-block">Installments Paid:</span>
                <strong>{{ $agreement->paid_installments }} / {{ $agreement->total_installments }}</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Audit Milestones Timeline -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-clock-history text-primary me-2"></i>Milestone Audit Log
          </h5>
        </div>
        <div class="card-body p-4">
          <ul class="list-unstyled mb-0 small">
            <li class="mb-3 d-flex gap-2">
              <i class="bi bi-circle-fill text-primary mt-1" style="font-size: 8px;"></i>
              <div>
                <strong class="d-block text-dark">Contract Drafted</strong>
                <span class="text-muted">{{ $agreement->created_at->format('d M, Y h:i A') }} by {{ $agreement->creator->name }}</span>
              </div>
            </li>
            @if($agreement->submitted_at)
              <li class="mb-3 d-flex gap-2">
                <i class="bi bi-circle-fill text-warning mt-1" style="font-size: 8px;"></i>
                <div>
                  <strong class="d-block text-dark">Submitted for Underwriting</strong>
                  <span class="text-muted">{{ $agreement->submitted_at->format('d M, Y h:i A') }}</span>
                </div>
              </li>
            @endif
            @if($agreement->approved_at)
              <li class="mb-3 d-flex gap-2">
                <i class="bi bi-circle-fill text-info mt-1" style="font-size: 8px;"></i>
                <div>
                  <strong class="d-block text-dark">Approved by Underwriter</strong>
                  <span class="text-muted">{{ $agreement->approved_at->format('d M, Y h:i A') }} by {{ $agreement->approvedBy?->name ?? 'Manager' }}</span>
                  @if($agreement->approval_notes)
                    <div class="text-muted fst-italic">"{{ $agreement->approval_notes }}"</div>
                  @endif
                </div>
              </li>
            @endif
            @if($agreement->activated_at)
              <li class="mb-3 d-flex gap-2">
                <i class="bi bi-circle-fill text-success mt-1" style="font-size: 8px;"></i>
                <div>
                  <strong class="d-block text-dark">Disbursed & Activated</strong>
                  <span class="text-muted">{{ $agreement->activated_at->format('d M, Y h:i A') }} by {{ $agreement->disbursedBy?->name ?? 'Officer' }}</span>
                </div>
              </li>
            @endif
            @if($agreement->cancelled_at)
              <li class="d-flex gap-2">
                <i class="bi bi-circle-fill text-danger mt-1" style="font-size: 8px;"></i>
                <div>
                  <strong class="d-block text-danger">Agreement Cancelled</strong>
                  <span class="text-muted">{{ $agreement->cancelled_at->format('d M, Y h:i A') }}</span>
                  <div class="text-muted fst-italic">Reason: "{{ $agreement->cancellation_reason }}"</div>
                </div>
              </li>
            @endif
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Approve Agreement -->
  <div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('agreements.approve', $agreement->id) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Approve Installment Agreement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">You are approving contract <strong>{{ $agreement->account_number }}</strong> for purchaser <strong>{{ $agreement->customer->full_name }}</strong>.</p>
          <div class="mb-3">
            <label for="approval_notes" class="form-label fw-semibold">Manager Underwriting Notes</label>
            <textarea name="approval_notes" id="approval_notes" rows="3" class="form-control"
                      placeholder="e.g. Verified residence and salary slips. Approved for showroom handover upon down payment."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Authorize & Approve</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Record Down Payment -->
  <div class="modal fade" id="downPaymentModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('agreements.down-payment', $agreement->id) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Record Customer Down Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info py-2 small mb-3">
            Total Required: <strong>Rs. {{ number_format($agreement->down_payment_amount) }}</strong> &bull;
            Remaining Deficit: <strong>Rs. {{ number_format($agreement->down_payment_deficit) }}</strong>
          </div>
          <div class="mb-3">
            <label for="amount" class="form-label fw-semibold">Payment Amount (PKR) <span class="text-danger">*</span></label>
            <input type="number" name="amount" id="amount" class="form-control"
                   value="{{ $agreement->down_payment_deficit > 0 ? $agreement->down_payment_deficit : $agreement->down_payment_amount }}" required>
          </div>
          <div class="mb-3">
            <label for="payment_method" class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
            <select name="payment_method" id="payment_method" class="form-select" required>
              <option value="cash">Cash in Drawer</option>
              <option value="bank_transfer">Direct Bank Transfer</option>
              <option value="raast">Raast Instant Pay</option>
              <option value="easypaisa">Easypaisa</option>
              <option value="jazzcash">JazzCash</option>
              <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="receipt_ref" class="form-label fw-semibold">Cashier Receipt # / Transaction Ref</label>
            <input type="text" name="receipt_ref" id="receipt_ref" class="form-control" placeholder="e.g. RCPT-2026-091">
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label fw-semibold">Cashier Remarks</label>
            <input type="text" name="notes" id="notes" class="form-control" placeholder="Optional notes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Record Collection</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Disburse Merchandise -->
  <div class="modal fade" id="disburseModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('agreements.disburse', $agreement->id) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold">Disburse Merchandise to Customer</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">
            This will officially disburse the hardware to purchaser <strong>{{ $agreement->customer->full_name }}</strong>,
            shift serial state to <strong>disbursed</strong>, decrement showroom inventory, and activate the installment contract.
          </p>
          <div class="mb-3">
            <label for="handover_notes" class="form-label fw-semibold">Handover / Packaging Remarks</label>
            <textarea name="handover_notes" id="handover_notes" rows="3" class="form-control"
                      placeholder="e.g. Unit inspected with customer, original seal opened in presence, warranty booklet and accessories provided."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Confirm Handover & Activate Contract</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Cancel Agreement -->
  <div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('agreements.cancel', $agreement->id) }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title fw-bold">Cancel Agreement</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">
            Are you sure you want to cancel agreement <strong>{{ $agreement->account_number }}</strong>?
            Any reserved showroom hardware unit will be released back to <strong>in_stock</strong> inventory.
          </p>
          <div class="mb-3">
            <label for="cancellation_reason" class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
            <textarea name="cancellation_reason" id="cancellation_reason" rows="3" class="form-control" required
                      placeholder="e.g. Customer decided not to proceed; down payment not collected."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
          <button type="submit" class="btn btn-danger">Cancel Agreement</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
