<x-app-layout title="Register Customer">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('customers.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Customer Directory
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Customer Onboarding & Registration</h1>
      <p class="text-muted mb-0">
        Register an installment applicant, capture Pakistani CNIC identity, primary guarantor, and personal reference.
      </p>
    </div>
  </div>

  <form method="POST" action="{{ route('customers.store') }}">
    @csrf

    <div class="row g-4">
      <div class="col-lg-8">
        <!-- Section 1: Customer Identification & Contact -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i>1. Customer Identity & Contact</h5>
            <p class="text-muted small mb-0">National identity and primary communication details.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Customer Full Legal Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" placeholder="e.g. Muhammad Aslam" required>
                @error('full_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Father / Husband Name</label>
                <input type="text" name="father_or_husband_name" class="form-control @error('father_or_husband_name') is-invalid @enderror" value="{{ old('father_or_husband_name') }}" placeholder="e.g. Abdul Ghafoor">
                @error('father_or_husband_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pakistani CNIC Number <span class="text-danger">*</span></label>
                <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror" value="{{ old('cnic') }}" placeholder="35201-1234567-1" maxlength="20" required>
                <small class="text-muted">Standard 13-digit Pakistani format (XXXXX-XXXXXXX-X)</small>
                @error('cnic')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                  <option value="male" {{ old('gender', 'male') === 'male' ? 'selected' : '' }}>Male</option>
                  <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                  <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Primary Mobile <span class="text-danger">*</span></label>
                <input type="text" name="mobile_primary" class="form-control @error('mobile_primary') is-invalid @enderror" value="{{ old('mobile_primary') }}" placeholder="0300-1234567" required>
                @error('mobile_primary')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" class="form-control @error('whatsapp_number') is-invalid @enderror" value="{{ old('whatsapp_number') }}" placeholder="0300-1234567">
                @error('whatsapp_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Alternative Contact</label>
                <input type="text" name="mobile_secondary" class="form-control @error('mobile_secondary') is-invalid @enderror" value="{{ old('mobile_secondary') }}" placeholder="Landline or family mobile">
                @error('mobile_secondary')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Email Address (Optional)</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="customer@example.com">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Residential Profile & Financials -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-house-door me-2 text-primary"></i>2. Residential & Financial Profile</h5>
            <p class="text-muted small mb-0">Living situation and household income verification.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Present Physical Address <span class="text-danger">*</span></label>
                <textarea name="present_address" class="form-control @error('present_address') is-invalid @enderror" rows="2" placeholder="House #, Street, Mohallah / Colony, City" required>{{ old('present_address') }}</textarea>
                @error('present_address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Permanent Address (as per CNIC)</label>
                <textarea name="permanent_address" class="form-control @error('permanent_address') is-invalid @enderror" rows="2" placeholder="Permanent residence address stated on CNIC">{{ old('permanent_address') }}</textarea>
                @error('permanent_address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Residence Ownership <span class="text-danger">*</span></label>
                <select name="residence_type" class="form-select @error('residence_type') is-invalid @enderror" required>
                  <option value="owned" {{ old('residence_type', 'owned') === 'owned' ? 'selected' : '' }}>Owned Property</option>
                  <option value="rented" {{ old('residence_type') === 'rented' ? 'selected' : '' }}>Rented Property</option>
                  <option value="family" {{ old('residence_type') === 'family' ? 'selected' : '' }}>Family / Inherited</option>
                </select>
                @error('residence_type')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Years at Present Address</label>
                <input type="number" name="residence_tenure_years" class="form-control @error('residence_tenure_years') is-invalid @enderror" value="{{ old('residence_tenure_years', 3) }}" min="0" max="100">
                @error('residence_tenure_years')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Utility Bill Consumer Ref #</label>
                <input type="text" name="utility_bill_ref_number" class="form-control @error('utility_bill_ref_number') is-invalid @enderror" value="{{ old('utility_bill_ref_number') }}" placeholder="Electricity / Gas Consumer #">
                <small class="text-muted">Used for field address verification</small>
                @error('utility_bill_ref_number')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Monthly Household Income (PKR)</label>
                <div class="input-group" style="max-width: 320px;">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="0.01" name="monthly_household_income" class="form-control @error('monthly_household_income') is-invalid @enderror" value="{{ old('monthly_household_income') }}" placeholder="e.g. 75000">
                </div>
                <small class="text-muted">Used in Debt-to-Income (DTI) approval ratio calculations</small>
                @error('monthly_household_income')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Section 3: Primary Legal Guarantor -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="fw-bold mb-1"><i class="bi bi-shield-check me-2 text-primary"></i>3. Primary Legal Guarantor</h5>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Mandatory Requirement</span>
            </div>
            <p class="text-muted small mb-0">Legal guarantor assuming joint liability per BR-GUAR-01.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Guarantor Full Name <span class="text-danger">*</span></label>
                <input type="text" name="guarantor_name" class="form-control @error('guarantor_name') is-invalid @enderror" value="{{ old('guarantor_name') }}" placeholder="e.g. Tariq Mehmood" required>
                @error('guarantor_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Guarantor CNIC <span class="text-danger">*</span></label>
                <input type="text" name="guarantor_cnic" class="form-control @error('guarantor_cnic') is-invalid @enderror" value="{{ old('guarantor_cnic') }}" placeholder="35201-9876543-1" maxlength="20" required>
                <small class="text-muted">Must not match customer's own CNIC</small>
                @error('guarantor_cnic')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Relationship to Customer <span class="text-danger">*</span></label>
                <select name="guarantor_relationship" class="form-select @error('guarantor_relationship') is-invalid @enderror" required>
                  <option value="brother" {{ old('guarantor_relationship') === 'brother' ? 'selected' : '' }}>Brother</option>
                  <option value="father" {{ old('guarantor_relationship') === 'father' ? 'selected' : '' }}>Father</option>
                  <option value="uncle" {{ old('guarantor_relationship') === 'uncle' ? 'selected' : '' }}>Uncle</option>
                  <option value="colleague" {{ old('guarantor_relationship') === 'colleague' ? 'selected' : '' }}>Colleague</option>
                  <option value="friend" {{ old('guarantor_relationship') === 'friend' ? 'selected' : '' }}>Friend</option>
                  <option value="cousin" {{ old('guarantor_relationship') === 'cousin' ? 'selected' : '' }}>Cousin</option>
                  <option value="other" {{ old('guarantor_relationship') === 'other' ? 'selected' : '' }}>Other Relation</option>
                </select>
                @error('guarantor_relationship')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Guarantor Mobile <span class="text-danger">*</span></label>
                <input type="text" name="guarantor_mobile" class="form-control @error('guarantor_mobile') is-invalid @enderror" value="{{ old('guarantor_mobile') }}" placeholder="0301-7654321" required>
                @error('guarantor_mobile')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Occupation / Job Title</label>
                <input type="text" name="guarantor_occupation" class="form-control @error('guarantor_occupation') is-invalid @enderror" value="{{ old('guarantor_occupation') }}" placeholder="e.g. Govt Officer / Trader">
                @error('guarantor_occupation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Employer / Business Name</label>
                <input type="text" name="guarantor_employer_name" class="form-control @error('guarantor_employer_name') is-invalid @enderror" value="{{ old('guarantor_employer_name') }}" placeholder="Department or Company Name">
                @error('guarantor_employer_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Estimated Monthly Income (PKR)</label>
                <div class="input-group">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="0.01" name="guarantor_monthly_income" class="form-control @error('guarantor_monthly_income') is-invalid @enderror" value="{{ old('guarantor_monthly_income') }}" placeholder="e.g. 95000">
                </div>
                @error('guarantor_monthly_income')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Physical Residence / Workplace Address <span class="text-danger">*</span></label>
                <textarea name="guarantor_address" class="form-control @error('guarantor_address') is-invalid @enderror" rows="2" placeholder="Full residential or workplace address" required>{{ old('guarantor_address') }}</textarea>
                @error('guarantor_address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Section 4: Personal Reference (Optional) -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>4. Personal Reference (Acquaintance)</h5>
            <p class="text-muted small mb-0">Neighbor, acquaintance, or colleague contact for verification.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Reference Full Name</label>
                <input type="text" name="ref_name" class="form-control @error('ref_name') is-invalid @enderror" value="{{ old('ref_name') }}" placeholder="e.g. Imran Ali">
                @error('ref_name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Relationship</label>
                <input type="text" name="ref_relationship" class="form-control @error('ref_relationship') is-invalid @enderror" value="{{ old('ref_relationship') }}" placeholder="e.g. Neighbor / Shopkeeper">
                @error('ref_relationship')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Mobile Number</label>
                <input type="text" name="ref_mobile" class="form-control @error('ref_mobile') is-invalid @enderror" value="{{ old('ref_mobile') }}" placeholder="0302-8889900">
                @error('ref_mobile')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-12">
                <label class="form-label fw-semibold">Residence Location</label>
                <input type="text" name="ref_address" class="form-control @error('ref_address') is-invalid @enderror" value="{{ old('ref_address') }}" placeholder="Street / Area name">
                @error('ref_address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Verification & Policy Information -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-info-circle me-2 text-primary"></i>Onboarding Workflow</h5>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="alert alert-info border-0 d-flex align-items-start small py-2 px-3 mb-3">
              <i class="bi bi-shield-lock-fill fs-5 me-2 text-info"></i>
              <div>
                <strong>Automatic Credit Profile:</strong> A credit score of <strong>50</strong> and initial ceiling of <strong>Rs. 150,000</strong> will be initialized upon registration.
              </div>
            </div>

            <div class="alert alert-warning border-0 d-flex align-items-start small py-2 px-3 mb-4">
              <i class="bi bi-geo-alt-fill fs-5 me-2 text-warning"></i>
              <div>
                <strong>Verification State:</strong> Customer starts as <code>pending_verification</code> until a Field Officer logs physical residence confirmation.
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-check2-circle me-1"></i>Register Customer & Open Dossier
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-light w-100 mt-2">
              Cancel
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
</x-app-layout>
