<x-app-layout title="Customer Dossier: {{ $customer->full_name }}">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('customers.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Customer Directory
        </a>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h1 class="h3 fw-bold mb-0">{{ $customer->full_name }}</h1>
        <span class="badge bg-light text-dark border font-monospace">{{ $customer->cnic }}</span>
        @if($customer->status === 'active')
          <span class="badge bg-success-subtle text-success border border-success-subtle">Active (Verified)</span>
        @elseif($customer->status === 'pending_verification')
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending Verification</span>
        @elseif($customer->status === 'blacklisted')
          <span class="badge bg-danger text-white">Blacklisted Defaulter</span>
        @else
          <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($customer->status) }}</span>
        @endif
      </div>
      <p class="text-muted small mb-0 mt-1">
        Debtor Account ID: <code class="text-muted">{{ $customer->ulid }}</code> &bull; Registered {{ $customer->created_at->format('M d, Y') }}
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <!-- Credit Assessment Action -->
      @if(auth()->user()->can('credit.assess') && ! $customer->isBlacklisted())
        <a href="{{ route('customers.assessments.create', $customer) }}" class="btn btn-outline-success">
          <i class="bi bi-speedometer2 me-1"></i>Conduct Credit Assessment
        </a>
      @endif

      <!-- Blacklist Management Trigger -->
      @if(auth()->user()->can('credit.blacklist'))
        @if($customer->isBlacklisted())
          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#blacklistModal">
            <i class="bi bi-shield-check me-1"></i>Restore from Blacklist
          </button>
        @else
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#blacklistModal">
            <i class="bi bi-slash-circle me-1"></i>Blacklist Customer
          </button>
        @endif
      @endif

      <!-- Status Management Trigger -->
      <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal">
        <i class="bi bi-shield-shaded me-1"></i>Update Status
      </button>

      <!-- Log Field Investigation Trigger -->
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#verificationModal">
        <i class="bi bi-journal-check me-1"></i>Log Field Audit
      </button>

      <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
        <i class="bi bi-pencil me-1"></i>Edit Dossier
      </a>
    </div>
  </div>


  <!-- Credit & Underwriting Overview Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Dynamic Credit Score</span>
          <div class="d-flex align-items-baseline gap-2 mb-2">
            <h3 class="fw-bold mb-0 {{ $customer->credit_score >= 70 ? 'text-success' : ($customer->credit_score >= 40 ? 'text-primary' : 'text-danger') }}">
              {{ $customer->credit_score }}
            </h3>
            <span class="text-muted small">/ 100</span>
          </div>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar {{ $customer->credit_score >= 70 ? 'bg-success' : ($customer->credit_score >= 40 ? 'bg-primary' : 'bg-danger') }}" style="width: {{ $customer->credit_score }}%"></div>
          </div>
          <small class="text-muted d-block mt-2">
            {{ $customer->credit_score >= 70 ? 'Prime Reliability' : ($customer->credit_score >= 40 ? 'Standard Risk' : 'High Delinquency Risk') }}
          </small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Credit Limit Ceiling</span>
          <h3 class="fw-bold mb-0 text-dark">Rs. {{ number_format($customer->max_authorized_credit) }}</h3>
          <small class="text-muted d-block mt-2">Authorized financed capacity</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Active Agreements</span>
          <h3 class="fw-bold mb-0 text-dark">{{ $customer->creditProfile?->active_agreements_count ?? 0 }}</h3>
          <small class="text-muted d-block mt-2">{{ $customer->creditProfile?->completed_agreements_count ?? 0 }} Completed Closed</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Cumulative Overdue DPD</span>
          <h3 class="fw-bold mb-0 {{ ($customer->creditProfile?->total_dpd_days ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
            {{ $customer->creditProfile?->total_dpd_days ?? 0 }} <span class="fs-6 fw-normal text-muted">Days</span>
          </h3>
          <small class="text-muted d-block mt-2">Lifetime days past due</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Dossier Navigation Tabs -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-profile" type="button">
            <i class="bi bi-person-lines-fill me-1"></i>Profile & Residence
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-guarantors" type="button">
            <i class="bi bi-shield-check me-1"></i>Legal Guarantors ({{ $customer->guarantors->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-references" type="button">
            <i class="bi bi-people me-1"></i>Personal References ({{ $customer->references->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-verifications" type="button">
            <i class="bi bi-journal-check me-1"></i>Field Verification Audits ({{ $customer->verifications->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-credit" type="button">
            <i class="bi bi-speedometer2 me-1"></i>Credit Assessments ({{ $customer->creditAssessments->count() }})
          </button>
        </li>
      </ul>

    </div>
    <div class="card-body p-4">
      <div class="tab-content">
        <!-- Tab 1: Profile & Residence -->
        <div class="tab-pane fade show active" id="tab-profile">
          <div class="row g-4">
            <div class="col-md-6">
              <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person me-2"></i>Biographical Details</h6>
              <table class="table table-sm table-borderless">
                <tr>
                  <td class="text-muted" style="width: 180px;">Full Legal Name:</td>
                  <td class="fw-semibold text-dark">{{ $customer->full_name }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Father / Husband Name:</td>
                  <td class="text-dark">{{ $customer->father_or_husband_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Pakistani CNIC:</td>
                  <td class="fw-bold font-monospace text-dark">{{ $customer->cnic }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Gender:</td>
                  <td class="text-dark text-capitalize">{{ $customer->gender }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Primary Mobile:</td>
                  <td class="fw-semibold text-dark">{{ $customer->mobile_primary }}</td>
                </tr>
                <tr>
                  <td class="text-muted">WhatsApp Number:</td>
                  <td class="text-dark">{{ $customer->whatsapp_number ?? 'None' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Alternative Contact:</td>
                  <td class="text-dark">{{ $customer->mobile_secondary ?? 'None' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Email Address:</td>
                  <td class="text-dark">{{ $customer->email ?? 'None provided' }}</td>
                </tr>
              </table>
            </div>

            <div class="col-md-6">
              <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-house me-2"></i>Residence & Household Profile</h6>
              <table class="table table-sm table-borderless">
                <tr>
                  <td class="text-muted" style="width: 180px;">Present Address:</td>
                  <td class="text-dark fw-semibold">{{ $customer->present_address }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Permanent Address:</td>
                  <td class="text-dark">{{ $customer->permanent_address ?? 'Same as present address' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Residence Ownership:</td>
                  <td>
                    <span class="badge bg-{{ $customer->residence_type === 'owned' ? 'success' : ($customer->residence_type === 'rented' ? 'warning' : 'info') }}-subtle text-dark border text-capitalize">
                      {{ $customer->residence_type }} Property
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted">Tenure at Address:</td>
                  <td class="text-dark">{{ $customer->residence_tenure_years ? $customer->residence_tenure_years . ' Years' : 'Not recorded' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Utility Bill Consumer Ref #:</td>
                  <td class="text-dark font-monospace">{{ $customer->utility_bill_ref_number ?? 'None' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Monthly Household Income:</td>
                  <td class="text-dark fw-bold">
                    {{ $customer->monthly_household_income ? 'Rs. ' . number_format($customer->monthly_household_income, 2) : 'Not disclosed' }}
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab 2: Legal Guarantors -->
        <div class="tab-pane fade" id="tab-guarantors">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Registered Legal Guarantors</h6>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addGuarantorModal">
              <i class="bi bi-plus-lg me-1"></i>Add Guarantor
            </button>
          </div>

          <div class="row g-3">
            @forelse($customer->guarantors as $g)
              <div class="col-md-6">
                <div class="card border shadow-none h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $g->full_name }}</h6>
                        <span class="badge bg-light text-dark border font-monospace mt-1">{{ $g->cnic }}</span>
                      </div>
                      <div>
                        @if($g->is_verified)
                          <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Verified</span>
                        @else
                          <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Unverified</span>
                        @endif
                      </div>
                    </div>
                    <hr class="my-2">
                    <div class="small">
                      <div><strong class="text-muted">Relation:</strong> <span class="text-capitalize">{{ $g->relationship }}</span></div>
                      <div><strong class="text-muted">Mobile:</strong> {{ $g->mobile }}</div>
                      <div><strong class="text-muted">Occupation:</strong> {{ $g->occupation ?? 'Not specified' }} &bull; {{ $g->employer_name ?? 'N/A' }}</div>
                      <div><strong class="text-muted">Income:</strong> {{ $g->monthly_income ? 'Rs. ' . number_format($g->monthly_income) : 'N/A' }}</div>
                      <div><strong class="text-muted">Address:</strong> {{ $g->address }}</div>
                    </div>
                    <div class="d-flex gap-2 mt-3 pt-2 border-top">
                      <form method="POST" action="{{ route('guarantors.toggle-verified', $g) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $g->is_verified ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                          <i class="bi {{ $g->is_verified ? 'bi-x-circle' : 'bi-check-circle' }} me-1"></i>
                          {{ $g->is_verified ? 'Mark Unverified' : 'Mark Verified' }}
                        </button>
                      </form>
                      <form method="POST" action="{{ route('guarantors.destroy', $g) }}" onsubmit="return confirm('Remove guarantor {{ $g->full_name }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12 py-4 text-center text-muted">
                <i class="bi bi-shield-x fs-1 d-block mb-2 text-warning"></i>
                No guarantors on file. At least 1 verified guarantor is required before credit approval.
              </div>
            @endforelse
          </div>
        </div>

        <!-- Tab 3: Personal References -->
        <div class="tab-pane fade" id="tab-references">
          <h6 class="fw-bold mb-3">Personal & Neighbor References</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Full Name</th>
                  <th>Relationship</th>
                  <th>Mobile Number</th>
                  <th>Address / Location</th>
                  <th>Recorded At</th>
                </tr>
              </thead>
              <tbody>
                @forelse($customer->references as $ref)
                  <tr>
                    <td class="fw-bold">{{ $ref->full_name }}</td>
                    <td>{{ $ref->relationship }}</td>
                    <td>{{ $ref->mobile }}</td>
                    <td>{{ $ref->address ?? 'N/A' }}</td>
                    <td class="text-muted small">{{ $ref->created_at->format('M d, Y') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No personal references on file.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 4: Field Verification Audits -->
        <div class="tab-pane fade" id="tab-verifications">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Investigation History & Field Reports</h6>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#verificationModal">
              <i class="bi bi-journal-plus me-1"></i>Log Field Report
            </button>
          </div>

          <div class="vstack gap-3">
            @forelse($customer->verifications as $v)
              <div class="card border shadow-none">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize me-2">
                        <i class="bi bi-geo-alt me-1"></i>{{ str_replace('_', ' ', $v->verification_type) }}
                      </span>
                      <strong class="text-dark">Investigator: {{ $v->verifiedBy->name ?? 'Staff Officer' }}</strong>
                      <span class="text-muted small ms-2">&bull; {{ $v->verified_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <div>
                      @if($v->outcome === 'approved')
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Approved</span>
                      @elseif($v->outcome === 'conditional')
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-exclamation-circle me-1"></i>Conditional</span>
                      @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                      @endif
                    </div>
                  </div>
                  <div class="d-flex gap-4 my-2 small">
                    <div>
                      <i class="bi {{ $v->residence_confirmed ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} me-1"></i>
                      Residence Confirmed: <strong>{{ $v->residence_confirmed ? 'Yes' : 'No' }}</strong>
                    </div>
                    <div>
                      <i class="bi {{ $v->workplace_confirmed ? 'bi-check-circle-fill text-success' : 'bi-dash-circle text-muted' }} me-1"></i>
                      Workplace Confirmed: <strong>{{ $v->workplace_confirmed ? 'Yes' : 'N/A' }}</strong>
                    </div>
                  </div>
                  <div class="bg-light rounded p-3 text-dark small mt-2">
                    <strong>Investigator Assessment:</strong>
                    <p class="mb-0 mt-1">{{ $v->investigator_notes }}</p>
                  </div>
                </div>
              </div>
            @empty
              <div class="py-4 text-center text-muted">
                <i class="bi bi-clipboard-x fs-1 d-block mb-2 text-secondary"></i>
                No field or telephonic investigation reports recorded yet.
              </div>
            @endforelse
          </div>
        </div>

        <!-- Tab 5: Credit Assessments & Approvals -->
        <div class="tab-pane fade" id="tab-credit">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h6 class="fw-bold text-primary mb-1"><i class="bi bi-speedometer2 me-2"></i>Underwriting History & Credit Approvals</h6>
              <p class="text-muted small mb-0">Record of quantitative risk scores, DTI calculations, and managerial credit limit sign-offs.</p>
            </div>
            @if(auth()->user()->can('credit.assess') && ! $customer->isBlacklisted())
              <a href="{{ route('customers.assessments.create', $customer) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>New Credit Assessment
              </a>
            @endif
          </div>

          @forelse($customer->creditAssessments as $assessment)
            <div class="card border mb-3 {{ $assessment->status === 'approved' ? 'border-success-subtle bg-success-subtle bg-opacity-10' : ($assessment->status === 'rejected' ? 'border-danger-subtle' : '') }}">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                  <div>
                    <span class="font-monospace fw-semibold text-dark">Assessment #{{ substr($assessment->ulid, -8) }}</span>
                    <small class="text-muted ms-2">{{ $assessment->assessed_at?->format('d M Y, h:i A') }}</small>
                    <span class="text-muted small ms-2">by {{ $assessment->assessedBy->name }}</span>
                  </div>
                  <div class="d-flex gap-2 align-items-center">
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
                    <a href="{{ route('credit.assessments.show', $assessment) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                      <i class="bi bi-eye me-1"></i>Dossier
                    </a>
                  </div>
                </div>

                <div class="row g-3 small mt-1">
                  <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block">Monthly Obligations</span>
                    <strong>Rs. {{ number_format($assessment->proposed_installment_limit) }}</strong>
                    <span class="text-muted">/ {{ number_format($assessment->monthly_income) }}</span>
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block">DTI Ratio</span>
                    <strong class="{{ $assessment->calculated_dti_percentage > 40.0 ? 'text-danger' : 'text-success' }}">
                      {{ $assessment->calculated_dti_percentage }}%
                    </strong>
                    @if($assessment->calculated_dti_percentage > 40.0)
                      <span class="badge bg-danger-subtle text-danger small">Over Cap</span>
                    @endif
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block">Score & Risk Tier</span>
                    <strong>{{ $assessment->score }}/100</strong>
                    <span class="badge bg-light text-dark border ms-1 text-uppercase">{{ $assessment->risk_tier }}</span>
                  </div>
                  <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block">Authorized Limit</span>
                    @if($assessment->latestApproval && $assessment->latestApproval->decision !== 'rejected')
                      <strong class="text-success">Rs. {{ number_format($assessment->latestApproval->authorized_credit_limit) }}</strong>
                    @elseif($assessment->status === 'rejected')
                      <strong class="text-danger">Rs. 0</strong>
                    @else
                      <span class="text-muted">Pending Manager Sign-off</span>
                    @endif
                  </div>
                </div>

                @if($assessment->conditions_summary)
                  <div class="mt-2 pt-2 border-top small text-muted">
                    <strong>Conditions:</strong> {{ $assessment->conditions_summary }}
                  </div>
                @endif
              </div>
            </div>
          @empty
            <div class="py-4 text-center text-muted border rounded">
              <i class="bi bi-speedometer2 fs-1 d-block mb-2 text-secondary"></i>
              No credit assessments recorded for this customer yet.
              @if(auth()->user()->can('credit.assess') && ! $customer->isBlacklisted())
                <div class="mt-2">
                  <a href="{{ route('customers.assessments.create', $customer) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-calculator me-1"></i>Conduct First Assessment
                  </a>
                </div>
              @endif
            </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>


  <!-- Modal: Add Guarantor -->
  <div class="modal fade" id="addGuarantorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content text-start">
        <form action="{{ route('customers.guarantors.store', $customer) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Add Legal Guarantor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Guarantor Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" required placeholder="Full Name">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Guarantor CNIC <span class="text-danger">*</span></label>
                <input type="text" name="cnic" class="form-control" required placeholder="XXXXX-XXXXXXX-X">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Relationship <span class="text-danger">*</span></label>
                <select name="relationship" class="form-select" required>
                  <option value="brother">Brother</option>
                  <option value="father">Father</option>
                  <option value="uncle">Uncle</option>
                  <option value="colleague">Colleague</option>
                  <option value="friend">Friend</option>
                  <option value="cousin">Cousin</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                <input type="text" name="mobile" class="form-control" required placeholder="0300-1234567">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Occupation</label>
                <input type="text" name="occupation" class="form-control" placeholder="Job Title / Business">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Employer Name</label>
                <input type="text" name="employer_name" class="form-control" placeholder="Company / Department">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Monthly Income (PKR)</label>
                <input type="number" step="0.01" name="monthly_income" class="form-control" placeholder="0.00">
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control" rows="2" required placeholder="Physical residence address"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Guarantor</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Log Verification Report -->
  <div class="modal fade" id="verificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start">
        <form action="{{ route('customers.verifications.store', $customer) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Log Field Investigation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Investigation Channel</label>
              <select name="verification_type" class="form-select" required>
                <option value="field_visit">Physical Field Residence Visit</option>
                <option value="telephonic">Telephonic Verification</option>
                <option value="utility_bill">Utility Bill Document Check</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Verification Date & Time</label>
              <input type="datetime-local" name="verified_at" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="residence_confirmed" value="1" id="resCheck" checked>
              <label class="form-check-label fw-semibold" for="resCheck">
                Physical Residence Confirmed
              </label>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="workplace_confirmed" value="1" id="workCheck">
              <label class="form-check-label fw-semibold" for="workCheck">
                Workplace / Employer Confirmed
              </label>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Investigation Assessment Outcome</label>
              <select name="outcome" class="form-select" required>
                <option value="approved">Approved (Satisfactory & Verified)</option>
                <option value="conditional">Conditional Approval (Minor Discrepancy)</option>
                <option value="rejected">Rejected (Untraceable / Fraudulent)</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Investigator Notes & Observations</label>
              <textarea name="investigator_notes" class="form-control" rows="3" placeholder="Enter findings, neighbor feedback, utility bill match..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit Investigation</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Update Status -->
  <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start">
        <form action="{{ route('customers.toggle-status', $customer) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Update Debtor Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Account Status</label>
              <select name="status" class="form-select" id="statusSelect" required>
                <option value="pending_verification" {{ $customer->status === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>Active (Approved)</option>
                <option value="restricted" {{ $customer->status === 'restricted' ? 'selected' : '' }}>Restricted</option>
                <option value="blacklisted" {{ $customer->status === 'blacklisted' ? 'selected' : '' }}>Blacklisted Defaulter</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Reason / Audit Remark</label>
              <textarea name="blacklisted_reason" class="form-control" rows="2" placeholder="State reason for status transition...">{{ $customer->creditProfile?->blacklisted_reason }}</textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Blacklist Management (FR-06.4) -->
  <div class="modal fade" id="blacklistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start">
        <form action="{{ route('customers.blacklist.toggle', $customer) }}" method="POST">
          @csrf
          <div class="modal-header {{ $customer->isBlacklisted() ? 'bg-success text-white' : 'bg-danger text-white' }}">
            <h5 class="modal-title fw-bold">
              <i class="bi {{ $customer->isBlacklisted() ? 'bi-shield-check' : 'bi-exclamation-triangle-fill' }} me-2"></i>
              {{ $customer->isBlacklisted() ? 'Restore Customer from Blacklist' : 'Blacklist Defaulter' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            @if($customer->isBlacklisted())
              <div class="alert alert-info small mb-3">
                Restoring this customer will remove them from the institutional blacklist and place them in <strong>Restricted</strong> status for supervisory review.
              </div>
              <p class="text-dark mb-0">
                Are you sure you want to restore <strong>{{ $customer->full_name }}</strong> (CNIC: {{ $customer->cnic }})?
              </p>
            @else
              <div class="alert alert-danger small mb-3">
                <strong>CRITICAL WARNING:</strong> Blacklisting an applicant immediately revokes all credit limits, rejects pending credit assessments, and prevents drafting any future installment agreements across all showrooms.
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-dark">Mandatory Blacklist Reason / Default Reference <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="3" required
                          placeholder="e.g. Willful default on contract #1084, non-responsive after legal notice issued, recovery absconder..."></textarea>
              </div>
            @endif
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn {{ $customer->isBlacklisted() ? 'btn-success' : 'btn-danger' }}">
              {{ $customer->isBlacklisted() ? 'Confirm Restoration' : 'Confirm Blacklist' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>

