<x-app-layout title="Edit Customer: {{ $customer->full_name }}">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Customer Dossier
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Edit Customer Profile</h1>
      <p class="text-muted mb-0">Update biographical records, address, and verification details.</p>
    </div>
  </div>

  <form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-1"><i class="bi bi-person-vcard me-2 text-primary"></i>Personal & National Identity</h5>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $customer->full_name) }}" required>
            @error('full_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Father / Husband Name</label>
            <input type="text" name="father_or_husband_name" class="form-control @error('father_or_husband_name') is-invalid @enderror" value="{{ old('father_or_husband_name', $customer->father_or_husband_name) }}">
            @error('father_or_husband_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Pakistani CNIC Number <span class="text-danger">*</span></label>
            <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror" value="{{ old('cnic', $customer->cnic) }}" required maxlength="20">
            @error('cnic')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
              <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Male</option>
              <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Female</option>
              <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Primary Mobile <span class="text-danger">*</span></label>
            <input type="text" name="mobile_primary" class="form-control @error('mobile_primary') is-invalid @enderror" value="{{ old('mobile_primary', $customer->mobile_primary) }}" required>
            @error('mobile_primary')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">WhatsApp Number</label>
            <input type="text" name="whatsapp_number" class="form-control @error('whatsapp_number') is-invalid @enderror" value="{{ old('whatsapp_number', $customer->whatsapp_number) }}">
            @error('whatsapp_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Alternative Contact</label>
            <input type="text" name="mobile_secondary" class="form-control @error('mobile_secondary') is-invalid @enderror" value="{{ old('mobile_secondary', $customer->mobile_secondary) }}">
            @error('mobile_secondary')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Email Address</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Verification Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="pending_verification" {{ old('status', $customer->status) === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
              <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active (Approved)</option>
              <option value="restricted" {{ old('status', $customer->status) === 'restricted' ? 'selected' : '' }}>Restricted</option>
              <option value="blacklisted" {{ old('status', $customer->status) === 'blacklisted' ? 'selected' : '' }}>Blacklisted Defaulter</option>
            </select>
            @error('status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-1"><i class="bi bi-house me-2 text-primary"></i>Residential & Financial Information</h5>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Present Address <span class="text-danger">*</span></label>
            <textarea name="present_address" class="form-control @error('present_address') is-invalid @enderror" rows="2" required>{{ old('present_address', $customer->present_address) }}</textarea>
            @error('present_address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Permanent Address (as per CNIC)</label>
            <textarea name="permanent_address" class="form-control @error('permanent_address') is-invalid @enderror" rows="2">{{ old('permanent_address', $customer->permanent_address) }}</textarea>
            @error('permanent_address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Residence Ownership <span class="text-danger">*</span></label>
            <select name="residence_type" class="form-select @error('residence_type') is-invalid @enderror" required>
              <option value="owned" {{ old('residence_type', $customer->residence_type) === 'owned' ? 'selected' : '' }}>Owned Property</option>
              <option value="rented" {{ old('residence_type', $customer->residence_type) === 'rented' ? 'selected' : '' }}>Rented Property</option>
              <option value="family" {{ old('residence_type', $customer->residence_type) === 'family' ? 'selected' : '' }}>Family / Inherited</option>
            </select>
            @error('residence_type')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Years at Present Address</label>
            <input type="number" name="residence_tenure_years" class="form-control @error('residence_tenure_years') is-invalid @enderror" value="{{ old('residence_tenure_years', $customer->residence_tenure_years) }}">
            @error('residence_tenure_years')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Utility Bill Consumer Ref #</label>
            <input type="text" name="utility_bill_ref_number" class="form-control @error('utility_bill_ref_number') is-invalid @enderror" value="{{ old('utility_bill_ref_number', $customer->utility_bill_ref_number) }}">
            @error('utility_bill_ref_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-12">
            <label class="form-label fw-semibold">Monthly Household Income (PKR)</label>
            <div class="input-group" style="max-width: 320px;">
              <span class="input-group-text">Rs.</span>
              <input type="number" step="0.01" name="monthly_household_income" class="form-control @error('monthly_household_income') is-invalid @enderror" value="{{ old('monthly_household_income', $customer->monthly_household_income) }}">
            </div>
            @error('monthly_household_income')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-5">
      <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
        <i class="bi bi-save me-1"></i>Save Changes
      </button>
      <a href="{{ route('customers.show', $customer) }}" class="btn btn-light px-4 py-2">
        Cancel
      </a>
    </div>
  </form>
</x-app-layout>
