<x-app-layout title="Edit Staff Member">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('staff.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Staff Directory
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Edit Staff Profile: {{ $staff->name }}</h1>
      <p class="text-muted mb-0">
        Update personnel details, reassign branch showroom, or modify access permissions.
      </p>
    </div>
  </div>

  <form method="POST" action="{{ route('staff.update', $staff) }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
      <!-- Left Column: Personal & Employment Details -->
      <div class="col-lg-8">
        <!-- Section 1: Personal & Contact -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
            <h5 class="fw-bold mb-1"><i class="bi bi-person me-2 text-primary"></i>Personal & Contact Details</h5>
            <p class="text-muted small mb-0">Identification and contact information.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $staff->name) }}" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Corporate Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}" required>
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pakistani CNIC Number</label>
                <input type="text" name="cnic" class="form-control @error('cnic') is-invalid @enderror" value="{{ old('cnic', $staff->cnic) }}" placeholder="35201-1234567-1" maxlength="20">
                @error('cnic')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Mobile Contact Number</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $staff->phone) }}" placeholder="+92 300 1234567">
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
            <h5 class="fw-bold mb-1"><i class="bi bi-briefcase me-2 text-primary"></i>Employment & Showroom Transfer</h5>
            <p class="text-muted small mb-0">Branch assignment and compensation details.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Employee Code</label>
                <input type="text" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror" value="{{ old('employee_code', $staff->employee_code) }}">
                @error('employee_code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Primary Branch Showroom</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                  <option value="">Headquarters / All Branches</option>
                  @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id', $staff->branch_id) == $branch->id ? 'selected' : '' }}>
                      {{ $branch->name }} ({{ $branch->code }})
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Change branch assignment to execute an internal employee transfer</small>
                @error('branch_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Job Designation</label>
                <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $staff->designation) }}">
                @error('designation')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Joining Date</label>
                <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', $staff->joining_date?->format('Y-m-d')) }}">
                @error('joining_date')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Monthly Salary (PKR)</label>
                <div class="input-group">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="0.01" name="salary" class="form-control @error('salary') is-invalid @enderror" value="{{ old('salary', $staff->salary) }}">
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
            <p class="text-muted small mb-0">Role-based authorization and status.</p>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="mb-3">
              <label class="form-label fw-semibold">Assigned Role <span class="text-danger">*</span></label>
              <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                @foreach($roles as $r)
                  <option value="{{ $r->id }}" {{ old('role_id', $staff->role_id) == $r->id ? 'selected' : '' }}>
                    {{ $r->display_name }} {{ $r->is_system ? '(Predefined)' : '(Custom)' }}
                  </option>
                @endforeach
              </select>
              @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
              <select name="status" class="form-select @error('status') is-invalid @enderror" required {{ $staff->id === auth()->id() ? 'disabled' : '' }}>
                <option value="active" {{ old('status', $staff->status) === 'active' ? 'selected' : '' }}>Active (Authorized)</option>
                <option value="suspended" {{ old('status', $staff->status) === 'suspended' ? 'selected' : '' }}>Suspended (Blocked)</option>
                <option value="inactive" {{ old('status', $staff->status) === 'inactive' ? 'selected' : '' }}>Inactive (Offboarded)</option>
              </select>
              @if($staff->id === auth()->id())
                <input type="hidden" name="status" value="active">
                <small class="text-muted">You cannot modify your own administrative account status.</small>
              @endif
              @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <hr class="my-3">

            <div class="mb-3">
              <label class="form-label fw-semibold">Change Password</label>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave empty to keep current">
              <small class="text-muted">Only fill if resetting password</small>
              @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Confirm New Password</label>
              <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password">
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i class="bi bi-save me-1"></i>Save Changes
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
