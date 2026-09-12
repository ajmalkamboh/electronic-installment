<x-app-layout title="Late Fees & Recovery Command Center">
  <!-- Header with Actions & Sub-navigation -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Late Fees & Recovery Command Center</h1>
      <p class="text-muted mb-0">Manage penalty calculation rules, supervisory waivers, delinquency aging (DPD), legal notices, and repossession workflows.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <form action="{{ route('recovery.run-assessment') }}" method="POST" class="d-inline">
        @csrf
        @if($branchId)
          <input type="hidden" name="branch_id" value="{{ $branchId }}">
        @endif
        <button type="submit" class="btn btn-outline-primary" onclick="this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span>Evaluating...';">
          <i class="bi bi-arrow-repeat me-1"></i>Run Daily Assessment
        </button>
      </form>
      <a href="{{ route('recovery.late-fees') }}" class="btn btn-outline-warning text-dark">
        <i class="bi bi-clock-history me-1"></i>Late Fee Ledger
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

  <!-- Branch Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('recovery.dashboard') }}" class="row g-3 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Showroom Branch Filter</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches & Showrooms</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                {{ $b->name }} ({{ $b->city }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-8 text-md-end pt-md-3">
          <span class="badge bg-light text-dark border p-2">
            <i class="bi bi-info-circle me-1 text-primary"></i>Late fee grace period and daily accrual apply automatically past due dates.
          </span>
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-danger-subtle text-danger p-3 fs-3">
            <i class="bi bi-cash-coin"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Overdue Principal + Markup</span>
            <h3 class="fw-bold mb-0 text-danger">PKR {{ number_format($totalOverdue, 0) }}</h3>
            <small class="text-muted">Total delinquent balance</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-warning-subtle text-warning p-3 fs-3">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Accrued Late Penalties</span>
            <h3 class="fw-bold mb-0 text-warning text-dark">PKR {{ number_format($totalLateFees, 0) }}</h3>
            <small class="text-muted">Active penalty pool</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-success-subtle text-success p-3 fs-3">
            <i class="bi bi-check2-circle"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Supervisory Waived Fees</span>
            <h3 class="fw-bold mb-0 text-success">PKR {{ number_format($totalWaived, 0) }}</h3>
            <small class="text-muted">Approved penalty relief</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-3">
            <i class="bi bi-truck"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Cases & Repossessions</span>
            <h3 class="fw-bold mb-0 text-dark">{{ number_format($activeCasesCount) }} <span class="fs-6 text-muted">/ {{ number_format($repossessedCount) }} Repossessed</span></h3>
            <small class="text-primary">Pipeline & recovered stock</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Delinquency Aging Stage Distribution Pipeline -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0">Delinquency Aging & Escalation Funnel (DPD)</h5>
          <small class="text-muted">Distribution of delinquent installment agreements across staged escalation buckets</small>
        </div>
        <a href="{{ route('recovery.cases.index') }}" class="btn btn-sm btn-outline-secondary">
          View All Cases <i class="bi bi-chevron-right ms-1"></i>
        </a>
      </div>
    </div>
    <div class="card-body p-4">
      <div class="row g-3 text-center">
        <!-- Stage 1 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'grace_period', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-light border h-100 transition-hover">
              <div class="badge bg-secondary mb-2">1 - 5 Days</div>
              <h4 class="fw-bold text-dark mb-1">{{ $aging['grace_period'] }}</h4>
              <small class="text-muted d-block fw-semibold">Grace Period</small>
              <span class="badge bg-light text-secondary border mt-2">Zero Late Fee</span>
            </div>
          </a>
        </div>

        <!-- Stage 2 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'overdue_reminder', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-warning-subtle border border-warning-subtle h-100 transition-hover">
              <div class="badge bg-warning text-dark mb-2">6 - 14 Days</div>
              <h4 class="fw-bold text-dark mb-1">{{ $aging['overdue_reminder'] }}</h4>
              <small class="text-dark d-block fw-semibold">Overdue Reminder</small>
              <span class="badge bg-warning text-dark mt-2">Penalty Active</span>
            </div>
          </a>
        </div>

        <!-- Stage 3 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'tele_collection', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-info-subtle border border-info-subtle h-100 transition-hover">
              <div class="badge bg-info text-dark mb-2">15 - 29 Days</div>
              <h4 class="fw-bold text-dark mb-1">{{ $aging['tele_collection'] }}</h4>
              <small class="text-dark d-block fw-semibold">Tele-Collection</small>
              <span class="badge bg-info text-dark mt-2">Formal Notice</span>
            </div>
          </a>
        </div>

        <!-- Stage 4 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'field_recovery', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-primary-subtle border border-primary-subtle h-100 transition-hover">
              <div class="badge bg-primary mb-2">30 - 59 Days</div>
              <h4 class="fw-bold text-dark mb-1">{{ $aging['field_recovery'] }}</h4>
              <small class="text-primary d-block fw-semibold">Field Recovery</small>
              <span class="badge bg-primary mt-2">Officer Visit</span>
            </div>
          </a>
        </div>

        <!-- Stage 5 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'legal_notice', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-danger-subtle border border-danger-subtle h-100 transition-hover">
              <div class="badge bg-danger mb-2">60 - 89 Days</div>
              <h4 class="fw-bold text-danger mb-1">{{ $aging['legal_notice'] }}</h4>
              <small class="text-danger d-block fw-semibold">Legal Notice</small>
              <span class="badge bg-danger mt-2">Guarantor Demand</span>
            </div>
          </a>
        </div>

        <!-- Stage 6 -->
        <div class="col-md-4 col-lg-2">
          <a href="{{ route('recovery.cases.index', ['stage' => 'repossession_pending', 'branch_id' => $branchId]) }}" class="text-decoration-none">
            <div class="p-3 rounded bg-dark text-white border h-100 transition-hover">
              <div class="badge bg-danger mb-2">90+ Days</div>
              <h4 class="fw-bold text-warning mb-1">{{ $aging['repossession_pending'] }}</h4>
              <small class="text-white-50 d-block fw-semibold">Repossession</small>
              <span class="badge bg-warning text-dark mt-2">Warrant Pending</span>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Priority Delinquent Accounts Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0">High-Priority Escalation Docket</h5>
          <small class="text-muted">Accounts with highest Days Past Due (DPD) requiring immediate supervisory or recovery action</small>
        </div>
        <a href="{{ route('recovery.cases.index') }}" class="btn btn-sm btn-outline-primary">
          All Recovery Cases ({{ $activeCasesCount }})
        </a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Case & Contract</th>
            <th>Customer</th>
            <th>Showroom</th>
            <th>DPD / Stage</th>
            <th class="text-end">Overdue Principal</th>
            <th class="text-end">Late Penalty</th>
            <th>Assigned Squad</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($priorityCases as $case)
            <tr>
              <td>
                <div class="fw-bold text-primary">{{ $case->case_number }}</div>
                <small class="text-muted font-monospace">{{ $case->agreement?->account_number }}</small>
              </td>
              <td>
                <div class="fw-semibold">{{ $case->customer?->full_name }}</div>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $case->customer?->mobile_primary }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $case->branch?->name }}</span>
              </td>
              <td>
                <div class="mb-1">{!! $case->stage_badge !!}</div>
                <small class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i>{{ $case->days_past_due }} DPD</small>
              </td>
              <td class="text-end fw-bold text-dark">
                PKR {{ number_format($case->total_overdue_amount, 2) }}
              </td>
              <td class="text-end fw-bold text-warning text-dark">
                PKR {{ number_format($case->total_late_fees, 2) }}
              </td>
              <td>
                @if($case->assignedOfficer)
                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-person me-1"></i>{{ $case->assignedOfficer->name }}
                  </span>
                @else
                  <span class="badge bg-secondary-subtle text-muted">Unassigned</span>
                @endif
              </td>
              <td class="text-center">
                <a href="{{ route('recovery.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-folder2-open me-1"></i>Docket
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                No high-priority delinquent recovery cases currently found.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</x-app-layout>
