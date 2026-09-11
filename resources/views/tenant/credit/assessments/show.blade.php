<x-app-layout title="Credit Assessment Dossier - {{ substr($creditAssessment->ulid, -8) }}">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('credit.assessments.index') }}">Credit Underwriting</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.show', $creditAssessment->customer) }}">{{ $creditAssessment->customer->full_name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Assessment #{{ substr($creditAssessment->ulid, -8) }}</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h1 class="h3 fw-bold mb-0">Underwriting Assessment Dossier</h1>
          @php
            $statusBadges = [
              'pending_approval' => 'bg-warning text-dark',
              'approved' => 'bg-success',
              'rejected' => 'bg-danger',
              'superseded' => 'bg-secondary',
            ];
          @endphp
          <span class="badge {{ $statusBadges[$creditAssessment->status] ?? 'bg-secondary' }} fs-6">
            {{ str_replace('_', ' ', ucfirst($creditAssessment->status)) }}
          </span>
        </div>
        <p class="text-muted mb-0">
          ULID: <span class="font-monospace fw-semibold">{{ $creditAssessment->ulid }}</span> &bull;
          Assessed on {{ $creditAssessment->assessed_at?->format('d M Y, h:i A') }} by <strong>{{ $creditAssessment->assessedBy->name }}</strong>
        </p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('customers.show', $creditAssessment->customer) }}" class="btn btn-outline-secondary">
          <i class="bi bi-person-lines-fill me-1"></i>Customer Dossier
        </a>
        <a href="{{ route('credit.assessments.index') }}" class="btn btn-outline-primary">
          <i class="bi bi-list-ul me-1"></i>Assessments Directory
        </a>
      </div>
    </div>
  </div>

  <!-- Metric Overview Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Computed Credit Score</span>
          <div class="d-flex align-items-baseline gap-2">
            <h3 class="fw-bold mb-0 text-dark">{{ $creditAssessment->score }}</h3>
            <span class="text-muted">/ 100</span>
          </div>
          @php
            $tierClasses = [
              'low' => 'bg-success-subtle text-success',
              'medium' => 'bg-info-subtle text-info',
              'high' => 'bg-warning-subtle text-warning',
              'critical' => 'bg-danger-subtle text-danger',
            ];
          @endphp
          <span class="badge {{ $tierClasses[$creditAssessment->risk_tier] ?? 'bg-secondary' }} text-uppercase mt-2">
            Risk Tier: {{ $creditAssessment->risk_tier }}
          </span>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Debt-to-Income (DTI) Ratio</span>
          <div class="d-flex align-items-baseline gap-2">
            <h3 class="fw-bold mb-0 {{ $creditAssessment->calculated_dti_percentage > 40.0 ? 'text-danger' : 'text-success' }}">
              {{ $creditAssessment->calculated_dti_percentage }}%
            </h3>
            <span class="small text-muted">(Cap: 40%)</span>
          </div>
          <div class="progress mt-2" style="height: 6px;">
            <div class="progress-bar {{ $creditAssessment->calculated_dti_percentage > 40.0 ? 'bg-danger' : ($creditAssessment->calculated_dti_percentage > 30.0 ? 'bg-warning' : 'bg-success') }}"
                 role="progressbar" style="width: {{ min(100, $creditAssessment->calculated_dti_percentage) }}%"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Recommended Credit Limit</span>
          <h3 class="fw-bold mb-0 text-primary">Rs. {{ number_format($creditAssessment->recommended_limit) }}</h3>
          <small class="text-muted d-block mt-2">
            Officer: <span class="fw-semibold text-capitalize">{{ $creditAssessment->recommendation }}</span>
          </small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Authorized Limit</span>
          <h3 class="fw-bold mb-0 text-dark">
            @if($creditAssessment->latestApproval && $creditAssessment->latestApproval->decision !== 'rejected')
              <span class="text-success">Rs. {{ number_format($creditAssessment->latestApproval->authorized_credit_limit) }}</span>
            @elseif($creditAssessment->status === 'rejected')
              <span class="text-danger">Rs. 0 (Rejected)</span>
            @else
              <span class="text-muted">Awaiting Decision</span>
            @endif
          </h3>
          <small class="text-muted d-block mt-2">Managerial authorization</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Underwriting Evaluation Details -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1 text-dark">Quantitative Capacity Breakdown</h5>
          <p class="text-muted small mb-0">Underwriting calculation inputs and household financial analysis.</p>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Verified Monthly Household Income</span>
                <h4 class="fw-bold text-dark mb-0">Rs. {{ number_format($creditAssessment->monthly_income) }}</h4>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Existing Monthly Debt Obligations</span>
                <h4 class="fw-bold text-dark mb-0">Rs. {{ number_format($creditAssessment->existing_debt_obligations) }}</h4>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Proposed Monthly Installment Quota</span>
                <h4 class="fw-bold text-primary mb-0">Rs. {{ number_format($creditAssessment->proposed_installment_limit) }}</h4>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Total Committed Monthly Obligations</span>
                <h4 class="fw-bold text-dark mb-0">
                  Rs. {{ number_format($creditAssessment->proposed_installment_limit + $creditAssessment->existing_debt_obligations) }}
                </h4>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <h6 class="fw-bold text-dark mb-3">Credit Officer Recommendation</h6>
          <div class="border rounded p-3 mb-3 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong class="text-dark">Recommendation:</strong>
              <span class="badge {{ $creditAssessment->recommendation === 'approved' ? 'bg-success' : ($creditAssessment->recommendation === 'conditional' ? 'bg-warning text-dark' : 'bg-danger') }} text-uppercase">
                {{ $creditAssessment->recommendation }}
              </span>
            </div>
            @if($creditAssessment->conditions_summary)
              <div class="mb-2">
                <span class="fw-semibold text-dark small d-block">Stipulations & Conditions:</span>
                <span class="text-muted small">{{ $creditAssessment->conditions_summary }}</span>
              </div>
            @endif
            @if($creditAssessment->assessment_notes)
              <div>
                <span class="fw-semibold text-dark small d-block">Officer Remarks:</span>
                <p class="text-muted small mb-0">{{ $creditAssessment->assessment_notes }}</p>
              </div>
            @endif
          </div>

          <div class="row g-2 small text-muted">
            <div class="col-sm-6">
              <i class="bi bi-person me-1"></i>Applicant: <strong>{{ $creditAssessment->customer->full_name }}</strong>
            </div>
            <div class="col-sm-6">
              <i class="bi bi-card-heading me-1"></i>CNIC: <strong>{{ $creditAssessment->customer->cnic }}</strong>
            </div>
            <div class="col-sm-6">
              <i class="bi bi-house me-1"></i>Residence: <strong class="text-capitalize">{{ str_replace('_', ' ', $creditAssessment->customer->residence_type) }}</strong>
            </div>
            <div class="col-sm-6">
              <i class="bi bi-shield-check me-1"></i>Guarantors: <strong>{{ $creditAssessment->customer->guarantors->count() }} Attached</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Managerial Sign-Off & Approval Decision -->
    <div class="col-lg-5">
      @if($creditAssessment->isPending() && auth()->user()->can('credit.approve'))
        <!-- Manager Decision Form -->
        <div class="card border-0 shadow-sm border-top border-4 border-primary mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-patch-check text-primary me-2"></i>Managerial Decision Sign-Off
            </h5>
            <p class="text-muted small mb-0">Record formal credit authorization or rejection.</p>
          </div>
          <div class="card-body p-4">
            @if($creditAssessment->dtiExceedsCap())
              <div class="alert alert-warning small mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Notice:</strong> This application's DTI ({{ $creditAssessment->calculated_dti_percentage }}%) exceeds the standard 40.0% cap. Approval requires documented managerial justification.
              </div>
            @endif

            <form method="POST" action="{{ route('credit.assessments.approve', $creditAssessment) }}">
              @csrf

              <div class="mb-3">
                <label for="decision" class="form-label fw-semibold text-dark">Authorization Decision <span class="text-danger">*</span></label>
                <select name="decision" id="decision" class="form-select @error('decision') is-invalid @enderror" required>
                  <option value="approved" {{ old('decision') === 'approved' ? 'selected' : '' }}>
                    Approve Application
                  </option>
                  <option value="conditional" {{ old('decision') === 'conditional' ? 'selected' : '' }}>
                    Conditional Approval
                  </option>
                  <option value="rejected" {{ old('decision') === 'rejected' ? 'selected' : '' }}>
                    Reject Application
                  </option>
                </select>
                @error('decision')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3" id="limitGroup">
                <label for="authorized_credit_limit" class="form-label fw-semibold text-dark">
                  Authorized Financed Credit Limit (PKR) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light">Rs.</span>
                  <input type="number" step="1000" min="0" max="5000000"
                         name="authorized_credit_limit" id="authorized_credit_limit"
                         class="form-control @error('authorized_credit_limit') is-invalid @enderror"
                         value="{{ old('authorized_credit_limit', $creditAssessment->recommended_limit) }}" required>
                </div>
                @error('authorized_credit_limit')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Maximum installment contract value allowed</small>
              </div>

              <div class="mb-3">
                <label for="conditions_imposed" class="form-label fw-semibold text-dark">Conditions Imposed</label>
                <input type="text" name="conditions_imposed" id="conditions_imposed"
                       class="form-control @error('conditions_imposed') is-invalid @enderror"
                       placeholder="e.g. 25% mandatory down payment; verified utility bill copy on file"
                       value="{{ old('conditions_imposed', $creditAssessment->conditions_summary) }}">
                @error('conditions_imposed')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-4">
                <label for="approval_notes" class="form-label fw-semibold text-dark">Managerial Remarks / Justification</label>
                <textarea name="approval_notes" id="approval_notes" rows="3"
                          class="form-control @error('approval_notes') is-invalid @enderror"
                          placeholder="Document authorization reason, committee sign-off notes, or rejection rationale...">{{ old('approval_notes') }}</textarea>
                @error('approval_notes')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-shield-fill-check me-1"></i>Record Formal Decision
              </button>
            </form>
          </div>
        </div>
      @endif

      <!-- Decision History / Sign-Off Certificate -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1 text-dark">Managerial Sign-Off Trail</h5>
          <p class="text-muted small mb-0">Official authorization records and committee decisions.</p>
        </div>
        <div class="card-body p-4">
          @forelse($creditAssessment->approvals as $approval)
            <div class="border rounded p-3 mb-3 {{ $approval->decision === 'rejected' ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : 'border-success-subtle bg-success-subtle bg-opacity-10' }}">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge {{ $approval->decision === 'approved' ? 'bg-success' : ($approval->decision === 'conditional' ? 'bg-warning text-dark' : 'bg-danger') }} text-uppercase">
                  {{ $approval->decision }}
                </span>
                <small class="text-muted">{{ $approval->decided_at?->format('d M Y, h:i A') }}</small>
              </div>
              <div class="mb-1">
                <span class="text-muted small">Authorized Limit:</span>
                <strong class="text-dark fs-5">Rs. {{ number_format($approval->authorized_credit_limit) }}</strong>
              </div>
              <div class="small text-muted mb-2">
                Sign-off: <strong>{{ $approval->approvedBy->name }}</strong>
                ({{ str_replace('_', ' ', ucfirst($approval->approval_level)) }})
              </div>
              @if($approval->conditions_imposed)
                <div class="small mb-1">
                  <strong>Conditions:</strong> {{ $approval->conditions_imposed }}
                </div>
              @endif
              @if($approval->approval_notes)
                <div class="small text-muted">
                  <strong>Notes:</strong> {{ $approval->approval_notes }}
                </div>
              @endif
            </div>
          @empty
            <div class="text-center py-4 text-muted">
              <i class="bi bi-clock-history fs-2 d-block mb-2 text-warning"></i>
              Application is currently pending managerial review. No formal authorization has been recorded yet.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
