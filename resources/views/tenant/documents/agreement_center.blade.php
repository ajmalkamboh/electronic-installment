<x-app-layout title="Document Suite - {{ $agreement->account_number }}">
  <!-- Header with Contract Info & Breadcrumb -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0 font-monospace">{{ $agreement->account_number }}</h1>
        {!! $agreement->status_badge !!}
      </div>
      <p class="text-muted mb-0">
        Legal Document & Printing Suite for <strong>{{ $agreement->customer?->full_name }}</strong> &bull;
        {{ $agreement->product?->name }} ({{ $agreement->branch?->name }})
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('documents.hub') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Document Hub
      </a>
      <a href="{{ route('agreements.show', $agreement) }}" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-text me-1"></i>Contract Dossier
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Top Contract Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <div class="row g-3 align-items-center text-center text-md-start">
        <div class="col-md-3">
          <small class="text-muted d-block">Customer CNIC</small>
          <span class="fw-bold font-monospace text-dark">{{ $agreement->customer?->cnic }}</span>
        </div>
        <div class="col-md-3">
          <small class="text-muted d-block">Merchandise Serial / IMEI</small>
          <span class="fw-bold font-monospace text-primary">{{ $agreement->serializedItem?->serial_number ?? 'NON-SERIALIZED' }}</span>
        </div>
        <div class="col-md-3">
          <small class="text-muted d-block">Total Financed Payable</small>
          <span class="fw-bold text-dark">PKR {{ number_format($agreement->total_payable, 2) }}</span>
        </div>
        <div class="col-md-3 text-md-end">
          <small class="text-muted d-block">Remaining Balance</small>
          <span class="fw-bold {{ (float)$agreement->remaining_balance > 0 ? 'text-danger' : 'text-success' }} fs-6">
            PKR {{ number_format($agreement->remaining_balance, 2) }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- 10 Canonical Document Cards Grid -->
  <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-ruled me-2 text-primary"></i>Standard Legal Documentation Suite (10 Documents)</h5>

  <div class="row g-3 mb-4">
    <!-- 1. Customer Application Form -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-secondary-subtle text-dark border">Document 01</span>
              <i class="bi bi-file-earmark-person fs-3 text-secondary"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Customer Application Dossier</h5>
            <p class="text-muted small mb-3">Comprehensive biographical, residential tenure, employer details, and applicant declaration with thumb impression block.</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'application-form', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-secondary w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Application Form (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 2. Customer Verification Report -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-info-subtle text-dark border">Document 02</span>
              <i class="bi bi-check2-circle fs-3 text-info"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Field Verification Report</h5>
            <p class="text-muted small mb-3">Physical residence inspection notes, neighbor checks, utility bill verification status, and field officer certification.</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'verification-report', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-info text-dark w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Verification Report (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 3. Credit Assessment & Approval Sheet -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-primary-subtle text-primary border">Document 03</span>
              <i class="bi bi-award fs-3 text-primary"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Credit Assessment & Approval Sheet</h5>
            <p class="text-muted small mb-3">Debt-to-income (DTI) calculations, disposable income, risk tier score, and Credit Underwriting Committee approval signatures.</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'credit-assessment', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-primary w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Credit Sheet (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 4. Legal Installment Contract -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-warning text-dark">Document 04 (Core Contract)</span>
              <i class="bi bi-file-earmark-ruled fs-3 text-warning"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Legal Installment Financing Contract</h5>
            <p class="text-muted small mb-3">Legally enforceable terms, complete installment schedule, repossession covenants, and stamp paper clearance margin options.</p>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('documents.print', ['type' => 'installment-contract', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-warning text-dark btn-sm flex-fill">
              <i class="bi bi-printer me-1"></i>Standard A4
            </a>
            <a href="{{ route('documents.print', ['type' => 'installment-contract', 'agreement' => $agreement, 'stamp_paper_margin' => 1]) }}" target="_blank" class="btn btn-outline-dark btn-sm flex-fill" title="Adds 85mm top margin on Page 1 for physical e-stamp paper">
              <i class="bi bi-file-text me-1"></i>Stamp Paper
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- 5. Guarantor Undertaking & Promissory Affidavit -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-secondary-subtle text-dark border">Document 05</span>
              <i class="bi bi-pen fs-3 text-secondary"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Guarantor Promissory Affidavit</h5>
            <p class="text-muted small mb-3">Binding joint & several guarantee, unconditional promissory undertaking to pay on demand, and notary sign-off box.</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'guarantor-affidavit', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-secondary w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Guarantor Affidavit (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 6. Delivery & Handover Note -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-success-subtle text-success border">Document 06</span>
              <i class="bi bi-box-seam fs-3 text-success"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Merchandise Delivery & Handover Note</h5>
            <p class="text-muted small mb-3">Showroom gate pass, customer physical receipt acknowledgment, and serialized hardware verification (IMEI / Serial Number).</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'delivery-note', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-success w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Delivery Note (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 7. Installment Payment Receipts -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-warning-subtle text-dark border">Document 07</span>
              <i class="bi bi-receipt fs-3 text-warning text-dark"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Installment Payment Receipts</h5>
            <p class="text-muted small mb-3">3-in-1 print engine supporting 80mm POS thermal roll, 58mm mini thermal roll, or A4 office voucher format.</p>
          </div>
          @php $latestPayment = $agreement->payments()->first(); @endphp
          @if($latestPayment)
            <div class="dropdown w-100">
              <button class="btn btn-outline-warning text-dark btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-printer me-1"></i>Print Receipt #{{ $latestPayment->payment_number }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="{{ route('documents.receipt', ['payment' => $latestPayment, 'format' => '80mm']) }}" target="_blank"><i class="bi bi-receipt me-2"></i>80mm Standard POS Thermal</a></li>
                <li><a class="dropdown-item" href="{{ route('documents.receipt', ['payment' => $latestPayment, 'format' => '58mm']) }}" target="_blank"><i class="bi bi-receipt-cutoff me-2"></i>58mm Mini POS Thermal</a></li>
                <li><a class="dropdown-item" href="{{ route('documents.receipt', ['payment' => $latestPayment, 'format' => 'a4']) }}" target="_blank"><i class="bi bi-file-earmark me-2"></i>Full Page A4 Voucher</a></li>
              </ul>
            </div>
          @else
            <button class="btn btn-outline-secondary btn-sm w-100" disabled>No payments recorded yet</button>
          @endif
        </div>
      </div>
    </div>

    <!-- 8. Customer Statement of Account -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-info-subtle text-info border">Document 08</span>
              <i class="bi bi-journal-text fs-3 text-info"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Customer Statement of Account</h5>
            <p class="text-muted small mb-3">Full historical customer ledger showing total billed installments, payment dates, receipts, late fee charges, and waivers.</p>
          </div>
          <a href="{{ route('documents.print', ['type' => 'account-statement', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-outline-info text-dark w-100 btn-sm">
            <i class="bi bi-printer me-1"></i>Print Account Statement (A4)
          </a>
        </div>
      </div>
    </div>

    <!-- 9. Early Settlement Letter -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-danger-subtle text-danger border">Document 09</span>
              <i class="bi bi-calculator fs-3 text-danger"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Early Settlement Payoff Offer</h5>
            <p class="text-muted small mb-3">Calculates rebate discount on unearned markup for premature payoff, stipulating net payoff sum valid for 7 days.</p>
          </div>
          <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#settlementModal">
            <i class="bi bi-calculator me-1"></i>Calculate & Print Settlement
          </button>
        </div>
      </div>
    </div>

    <!-- 10. Clearance Certificate & NOC -->
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-success transition-hover">
        <div class="card-body p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="badge bg-success text-white">Document 10 (Completion)</span>
              <i class="bi bi-patch-check-fill fs-3 text-success"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Clearance Certificate & NOC</h5>
            <p class="text-muted small mb-3">Official gold-seal No Objection Certificate releasing all claims on the debtor, guarantors, and serialized merchandise.</p>
          </div>
          @if($isCleared)
            <a href="{{ route('documents.print', ['type' => 'clearance-noc', 'agreement' => $agreement]) }}" target="_blank" class="btn btn-success w-100 btn-sm">
              <i class="bi bi-patch-check me-1"></i>Print Official NOC Certificate
            </a>
          @else
            <form action="{{ route('documents.issue-noc', $agreement) }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-outline-secondary w-100 btn-sm" title="Requires zero outstanding balance">
                <i class="bi bi-lock me-1"></i>NOC Locked (Balance: PKR {{ number_format($agreement->remaining_balance) }})
              </button>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Early Settlement Calculator -->
  <div class="modal fade" id="settlementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('documents.print', ['type' => 'settlement-letter', 'agreement' => $agreement]) }}" method="GET" target="_blank">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-calculator me-2"></i>Early Contract Settlement Calculator</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="p-3 bg-light rounded border small mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span>Remaining Total Payable:</span>
                <strong>PKR {{ number_format($settlementPreview['gross_remaining_balance'], 2) }}</strong>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span>Unearned Financing Markup:</span>
                <span class="text-primary fw-bold">PKR {{ number_format($settlementPreview['unearned_markup_total'], 2) }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Accrued Late Penalties:</span>
                <span class="text-danger fw-bold">PKR {{ number_format($settlementPreview['accrued_late_fees'], 2) }}</span>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Unearned Markup Rebate Discount (%)</label>
              <select name="rebate_pct" class="form-select" required>
                <option value="0">0% (No Markup Rebate)</option>
                <option value="25">25% Early Settlement Discount</option>
                <option value="50" selected>50% Standard Commercial Concession</option>
                <option value="75">75% Generous Payoff Concession</option>
                <option value="100">100% Full Unearned Markup Waiver</option>
              </select>
              <small class="text-muted">The rebate discount is deducted from the unearned markup to incentivize prompt lump-sum liquidation.</small>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">
              <i class="bi bi-file-earmark-pdf me-1"></i>Generate Formal Settlement Letter
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
