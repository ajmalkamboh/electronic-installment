<x-app-layout title="Late Fee Ledger & Supervisory Waivers">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Late Fee Ledger & Supervisory Waivers</h1>
      <p class="text-muted mb-0">Inspect overdue installment schedules, accrued penalties, and execute authorized late fee waivers with immutable audit logs.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('recovery.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Recovery Dashboard
      </a>
      <a href="{{ route('recovery.cases.index') }}" class="btn btn-primary">
        <i class="bi bi-folder2-open me-1"></i>Recovery Cases
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

  <!-- Filter & Search Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('recovery.late-fees') }}" class="row g-3 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Search Customer / Contract</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Name, CNIC, Phone, Account #" value="{{ $search }}">
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-5 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">
            <i class="bi bi-filter me-1"></i>Apply Filter
          </button>
          @if($search || $branchId)
            <a href="{{ route('recovery.late-fees') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Overdue Installments & Penalty Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0">Delinquent Installment Schedules</h5>
          <small class="text-muted">Unpaid installments past maturity with active penalty accruals</small>
        </div>
        <span class="badge bg-danger-subtle text-danger p-2">
          {{ $schedules->total() }} Schedules Overdue
        </span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Customer & Contact</th>
            <th>Contract & Inst #</th>
            <th>Due Date</th>
            <th>Days Overdue</th>
            <th class="text-end">Balance Due</th>
            <th class="text-end">Accrued Late Fee</th>
            <th class="text-center">Waiver Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($schedules as $sch)
            <tr>
              <td>
                <div class="fw-bold">{{ $sch->agreement?->customer?->full_name }}</div>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $sch->agreement?->customer?->mobile_primary }}</small>
                <span class="d-block small text-muted font-monospace">{{ $sch->agreement?->customer?->cnic }}</span>
              </td>
              <td>
                <a href="{{ route('agreements.show', $sch->agreement_id ?? $sch->installment_agreement_id) }}" class="fw-semibold text-primary">
                  {{ $sch->agreement?->account_number }}
                </a>
                <div class="small text-muted">Installment #{{ $sch->installment_number }} of {{ $sch->agreement?->total_installments }}</div>
                <span class="badge bg-light text-dark border small">{{ $sch->branch?->name }}</span>
              </td>
              <td>
                <div class="fw-semibold">{{ $sch->due_date->format('d M, Y') }}</div>
                <small class="text-muted">{{ $sch->due_date->diffForHumans() }}</small>
              </td>
              <td>
                <span class="badge bg-danger">
                  <i class="bi bi-clock-history me-1"></i>{{ $sch->daysOverdue() }} Days
                </span>
              </td>
              <td class="text-end fw-bold text-dark">
                PKR {{ number_format($sch->remaining_balance, 2) }}
              </td>
              <td class="text-end fw-bold text-warning text-dark">
                PKR {{ number_format($sch->late_fee_amount, 2) }}
              </td>
              <td class="text-center">
                @if((float) $sch->late_fee_amount > 0)
                  @if($canWaive)
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#waiverModal{{ $sch->id }}">
                      <i class="bi bi-percent me-1"></i>Waive Fee
                    </button>

                    <!-- Modal for Late Fee Waiver -->
                    <div class="modal fade" id="waiverModal{{ $sch->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content text-start">
                          <form action="{{ route('recovery.late-fees.waive', $sch) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-danger text-white">
                              <h5 class="modal-title fs-6 fw-bold">
                                <i class="bi bi-shield-exclamation me-1"></i>Authorize Penalty Waiver
                              </h5>
                              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                              <div class="alert alert-light border small mb-3">
                                <div class="row g-2">
                                  <div class="col-6"><strong>Customer:</strong> {{ $sch->agreement?->customer?->full_name }}</div>
                                  <div class="col-6"><strong>Account:</strong> {{ $sch->agreement?->account_number }}</div>
                                  <div class="col-6"><strong>Installment:</strong> #{{ $sch->installment_number }}</div>
                                  <div class="col-6 text-danger"><strong>Accrued Fee:</strong> PKR {{ number_format($sch->late_fee_amount, 2) }}</div>
                                </div>
                              </div>

                              <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                  <label class="form-label small fw-bold mb-0">Waiver Amount (PKR) <span class="text-danger">*</span></label>
                                  <button type="button" class="btn btn-link btn-sm p-0 text-primary small text-decoration-none" onclick="document.getElementById('waiverAmountInput{{ $sch->id }}').value='{{ $sch->late_fee_amount }}';">
                                    Waive Full (100%)
                                  </button>
                                </div>
                                <input type="number" step="0.01" max="{{ $sch->late_fee_amount }}" min="0.01" name="waived_amount" id="waiverAmountInput{{ $sch->id }}" class="form-control" value="{{ $sch->late_fee_amount }}" required>
                                <small class="text-muted">Enter partial or full waiver amount.</small>
                              </div>

                              <div class="mb-3">
                                <label class="form-label small fw-bold">Mandatory Justification Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Verified customer medical emergency document; approved as good-will gesture." required minlength="5"></textarea>
                                <small class="text-muted">This justification is permanently preserved in the supervisory audit trail.</small>
                              </div>

                              <div class="p-2 bg-light rounded small text-muted">
                                <i class="bi bi-person-badge me-1"></i>Approving Supervisor: <strong>{{ Auth::user()->name }}</strong> ({{ ucfirst(Auth::user()->role) }})
                              </div>
                            </div>
                            <div class="modal-footer bg-light">
                              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-check-circle me-1"></i>Approve & Audit Waiver
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  @else
                    <span class="badge bg-light text-muted border" title="Requires Branch Manager or Company Admin role">
                      <i class="bi bi-lock me-1"></i>Approval Required
                    </span>
                  @endif
                @else
                  <span class="badge bg-success-subtle text-success">Zero Fee</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                No overdue installment schedules with active penalties found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($schedules->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $schedules->links() }}
      </div>
    @endif
  </div>

  <!-- Recent Waivers Audit Trail -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h5 class="fw-bold mb-0">Supervisory Penalty Waiver Audit Log</h5>
      <small class="text-muted">Immutable transaction history of all late fee waivers approved by management</small>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date & Time</th>
            <th>Customer & Contract</th>
            <th>Inst #</th>
            <th class="text-end">Original Fee</th>
            <th class="text-end">Waived Amount</th>
            <th class="text-end">Remaining Fee</th>
            <th>Approved By</th>
            <th>Justification</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentWaivers as $w)
            <tr>
              <td>
                <div class="fw-semibold">{{ $w->created_at->format('d M, Y') }}</div>
                <small class="text-muted">{{ $w->created_at->format('h:i A') }}</small>
              </td>
              <td>
                <div class="fw-bold">{{ $w->agreement?->customer?->full_name }}</div>
                <small class="text-muted font-monospace">{{ $w->agreement?->account_number }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">#{{ $w->schedule?->installment_number }}</span>
              </td>
              <td class="text-end text-muted">
                PKR {{ number_format($w->original_late_fee, 2) }}
              </td>
              <td class="text-end fw-bold text-success">
                - PKR {{ number_format($w->waived_amount, 2) }}
              </td>
              <td class="text-end fw-bold text-dark">
                PKR {{ number_format($w->remaining_late_fee, 2) }}
              </td>
              <td>
                <span class="badge bg-primary-subtle text-primary">
                  <i class="bi bi-person me-1"></i>{{ $w->waivedBy?->name }}
                </span>
              </td>
              <td class="small text-muted" style="max-width: 250px;">
                {{ $w->reason }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                No penalty waivers recorded yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</x-app-layout>
