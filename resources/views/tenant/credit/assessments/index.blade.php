<x-app-layout title="Credit Underwriting Center">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Credit Underwriting Center</h1>
      <p class="text-muted mb-0">
        Quantitative risk analysis, Debt-to-Income (DTI) evaluation, and multi-tier credit authorization workflow.
      </p>
    </div>
    <div class="d-flex gap-2">
      @if(auth()->user()->can('credit.approve'))
        <a href="{{ route('credit.approvals.index') }}" class="btn btn-outline-primary">
          <i class="bi bi-clock-history me-1"></i>Approval Queue
          @if($pendingCount > 0)
            <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
          @endif
        </a>
      @endif
      <a href="{{ route('customers.index') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>New Assessment (via Customer)
      </a>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary-subtle text-primary rounded p-3 me-3">
            <i class="bi bi-speedometer2 fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Total Assessments</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalAssessments }}</h4>
            <small class="text-muted">Historical evaluations</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning-subtle text-warning rounded p-3 me-3">
            <i class="bi bi-hourglass-split fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Pending Approvals</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $pendingCount }}</h4>
            <small class="text-muted">Awaiting Manager sign-off</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success-subtle text-success rounded p-3 me-3">
            <i class="bi bi-shield-check fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Authorized Active</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $approvedCount }}</h4>
            <small class="text-muted">{{ $rejectedCount }} Rejected Applications</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info-subtle text-info rounded p-3 me-3">
            <i class="bi bi-pie-chart fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Avg Portfolio DTI</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $avgDti }}%</h4>
            <small class="text-muted">Institutional Cap: 40.0%</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('credit.assessments.index') }}" class="row g-3 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 ps-0"
                   placeholder="Search by customer name, CNIC, mobile, or Assessment ULID..."
                   value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Lifecycle Statuses</option>
            <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            <option value="superseded" {{ request('status') === 'superseded' ? 'selected' : '' }}>Superseded</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="risk_tier" class="form-select">
            <option value="">All Risk Tiers</option>
            <option value="low" {{ request('risk_tier') === 'low' ? 'selected' : '' }}>Low (Prime)</option>
            <option value="medium" {{ request('risk_tier') === 'medium' ? 'selected' : '' }}>Medium (Standard)</option>
            <option value="high" {{ request('risk_tier') === 'high' ? 'selected' : '' }}>High Risk</option>
            <option value="critical" {{ request('risk_tier') === 'critical' ? 'selected' : '' }}>Critical (Subprime)</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
          @if(request()->hasAny(['search', 'status', 'risk_tier']))
            <a href="{{ route('credit.assessments.index') }}" class="btn btn-outline-secondary" title="Reset Filters">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Credit Assessments Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Reference & Date</th>
              <th>Applicant Customer</th>
              <th>Income vs Proposed Obligation</th>
              <th>DTI Ratio</th>
              <th>Risk Score & Tier</th>
              <th>Officer Recommendation</th>
              <th>Status</th>
              <th class="text-end pe-4">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($assessments as $assessment)
              <tr>
                <td class="ps-4">
                  <span class="fw-semibold text-dark font-monospace small d-block">
                    {{ substr($assessment->ulid, -8) }}
                  </span>
                  <small class="text-muted">{{ $assessment->assessed_at?->format('d M Y, h:i A') }}</small>
                </td>
                <td>
                  <a href="{{ route('customers.show', $assessment->customer) }}" class="fw-bold text-decoration-none text-dark">
                    {{ $assessment->customer->full_name }}
                  </a>
                  <div class="small text-muted font-monospace">{{ $assessment->customer->cnic }}</div>
                </td>
                <td>
                  <div>
                    <span class="text-muted small">Monthly:</span>
                    <strong class="text-dark">Rs. {{ number_format($assessment->monthly_income) }}</strong>
                  </div>
                  <div class="small text-muted">
                    Proposed: <strong class="text-primary">Rs. {{ number_format($assessment->proposed_installment_limit) }}</strong>/mo
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold {{ $assessment->calculated_dti_percentage > 40.0 ? 'text-danger' : 'text-success' }}">
                      {{ $assessment->calculated_dti_percentage }}%
                    </span>
                    @if($assessment->calculated_dti_percentage > 40.0)
                      <span class="badge bg-danger-subtle text-danger small">Over Cap</span>
                    @endif
                  </div>
                  <div class="progress mt-1" style="height: 4px; width: 80px;">
                    <div class="progress-bar {{ $assessment->calculated_dti_percentage > 40.0 ? 'bg-danger' : ($assessment->calculated_dti_percentage > 30.0 ? 'bg-warning' : 'bg-success') }}"
                         role="progressbar" style="width: {{ min(100, $assessment->calculated_dti_percentage) }}%"></div>
                  </div>
                </td>
                <td>
                  <div class="d-flex align-items-center gap-1">
                    <span class="fw-bold fs-6">{{ $assessment->score }}</span>
                    <span class="text-muted small">/100</span>
                  </div>
                  @php
                    $tierClasses = [
                      'low' => 'bg-success-subtle text-success',
                      'medium' => 'bg-info-subtle text-info',
                      'high' => 'bg-warning-subtle text-warning',
                      'critical' => 'bg-danger-subtle text-danger',
                    ];
                  @endphp
                  <span class="badge {{ $tierClasses[$assessment->risk_tier] ?? 'bg-secondary' }} small text-uppercase">
                    {{ $assessment->risk_tier }}
                  </span>
                </td>
                <td>
                  @php
                    $recClasses = [
                      'approved' => 'text-success',
                      'conditional' => 'text-warning',
                      'rejected' => 'text-danger',
                    ];
                  @endphp
                  <strong class="{{ $recClasses[$assessment->recommendation] ?? 'text-secondary' }} d-block">
                    {{ ucfirst($assessment->recommendation) }}
                  </strong>
                  <small class="text-muted">Limit: Rs. {{ number_format($assessment->recommended_limit) }}</small>
                </td>
                <td>
                  @php
                    $statusBadges = [
                      'pending_approval' => 'bg-warning text-dark',
                      'approved' => 'bg-success',
                      'rejected' => 'bg-danger',
                      'superseded' => 'bg-secondary',
                    ];
                  @endphp
                  <span class="badge {{ $statusBadges[$assessment->status] ?? 'bg-secondary' }}">
                    {{ str_replace('_', ' ', ucfirst($assessment->status)) }}
                  </span>
                </td>
                <td class="text-end pe-4">
                  <a href="{{ route('credit.assessments.show', $assessment) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>View Dossier
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary"></i>
                  No credit assessments found matching your filter criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($assessments->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $assessments->links() }}
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
