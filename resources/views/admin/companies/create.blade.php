<x-app-layout title="Onboard New Tenant Company - Super Admin">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
          <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Onboard New Retail Tenant</h1>
      </div>
      <p class="text-muted mb-0">Register multi-tenant client organization &bull; Setup HQ showroom &bull; Authorize root company admin</p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-9">
      <form method="POST" action="{{ route('admin.companies.store') }}">
        @csrf

        <!-- 1. Company Information -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i>Company Profile</h5>
            <p class="text-muted small mb-0">Corporate identity and legal entity registration</p>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Commercial Trade Name <span class="text-danger">*</span></label>
                <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="e.g. Metro Electronics" required>
                @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Registered Legal Name</label>
                <input type="text" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name') }}" placeholder="e.g. Metro Electronics Pvt Ltd">
                @error('legal_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Corporate Contact Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="info@metroelectronics.pk" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Primary Contact Phone</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+92 300 1234567">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Headquarters City <span class="text-danger">*</span></label>
                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" placeholder="e.g. Lahore, Karachi, Rawalpindi" required>
                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Principal Office Address</label>
                <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" placeholder="Commercial Market, Main Road">
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- 2. SaaS Subscription Plan Tier -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1"><i class="bi bi-tag me-2 text-success"></i>SaaS Subscription &amp; Tier</h5>
            <p class="text-muted small mb-0">Select plan quota limits and billing status</p>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold">Subscription Plan <span class="text-danger">*</span></label>
                <select name="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
                  <option value="">-- Choose SaaS Tier --</option>
                  @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                      {{ $plan->name }} &mdash; PKR {{ number_format($plan->price_monthly) }}/mo &bull; Max {{ $plan->max_users }} Staff &bull; {{ $plan->max_branches }} Branches
                    </option>
                  @endforeach
                </select>
                @error('plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4 d-flex align-items-center">
                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="is_trial" value="1" id="is_trial" {{ old('is_trial', 1) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="is_trial">
                    Start with 14-Day Free Trial
                  </label>
                  <small class="d-block text-muted">No upfront fee required</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Primary Root Administrator Account -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1"><i class="bi bi-person-badge me-2 text-info"></i>Tenant Administrator Account</h5>
            <p class="text-muted small mb-0">Initial executive account for the tenant owner</p>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-semibold">Admin Full Name <span class="text-danger">*</span></label>
                <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" placeholder="e.g. Muhammad Usman" required>
                @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Login Email Address <span class="text-danger">*</span></label>
                <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" placeholder="admin@metroelectronics.pk" required>
                @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Temporary Password <span class="text-danger">*</span></label>
                <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" placeholder="Min 8 characters" required>
                @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
          <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Onboard &amp; Provision Tenant
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
