<x-app-layout title="Conduct Credit Assessment - {{ $customer->full_name }}">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('credit.assessments.index') }}">Credit Underwriting</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer) }}">{{ $customer->full_name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Conduct Assessment</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Credit Risk Underwriting Sheet</h1>
        <p class="text-muted mb-0">
          Assess debt capacity, evaluate Debt-to-Income (DTI) ratio, and formulate authorization recommendations for <strong>{{ $customer->full_name }}</strong> (CNIC: <span class="font-monospace">{{ $customer->cnic }}</span>).
        </p>
      </div>
      <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Dossier
      </a>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Customer Baseline Underwriting Profile -->
    <div class="col-lg-4">
      <!-- Profile Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-person-badge text-primary me-2"></i>Applicant Baseline
          </h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Applicant Name</span>
              <strong class="text-dark">{{ $customer->full_name }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Pakistani CNIC</span>
              <span class="font-monospace fw-semibold">{{ $customer->cnic }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Declared Income</span>
              <strong class="text-success">Rs. {{ number_format($customer->monthly_household_income) }}</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Residence Status</span>
              <span class="text-dark fw-semibold text-capitalize">{{ str_replace('_', ' ', $customer->residence_type) }} ({{ $customer->residence_tenure_years }} yrs)</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Current Credit Score</span>
              <span class="badge bg-primary fs-6">{{ $customer->credit_score }}/100</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Cumulative Overdue DPD</span>
              <span class="fw-bold {{ ($customer->creditProfile?->total_dpd_days ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                {{ $customer->creditProfile?->total_dpd_days ?? 0 }} Days
              </span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Legal Guarantors Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-shield-check text-success me-2"></i>Guarantors
          </h5>
          <span class="badge bg-light text-dark">{{ $customer->guarantors->count() }} Attached</span>
        </div>
        <div class="card-body">
          @forelse($customer->guarantors as $guarantor)
            <div class="border rounded p-2 mb-2 {{ $guarantor->is_verified ? 'border-success-subtle bg-success-subtle' : 'border-warning-subtle bg-warning-subtle' }}">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="text-dark small">{{ $guarantor->full_name }}</strong>
                @if($guarantor->is_verified)
                  <span class="badge bg-success small"><i class="bi bi-check-circle me-1"></i>Verified</span>
                @else
                  <span class="badge bg-warning text-dark small"><i class="bi bi-hourglass me-1"></i>Pending</span>
                @endif
              </div>
              <div class="text-muted small">
                {{ $guarantor->relationship }} &bull; {{ $guarantor->occupation ?? 'Employed' }}
              </div>
              <div class="text-dark small mt-1">
                Income: <strong>Rs. {{ number_format($guarantor->monthly_income) }}</strong>
              </div>
            </div>
          @empty
            <div class="text-muted small text-center py-3">
              <i class="bi bi-exclamation-triangle text-warning d-block fs-3 mb-1"></i>
              No legal guarantors attached. Recommendation: At least 1-2 verified guarantors required for electronic financing.
            </div>
          @endforelse
        </div>
      </div>

      <!-- Institutional Underwriting Policy Card -->
      <div class="card border-0 shadow-sm bg-light">
        <div class="card-body">
          <h6 class="fw-bold text-dark mb-2">
            <i class="bi bi-info-circle text-primary me-1"></i>Regulatory Standards
          </h6>
          <ul class="text-muted small mb-0 ps-3">
            <li class="mb-1"><strong>Debt-to-Income (DTI) Cap:</strong> Institutional maximum is <strong>40.0%</strong>.</li>
            <li class="mb-1"><strong>Over-Cap Exception:</strong> DTI > 40% requires higher down payment or 2nd government guarantor.</li>
            <li><strong>Decision Authority:</strong> Approvals granted by Branch Managers or Company Directors.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Right Column: Interactive Underwriting Sheet & DTI Calculator -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h4 class="fw-bold mb-1 text-dark">Quantitative Risk Assessment</h4>
          <p class="text-muted small mb-0">
            Adjust verified financial values to evaluate live debt capacity and risk score calculation.
          </p>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('customers.assessments.store', $customer) }}" id="assessmentForm">
            @csrf

            <!-- Section 1: Financial Capacity & Debt Calculation -->
            <div class="row g-3 mb-4">
              <div class="col-12">
                <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2">
                  <i class="bi bi-calculator me-1"></i>1. Capacity & Obligations
                </h6>
              </div>

              <div class="col-md-4">
                <label for="monthly_income" class="form-label fw-semibold text-dark">
                  Verified Monthly Income (PKR) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light">Rs.</span>
                  <input type="number" step="500" min="5000" max="10000000"
                         name="monthly_income" id="monthly_income"
                         class="form-control @error('monthly_income') is-invalid @enderror"
                         value="{{ old('monthly_income', $defaultIncome) }}" required>
                </div>
                @error('monthly_income')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Net household earnings</small>
              </div>

              <div class="col-md-4">
                <label for="existing_debt_obligations" class="form-label fw-semibold text-dark">
                  Existing Monthly Debts (PKR)
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light">Rs.</span>
                  <input type="number" step="500" min="0" max="10000000"
                         name="existing_debt_obligations" id="existing_debt_obligations"
                         class="form-control @error('existing_debt_obligations') is-invalid @enderror"
                         value="{{ old('existing_debt_obligations', 0) }}">
                </div>
                @error('existing_debt_obligations')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Other active loan / installment payments</small>
              </div>

              <div class="col-md-4">
                <label for="proposed_installment_limit" class="form-label fw-semibold text-dark">
                  Proposed Monthly Installment (PKR) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light">Rs.</span>
                  <input type="number" step="500" min="1000" max="10000000"
                         name="proposed_installment_limit" id="proposed_installment_limit"
                         class="form-control @error('proposed_installment_limit') is-invalid @enderror"
                         value="{{ old('proposed_installment_limit', $defaultInstallment) }}" required>
                </div>
                @error('proposed_installment_limit')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Applicant requested installment quota</small>
              </div>
            </div>

            <!-- Dynamic Live DTI & Risk Calculation Preview Panel -->
            <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-4">
              <div class="card-body p-3">
                <div class="row align-items-center text-center text-md-start g-3">
                  <div class="col-md-3">
                    <span class="text-muted small fw-semibold d-block">Total Monthly Obligations</span>
                    <strong class="fs-5 text-dark" id="previewTotalDebt">Rs. {{ number_format($defaultInstallment) }}</strong>
                  </div>
                  <div class="col-md-3 border-start">
                    <span class="text-muted small fw-semibold d-block">Calculated DTI Ratio</span>
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                      <strong class="fs-4 text-dark" id="previewDti">{{ $initialDti }}%</strong>
                      <span id="previewDtiBadge" class="badge bg-success">Compliant</span>
                    </div>
                  </div>
                  <div class="col-md-3 border-start">
                    <span class="text-muted small fw-semibold d-block">Calculated Score</span>
                    <strong class="fs-4 text-dark" id="previewScore">{{ $initialScore }}<span class="fs-6 text-muted">/100</span></strong>
                  </div>
                  <div class="col-md-3 border-start">
                    <span class="text-muted small fw-semibold d-block">Evaluated Risk Tier</span>
                    <span id="previewRiskTier" class="badge bg-info text-uppercase fs-6">Standard</span>
                  </div>
                </div>

                <!-- Over-Cap Warning Alert -->
                <div id="dtiWarningAlert" class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0 d-none">
                  <i class="bi bi-exclamation-octagon-fill fs-4 flex-shrink-0"></i>
                  <div>
                    <strong>Regulatory Warning:</strong> Debt-to-Income exceeds the standard 40.0% regulatory cap.
                    Requires conditional mitigation (higher down payment or secondary guarantor).
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 2: Officer Recommendation & Authorized Limit -->
            <div class="row g-3 mb-4">
              <div class="col-12">
                <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2">
                  <i class="bi bi-award me-1"></i>2. Underwriting Recommendation & Limits
                </h6>
              </div>

              <div class="col-md-6">
                <label for="recommended_limit" class="form-label fw-semibold text-dark">
                  Recommended Total Credit Limit (PKR) <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-light">Rs.</span>
                  <input type="number" step="1000" min="10000" max="5000000"
                         name="recommended_limit" id="recommended_limit"
                         class="form-control @error('recommended_limit') is-invalid @enderror"
                         value="{{ old('recommended_limit', $suggestedLimit) }}" required>
                </div>
                @error('recommended_limit')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Suggested ceiling based on disposable income: Rs. {{ number_format($suggestedLimit) }}</small>
              </div>

              <div class="col-md-6">
                <label for="recommendation" class="form-label fw-semibold text-dark">
                  Credit Officer Recommendation <span class="text-danger">*</span>
                </label>
                <select name="recommendation" id="recommendation" class="form-select @error('recommendation') is-invalid @enderror" required>
                  <option value="approved" {{ old('recommendation', 'approved') === 'approved' ? 'selected' : '' }}>
                    Approve (Standard Terms & Down Payment)
                  </option>
                  <option value="conditional" {{ old('recommendation') === 'conditional' ? 'selected' : '' }}>
                    Conditional Approval (Extra Guarantor / Higher Down Payment)
                  </option>
                  <option value="rejected" {{ old('recommendation') === 'rejected' ? 'selected' : '' }}>
                    Reject (Insufficient Capacity / High Delinquency Risk)
                  </option>
                </select>
                @error('recommendation')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-12" id="conditionsGroup">
                <label for="conditions_summary" class="form-label fw-semibold text-dark">
                  Approval Conditions / Stipulations
                </label>
                <input type="text" name="conditions_summary" id="conditions_summary"
                       class="form-control @error('conditions_summary') is-invalid @enderror"
                       placeholder="e.g. Minimum 30% cash down payment required and 1 government employee guarantor co-signer"
                       value="{{ old('conditions_summary') }}">
                @error('conditions_summary')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Stipulations that must be met before agreement activation.</small>
              </div>

              <div class="col-12">
                <label for="assessment_notes" class="form-label fw-semibold text-dark">
                  Credit Officer Assessment Remarks
                </label>
                <textarea name="assessment_notes" id="assessment_notes" rows="3"
                          class="form-control @error('assessment_notes') is-invalid @enderror"
                          placeholder="Document findings from applicant interview, showroom consultation, and residence observation...">{{ old('assessment_notes') }}</textarea>
                @error('assessment_notes')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <a href="{{ route('customers.show', $customer) }}" class="btn btn-light px-4">Cancel</a>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i>Submit Assessment for Manager Review
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const incomeInput = document.getElementById('monthly_income');
      const debtInput = document.getElementById('existing_debt_obligations');
      const installmentInput = document.getElementById('proposed_installment_limit');

      const previewTotalDebt = document.getElementById('previewTotalDebt');
      const previewDti = document.getElementById('previewDti');
      const previewDtiBadge = document.getElementById('previewDtiBadge');
      const dtiWarningAlert = document.getElementById('dtiWarningAlert');
      const previewRiskTier = document.getElementById('previewRiskTier');

      function recalculateLive() {
        const income = parseFloat(incomeInput.value) || 0;
        const debts = parseFloat(debtInput.value) || 0;
        const installment = parseFloat(installmentInput.value) || 0;

        const totalObligations = installment + debts;
        previewTotalDebt.innerText = 'Rs. ' + totalObligations.toLocaleString();

        let dti = 100;
        if (income > 0) {
          dti = Math.min(100, Math.max(0, (totalObligations / income) * 100));
        }
        const dtiRounded = dti.toFixed(1);
        previewDti.innerText = dtiRounded + '%';

        if (dti > 40.0) {
          previewDtiBadge.className = 'badge bg-danger';
          previewDtiBadge.innerText = 'Exceeds Cap (40%)';
          dtiWarningAlert.classList.remove('d-none');
          previewRiskTier.className = 'badge bg-danger text-uppercase fs-6';
          previewRiskTier.innerText = dti > 50 ? 'Critical' : 'High Risk';
        } else if (dti > 30.0) {
          previewDtiBadge.className = 'badge bg-warning text-dark';
          previewDtiBadge.innerText = 'Moderate (30-40%)';
          dtiWarningAlert.classList.add('d-none');
          previewRiskTier.className = 'badge bg-info text-uppercase fs-6';
          previewRiskTier.innerText = 'Medium';
        } else {
          previewDtiBadge.className = 'badge bg-success';
          previewDtiBadge.innerText = 'Compliant (<30%)';
          dtiWarningAlert.classList.add('d-none');
          previewRiskTier.className = 'badge bg-success text-uppercase fs-6';
          previewRiskTier.innerText = 'Low Risk';
        }
      }

      incomeInput.addEventListener('input', recalculateLive);
      debtInput.addEventListener('input', recalculateLive);
      installmentInput.addEventListener('input', recalculateLive);

      recalculateLive();
    });
  </script>
  @endpush
</x-app-layout>
