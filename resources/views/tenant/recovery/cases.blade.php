<x-app-layout title="Delinquency Recovery Cases">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Delinquency Recovery Cases</h1>
      <p class="text-muted mb-0">Track customer default stages, issue formal legal notices, and manage physical product repossession.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('recovery.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Command Center
      </a>
      <a href="{{ route('recovery.late-fees') }}" class="btn btn-outline-warning text-dark">
        <i class="bi bi-clock-history me-1"></i>Late Fee Ledger
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Filters & Search -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('recovery.cases.index') }}" class="row g-3 align-items-center">
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Search Customer / Case</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Case #, Account #, Name, CNIC" value="{{ $search }}">
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Escalation Stage</label>
          <select name="stage" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Escalation Stages</option>
            <option value="grace_period" {{ $stage === 'grace_period' ? 'selected' : '' }}>Grace Period (1-5d)</option>
            <option value="overdue_reminder" {{ $stage === 'overdue_reminder' ? 'selected' : '' }}>Reminder Notice (6-14d)</option>
            <option value="tele_collection" {{ $stage === 'tele_collection' ? 'selected' : '' }}>Tele-Collection (15-29d)</option>
            <option value="field_recovery" {{ $stage === 'field_recovery' ? 'selected' : '' }}>Field Recovery (30-59d)</option>
            <option value="legal_notice" {{ $stage === 'legal_notice' ? 'selected' : '' }}>Legal Demand Notice (60-89d)</option>
            <option value="repossession_pending" {{ $stage === 'repossession_pending' ? 'selected' : '' }}>Repossession Pending (90d+)</option>
            <option value="repossessed" {{ $stage === 'repossessed' ? 'selected' : '' }}>Repossessed</option>
            <option value="written_off" {{ $stage === 'written_off' ? 'selected' : '' }}>Written Off</option>
            <option value="resolved" {{ $stage === 'resolved' ? 'selected' : '' }}>Resolved / Settled</option>
          </select>
        </div>

        <div class="col-md-2">
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

        <div class="col-md-4 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">
            <i class="bi bi-filter me-1"></i>Filter
          </button>
          @if($search || $stage || $status || $branchId)
            <a href="{{ route('recovery.cases.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Cases List Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0">Delinquency Case Docket Registry</h5>
          <small class="text-muted">Total {{ $cases->total() }} accounts in recovery lifecycle</small>
        </div>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Case # & Contract</th>
            <th>Customer</th>
            <th>Showroom</th>
            <th>DPD & Escalation Stage</th>
            <th class="text-end">Overdue Principal</th>
            <th class="text-end">Late Penalties</th>
            <th>Field Officer</th>
            <th>Status</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($cases as $case)
            <tr>
              <td>
                <div class="fw-bold text-primary font-monospace">{{ $case->case_number }}</div>
                <small class="text-muted font-monospace">{{ $case->agreement?->account_number }}</small>
              </td>
              <td>
                <div class="fw-semibold">{{ $case->customer?->full_name }}</div>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $case->customer?->mobile_primary }}</small>
                <span class="d-block small text-muted font-monospace">{{ $case->customer?->cnic }}</span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $case->branch?->name }}</span>
              </td>
              <td>
                <div class="mb-1">{!! $case->stage_badge !!}</div>
                <small class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i>{{ $case->days_past_due }} Days Past Due</small>
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
              <td>
                {!! $case->status_badge !!}
              </td>
              <td class="text-center">
                <a href="{{ route('recovery.cases.show', $case) }}" class="btn btn-sm btn-primary">
                  <i class="bi bi-folder2-open me-1"></i>Docket
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                No recovery cases matching the selected filters.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($cases->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $cases->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
