<x-app-layout title="Managerial Approval Queue">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Credit Approval Queue</h1>
      <p class="text-muted mb-0">
        Review pending credit underwriting applications, verify DTI capacity, and authorize financing credit limits.
      </p>
    </div>
    <div>
      <a href="{{ route('credit.assessments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-list-columns-reverse me-1"></i>All Assessments Directory
      </a>
    </div>
  </div>

  <!-- Pending Applications Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0 text-dark">
        <i class="bi bi-hourglass-split text-warning me-2"></i>Applications Pending Review
      </h5>
      <span class="badge bg-warning text-dark fs-6">{{ $pendingAssessments->total() }} Awaiting Action</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Reference & Date</th>
              <th>Applicant</th>
              <th>Income & Debts</th>
              <th>DTI Ratio</th>
              <th>Risk Score</th>
              <th>Officer Recommendation</th>
              <th>Suggested Limit</th>
              <th class="text-end pe-4">Managerial Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pendingAssessments as $assessment)
              <tr>
                <td class="ps-4">
                  <span class="font-monospace fw-semibold text-dark small d-block">
                    {{ substr($assessment->ulid, -8) }}
                  </span>
                  <small class="text-muted">{{ $assessment->assessed_at?->diffForHumans() }}</small>
                </td>
                <td>
                  <a href="{{ route('customers.show', $assessment->customer) }}" class="fw-bold text-dark text-decoration-none">
                    {{ $assessment->customer->full_name }}
                  </a>
                  <div class="small text-muted font-monospace">{{ $assessment->customer->cnic }}</div>
                </td>
                <td>
                  <div class="small text-muted">
                    Income: <strong class="text-dark">Rs. {{ number_format($assessment->monthly_income) }}</strong>
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
                </td>
                <td>
                  <span class="fw-bold fs-6">{{ $assessment->score }}</span><span class="text-muted small">/100</span>
                  @php
                    $tierClasses = [
                      'low' => 'bg-success-subtle text-success',
                      'medium' => 'bg-info-subtle text-info',
                      'high' => 'bg-warning-subtle text-warning',
                      'critical' => 'bg-danger-subtle text-danger',
                    ];
                  @endphp
                  <span class="badge {{ $tierClasses[$assessment->risk_tier] ?? 'bg-secondary' }} small d-block mt-1 text-uppercase">
                    {{ $assessment->risk_tier }}
                  </span>
                </td>
                <td>
                  <span class="badge {{ $assessment->recommendation === 'approved' ? 'bg-success' : ($assessment->recommendation === 'conditional' ? 'bg-warning text-dark' : 'bg-danger') }} text-uppercase">
                    {{ $assessment->recommendation }}
                  </span>
                  <small class="text-muted d-block mt-1">by {{ $assessment->assessedBy->name }}</small>
                </td>
                <td>
                  <strong class="text-dark">Rs. {{ number_format($assessment->recommended_limit) }}</strong>
                </td>
                <td class="text-end pe-4">
                  <a href="{{ route('credit.assessments.show', $assessment) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-shield-check me-1"></i>Review & Decide
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-check2-circle text-success fs-1 d-block mb-2"></i>
                  All clear! There are no credit applications pending managerial review.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($pendingAssessments->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $pendingAssessments->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- Recent Decisions Audit Trail -->
  @if($recentDecisions->isNotEmpty())
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-0 text-dark">
          <i class="bi bi-clock-history text-muted me-2"></i>Recent Approval Decisions
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Decided At</th>
                <th>Applicant</th>
                <th>Authorized Limit</th>
                <th>Decision</th>
                <th>Authorizing Manager</th>
                <th class="text-end pe-4">Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentDecisions as $decision)
                <tr>
                  <td class="ps-4 text-muted">{{ $decision->decided_at?->format('d M Y, h:i A') }}</td>
                  <td>
                    <a href="{{ route('customers.show', $decision->customer) }}" class="fw-bold text-dark text-decoration-none">
                      {{ $decision->customer->full_name }}
                    </a>
                  </td>
                  <td>
                    <strong class="{{ $decision->decision === 'rejected' ? 'text-danger' : 'text-success' }}">
                      Rs. {{ number_format($decision->authorized_credit_limit) }}
                    </strong>
                  </td>
                  <td>
                    <span class="badge {{ $decision->decision === 'approved' ? 'bg-success' : ($decision->decision === 'conditional' ? 'bg-warning text-dark' : 'bg-danger') }} text-uppercase">
                      {{ $decision->decision }}
                    </span>
                  </td>
                  <td>{{ $decision->approvedBy->name }}</td>
                  <td class="text-end pe-4 text-muted">{{ Str::limit($decision->approval_notes, 40) ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</x-app-layout>
