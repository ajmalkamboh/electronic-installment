<x-app-layout title="Recovery Case Docket - {{ $case->case_number }}">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0 font-monospace">{{ $case->case_number }}</h1>
        {!! $case->stage_badge !!}
        {!! $case->status_badge !!}
      </div>
      <p class="text-muted mb-0">Delinquency recovery case file for Contract <strong>#{{ $case->agreement?->account_number }}</strong></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('recovery.cases.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>All Cases
      </a>

      <!-- Action: Issue Notice -->
      <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-target="#issueNoticeModal">
        <i class="bi bi-file-earmark-text me-1"></i>Issue Legal Notice
      </button>

      <!-- Action: Authorize Repossession -->
      @if(in_array($case->stage, ['legal_notice', 'repossession_pending']) && ! $case->repossession_authorized_at)
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#authRepossessionModal">
          <i class="bi bi-shield-lock me-1"></i>Authorize Repossession
        </button>
      @endif

      <!-- Action: Execute Repossession -->
      @if($case->repossession_authorized_at && $case->stage !== 'repossessed')
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#execRepossessionModal">
          <i class="bi bi-truck me-1"></i>Execute Repossession
        </button>
      @endif

      <!-- Action: Bad-Debt Write-Off -->
      @if(! in_array($case->stage, ['repossessed', 'written_off', 'resolved']))
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#writeOffModal">
          <i class="bi bi-file-earmark-x me-1"></i>Write-Off
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

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">
    <!-- Left Column: Customer & Contract Profile -->
    <div class="col-lg-4">
      <!-- Financial Delinquency Snapshot -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white py-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-shield-exclamation me-1"></i>Delinquency Snapshot</h6>
        </div>
        <div class="card-body p-4">
          <div class="text-center mb-3">
            <span class="text-muted small">Days Past Due (DPD)</span>
            <h2 class="fw-bold text-danger mb-0">{{ $case->days_past_due }} Days</h2>
            <small class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1">
              Active Delinquency Stage
            </small>
          </div>
          <hr>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Overdue Installments:</span>
            <span class="fw-bold text-dark">PKR {{ number_format($case->total_overdue_amount, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Accrued Late Fees:</span>
            <span class="fw-bold text-warning text-dark">PKR {{ number_format($case->total_late_fees, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between pt-2 border-top">
            <span class="fw-bold text-dark">Total Immediate Demand:</span>
            <span class="fw-bold text-danger fs-5">
              PKR {{ number_format($case->total_overdue_amount + $case->total_late_fees, 2) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Customer Dossier Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-1"></i>Customer Dossier</h6>
        </div>
        <div class="card-body p-4">
          <h5 class="fw-bold mb-1">{{ $case->customer?->full_name }}</h5>
          <p class="text-muted small mb-3">Customer ID: CUST-{{ str_pad($case->customer_id, 4, '0', STR_PAD_LEFT) }}</p>

          <div class="mb-2">
            <small class="text-muted d-block">CNIC Number</small>
            <span class="fw-semibold font-monospace">{{ $case->customer?->cnic }}</span>
          </div>

          <div class="mb-2">
            <small class="text-muted d-block">Primary Contact</small>
            <a href="tel:{{ $case->customer?->mobile_primary }}" class="text-decoration-none fw-semibold">
              <i class="bi bi-telephone me-1"></i>{{ $case->customer?->mobile_primary }}
            </a>
            @if($case->customer?->mobile_secondary)
              <span class="text-muted small"> / {{ $case->customer?->mobile_secondary }}</span>
            @endif
          </div>

          <div class="mb-2">
            <small class="text-muted d-block">Residential Address</small>
            <span class="small text-dark">{{ $case->customer?->present_address ?? 'Not specified' }}</span>
          </div>

          <div class="mb-2">
            <small class="text-muted d-block">Workplace / Occupation</small>
            <span class="small text-dark">{{ $case->customer?->residence_type ?? 'N/A' }}</span>
          </div>

          @if($case->customer?->creditProfile)
            <div class="mt-3 p-2 rounded bg-light border small">
              <div class="d-flex justify-content-between">
                <span>Credit Score:</span>
                <strong>{{ $case->customer->creditProfile->credit_score }} / 100</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span>Risk Tier:</span>
                <span class="badge bg-secondary">{{ strtoupper($case->customer->creditProfile->risk_tier) }}</span>
              </div>
            </div>
          @endif
        </div>
      </div>

      <!-- Guarantors Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-people me-1"></i>Guarantors & Legal Underwriters</h6>
        </div>
        <div class="card-body p-4">
          @forelse($case->agreement?->guarantors ?? [] as $guarantor)
            <div class="p-3 mb-2 rounded bg-light border">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="text-dark">{{ $guarantor->full_name }}</strong>
                @if($guarantor->pivot->is_primary)
                  <span class="badge bg-primary small">Primary Guarantor</span>
                @else
                  <span class="badge bg-secondary small">Secondary</span>
                @endif
              </div>
              <small class="text-muted d-block font-monospace"><i class="bi bi-card-heading me-1"></i>{{ $guarantor->cnic }}</small>
              <small class="text-muted d-block"><i class="bi bi-telephone me-1"></i><a href="tel:{{ $guarantor->mobile }}">{{ $guarantor->mobile }}</a></small>
              <small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i>{{ $guarantor->address }}</small>
            </div>
          @empty
            <p class="text-muted small mb-0">No active guarantors attached to contract.</p>
          @endforelse
        </div>
      </div>

      <!-- Serialized Asset Details -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-1"></i>Financed Merchandise</h6>
        </div>
        <div class="card-body p-4">
          <h6 class="fw-bold mb-1">{{ $case->agreement?->product?->name }}</h6>
          <p class="text-muted small mb-3">Model: {{ $case->agreement?->product?->model_number ?? 'N/A' }}</p>

          <div class="p-2 rounded bg-light border mb-3">
            <small class="text-muted d-block">IMEI / Serial Number</small>
            <span class="fw-bold font-monospace text-primary">
              {{ $case->agreement?->serializedItem?->serial_number ?? 'NON-SERIALIZED' }}
            </span>
          </div>

          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Cash Price:</span>
            <span>PKR {{ number_format($case->agreement?->cash_price, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between small mb-1">
            <span class="text-muted">Total Financed Payable:</span>
            <span class="fw-bold">PKR {{ number_format($case->agreement?->total_payable, 2) }}</span>
          </div>
          <div class="d-flex justify-content-between small">
            <span class="text-muted">Remaining Balance:</span>
            <span class="fw-bold text-danger">PKR {{ number_format($case->agreement?->remaining_balance, 2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Schedules, Legal Notices, and Escalations -->
    <div class="col-lg-8">
      <!-- Delinquent Schedules Ledger -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0">Payment Schedule & Penalties</h5>
            <small class="text-muted">Audit breakdown of all installment dues, late penalties, and payment status</small>
          </div>
          <a href="{{ route('agreements.show', $case->installment_agreement_id) }}" class="btn btn-sm btn-outline-secondary">
            View Agreement <i class="bi bi-arrow-up-right ms-1"></i>
          </a>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Due Date</th>
                <th class="text-end">Principal + Markup</th>
                <th class="text-end">Paid Amount</th>
                <th class="text-end">Remaining</th>
                <th class="text-end">Late Penalty</th>
                <th>Status</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($case->agreement?->schedules ?? [] as $sch)
                <tr class="{{ $sch->daysOverdue() > 0 ? 'table-danger-subtle' : '' }}">
                  <td class="fw-bold">#{{ $sch->installment_number }}</td>
                  <td>
                    <div>{{ $sch->due_date->format('d M, Y') }}</div>
                    @if($sch->daysOverdue() > 0)
                      <small class="text-danger fw-semibold">{{ $sch->daysOverdue() }} DPD</small>
                    @endif
                  </td>
                  <td class="text-end">PKR {{ number_format($sch->total_amount, 2) }}</td>
                  <td class="text-end text-success">PKR {{ number_format($sch->paid_amount, 2) }}</td>
                  <td class="text-end fw-bold text-dark">PKR {{ number_format($sch->remaining_balance, 2) }}</td>
                  <td class="text-end fw-bold text-warning text-dark">
                    PKR {{ number_format($sch->late_fee_amount, 2) }}
                  </td>
                  <td>{!! $sch->status_badge !!}</td>
                  <td class="text-center">
                    @if((float) $sch->late_fee_amount > 0 && $canWaive)
                      <a href="{{ route('recovery.late-fees', ['search' => $case->agreement?->account_number]) }}" class="btn btn-xs btn-outline-danger">
                        Waive
                      </a>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <!-- Legal Notices & Repossession Warrants Log -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0">Legal Notices & Repossession Warrants</h5>
            <small class="text-muted">Issued formal communications under Pakistani Contract & Installment Law</small>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#issueNoticeModal">
            <i class="bi bi-plus-circle me-1"></i>New Notice
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Notice Number</th>
                <th>Type</th>
                <th>Recipient</th>
                <th>Issued At</th>
                <th>Demand Deadline</th>
                <th class="text-end">Demand Amount</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($case->notices as $notice)
                <tr>
                  <td class="fw-bold font-monospace text-primary">{{ $notice->notice_number }}</td>
                  <td>{!! $notice->type_badge !!}</td>
                  <td>
                    <div class="fw-semibold">{{ $notice->recipient_name }}</div>
                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $notice->recipient_type)) }}</small>
                  </td>
                  <td>
                    <div>{{ $notice->issued_at->format('d M, Y') }}</div>
                    <small class="text-muted">{{ $notice->issuedBy?->name }}</small>
                  </td>
                  <td>
                    <span class="badge bg-danger">{{ $notice->demand_deadline->format('d M, Y') }}</span>
                  </td>
                  <td class="text-end fw-bold text-dark">
                    PKR {{ number_format($notice->total_demand_amount, 2) }}
                  </td>
                  <td class="text-center">
                    <a href="{{ route('recovery.notices.print', $notice) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-printer me-1"></i>Print A4
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    No legal notices or repossession warrants issued yet.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Repossession & Resolution Audit Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="fw-bold mb-0">Repossession & Case Resolution Trail</h5>
          <small class="text-muted">Milestones and supervisory authorizations for asset recovery and bad debt write-offs</small>
        </div>
        <div class="card-body p-4">
          <div class="timeline">
            @if($case->repossession_authorized_at)
              <div class="p-3 mb-3 rounded bg-warning-subtle border border-warning-subtle">
                <div class="d-flex justify-content-between mb-1">
                  <strong class="text-dark"><i class="bi bi-shield-check me-1"></i>Repossession Authorized</strong>
                  <span class="text-muted small">{{ $case->repossession_authorized_at->format('d M, Y h:i A') }}</span>
                </div>
                <small class="text-muted d-block">Authorized By: <strong>{{ $case->repossessionAuthorizedBy?->name }}</strong></small>
                <p class="small mb-0 mt-2 text-dark">{{ $case->repossession_notes }}</p>
              </div>
            @endif

            @if($case->repossessed_at)
              <div class="p-3 mb-3 rounded bg-success-subtle border border-success-subtle">
                <div class="d-flex justify-content-between mb-1">
                  <strong class="text-success"><i class="bi bi-truck me-1"></i>Asset Repossession Executed</strong>
                  <span class="text-muted small">{{ $case->repossessed_at->format('d M, Y h:i A') }}</span>
                </div>
                <small class="text-muted d-block">Executed By: <strong>{{ $case->repossessedBy?->name }}</strong></small>
                <small class="text-muted d-block">Recovered Condition: <span class="badge bg-success">{{ strtoupper($case->repossessed_condition) }}</span></small>
                <p class="small mb-0 mt-2 text-dark">{{ $case->repossession_notes }}</p>
              </div>
            @endif

            @if($case->written_off_at)
              <div class="p-3 mb-3 rounded bg-dark text-white">
                <div class="d-flex justify-content-between mb-1">
                  <strong class="text-danger"><i class="bi bi-file-earmark-x me-1"></i>Bad Debt Written Off</strong>
                  <span class="text-white-50 small">{{ $case->written_off_at->format('d M, Y h:i A') }}</span>
                </div>
                <small class="text-white-50 d-block">Authorized By: <strong>{{ $case->writtenOffBy?->name }}</strong></small>
                <small class="text-white-50 d-block">Loss Written Off: <strong class="text-warning">PKR {{ number_format($case->written_off_amount, 2) }}</strong></small>
                <p class="small mb-0 mt-2 text-white-50">{{ $case->written_off_reason }}</p>
              </div>
            @endif

            @if(! $case->repossession_authorized_at && ! $case->repossessed_at && ! $case->written_off_at)
              <div class="text-center py-4 text-muted">
                <i class="bi bi-clock-history fs-3 d-block mb-1"></i>
                No terminal repossession or write-off actions taken on this case.
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Issue Notice -->
  <div class="modal fade" id="issueNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('recovery.cases.issue-notice', $case) }}" method="POST">
          @csrf
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title fs-6 fw-bold">
              <i class="bi bi-file-earmark-text me-1"></i>Issue Formal Demand / Legal Notice
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label small fw-bold">Notice Type <span class="text-danger">*</span></label>
              <select name="notice_type" class="form-select" required>
                <option value="reminder_notice">1st Friendly Overdue Reminder (1-14 Days)</option>
                <option value="formal_overdue_notice">Formal Overdue Demand Notice (15-29 Days)</option>
                <option value="guarantor_notice">Guarantor Recovery Notice (30-59 Days)</option>
                <option value="final_demand_notice">Final Pre-Legal Demand Notice (60-89 Days)</option>
                <option value="legal_notice">Formal Legal Advocate Notice (60-89 Days)</option>
                <option value="repossession_warrant">Asset Repossession Warrant (90+ Days)</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Recipient <span class="text-danger">*</span></label>
              <select name="recipient_type" class="form-select" required>
                <option value="customer">Customer ({{ $case->customer?->full_name }})</option>
                <option value="primary_guarantor">Primary Guarantor</option>
                <option value="all">Customer & All Guarantors</option>
              </select>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small fw-bold">Payment Deadline (Days)</label>
                <input type="number" name="demand_days" class="form-control" value="7" min="1" max="30" required>
              </div>
              <div class="col-6">
                <label class="form-label small fw-bold">Delivery Channel</label>
                <select name="delivery_channel" class="form-select" required>
                  <option value="hand_delivery">Hand Delivery (Field Officer)</option>
                  <option value="registered_post">Registered Pakistan Post / Courier</option>
                  <option value="whatsapp">WhatsApp / SMS</option>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Special Instructions / Remarks</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Sent via courier tracking #TCS-9843924"></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Issue & Generate Printable Notice</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Authorize Repossession -->
  <div class="modal fade" id="authRepossessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('recovery.cases.authorize-repossession', $case) }}" method="POST">
          @csrf
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fs-6 fw-bold">
              <i class="bi bi-shield-lock me-1"></i>Authorize Asset Repossession
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p class="small text-muted mb-3">
              Authorizing repossession flags this agreement as <strong>Defaulted</strong> and empowers the recovery team to seize the financed merchandise (<strong>{{ $case->agreement?->product?->name }} - {{ $case->agreement?->serializedItem?->serial_number }}</strong>).
            </p>
            <div class="mb-3">
              <label class="form-label small fw-bold">Supervisory Authorization Notes <span class="text-danger">*</span></label>
              <textarea name="notes" class="form-control" rows="3" placeholder="State reason for authorizing physical repossession (e.g. Unresponsive to multiple legal notices and broken PTPs)..." required minlength="5"></textarea>
            </div>
            <div class="p-2 rounded bg-light small text-muted">
              Supervisor: <strong>{{ Auth::user()->name }}</strong> ({{ ucfirst(Auth::user()->role) }})
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Authorize Repossession</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Execute Repossession -->
  <div class="modal fade" id="execRepossessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('recovery.cases.execute-repossession', $case) }}" method="POST">
          @csrf
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title fs-6 fw-bold">
              <i class="bi bi-truck me-1"></i>Record Physical Asset Repossession
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <p class="small text-muted mb-3">
              Submitting this form confirms that the merchandise has been physically retrieved from customer custody and returned to branch showroom inventory.
            </p>
            <div class="mb-3">
              <label class="form-label small fw-bold">Recovered Asset Condition <span class="text-danger">*</span></label>
              <select name="condition" class="form-select" required>
                <option value="like_new">Like New / Pristine Condition</option>
                <option value="good" selected>Good / Minor Cosmetic Wear</option>
                <option value="fair">Fair / Functional with Noticeable Wear</option>
                <option value="damaged">Damaged / Requires Repair or Refurbishment</option>
                <option value="scrapped">Severely Damaged / Total Loss Scrap</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Handover & Inspection Notes</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="Specify physical condition, accessories returned, customer acknowledgment..."></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark btn-sm">Confirm Repossession & Restock</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Write-Off -->
  <div class="modal fade" id="writeOffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('recovery.cases.write-off', $case) }}" method="POST">
          @csrf
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title fs-6 fw-bold">
              <i class="bi bi-file-earmark-x me-1"></i>Authorize Bad-Debt Write-Off
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="alert alert-danger small mb-3">
              <i class="bi bi-exclamation-triangle me-1"></i><strong>Warning:</strong> Writing off unrecoverable debt closes the recovery case and permanently logs the financial loss against showroom profit/loss accounts.
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Write-Off Loss Amount (PKR) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" name="loss_amount" class="form-control" value="{{ $case->total_overdue_amount }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-bold">Mandatory Justification <span class="text-danger">*</span></label>
              <textarea name="reason" class="form-control" rows="3" placeholder="Provide full justification (e.g. Debtor deceased / Untraceable after police FIR)..." required minlength="10"></textarea>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Confirm Debt Write-Off</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
