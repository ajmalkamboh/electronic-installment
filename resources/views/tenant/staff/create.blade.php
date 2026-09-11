<x-app-layout title="Register Staff Member">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('staff.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Staff Directory
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Register New Staff Member</h1>
      <p class="text-muted mb-0">
        Create an employee profile, assign showroom branch, and set up role-based credentials.
      </p>
    </div>
  </div>

  <form method="POST" action="{{ route('staff.store') }}">
    @csrf

    <div class="row g-4">
      <!-- Left Column: Personal & Employment Details -->
      <div class="col-lg-8">
        <!-- Section 1: Personal & Contact -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-person me-2 text-primary"></i>Personal & Contact Details</h5>
            <p class="text-muted small mb-0">Basic identification information matching national records.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Muhammad Kashif" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Corporate Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="kashif@company.pk" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pakistani CNIC Number</label>
                <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror" value="{{ old('cnic') }}" placeholder="35201-1234567-1" maxlength="20">
                <small class="text-muted">Standard 13-digit Pakistani national identity card format</small>
                @error('cnic')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Mobile Contact Number</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+92 300 1234567">
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2: Employment & Branch Assignment -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-briefcase me-2 text-primary"></i>Employment & Branch Assignment</h5>
            <p class="text-muted small mb-0">Showroom location, employee code, and organizational position.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Employee Code</label>
                <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror" value="{{ old('employee_code') }}" placeholder="e.g. EMP-004">
                <small class="text-muted">Unique identifier within company</small>
                @error('employee_code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Primary Branch Showroom</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                  <option value="">Headquarters / All Branches</option>
                  @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                      {{ $branch->name }} ({{ $branch->code }})
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Leave empty for multi-branch corporate staff</small>
                @error('branch_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Job Designation</label>
                <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation') }}" placeholder="e.g. Credit Officer">
                @error('designation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Joining Date</label>
                <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', date('Y-m-d')) }}">
                @error('joining_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Monthly Salary (PKR)</label>
                <div class="input-group">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="0.01" name="salary" class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary') }}" placeholder="0.00">
                </div>
                @error('salary')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Security & Role Assignment -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Security & Role</h5>
            <p class="text-muted small mb-0">Role-based access level and account credentials.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="mb-3">
              <label class="form-label fw-semibold">System Role <span class="text-danger">*</span></label>
              <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                <option value="">Select Role...</option>
                @foreach($roles as $r)
                  <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>
                    {{ $r->display_name }} {{ $r->is_system ? '(Predefined)' : '(Custom)' }}
                  </option>
                @endforeach
              </select>
              @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Initial Account Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Authorized)</option>
                <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended (Blocked)</option>
                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Offboarded)</option>
              </select>
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <hr class="my-3">

            <div class="mb-3">
              <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Min. 8 characters" required>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-check2-circle me-1"></i>Register Employee
            </button>
            <a href="{{ route('staff.index') }}" class="btn btn-light w-100 mt-2">
              Cancel
            </a>
          </div>
        </div>
      </div>
    </div>
  </form>
</x-app-layout>
