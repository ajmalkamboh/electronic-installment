<x-app-layout title="Add Branch Showroom">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Register New Showroom</h1>
      <p class="text-muted mb-0">Establish an operational retail showroom or warehouse location for <strong>{{ $company->name }}</strong>.</p>
    </div>
    <div>
      <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Showrooms
      </a>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1">Showroom Details</h5>
          <p class="text-muted small mb-0">Branch codes must be unique across all showrooms in your company.</p>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('branches.store') }}">
            @csrf

            <div class="row g-3">
              <!-- Branch Name -->
              <div class="col-12 col-md-8">
                <label for="name" class="form-label fw-semibold">Showroom Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Rawalpindi Saddar Branch" required>
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Branch Code -->
              <div class="col-12 col-md-4">
                <label for="code" class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('code') is-invalid @enderror text-uppercase" id="code" name="code" value="{{ old('code') }}" placeholder="e.g. RWP-01" required>
                <small class="text-muted">Unique identifier</small>
                @error('code')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- City -->
              <div class="col-12 col-md-6">
                <label for="city" class="form-label fw-semibold">City</label>
                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}" placeholder="e.g. Rawalpindi">
                @error('city')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Phone -->
              <div class="col-12 col-md-6">
                <label for="phone" class="form-label fw-semibold">Landline / Contact Phone</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. +92 51 5550001">
                @error('phone')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Email -->
              <div class="col-12">
                <label for="email" class="form-label fw-semibold">Showroom Official Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="e.g. rwp@premierelectronics.pk">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Physical Address -->
              <div class="col-12">
                <label for="address" class="form-label fw-semibold">Physical Street Address</label>
                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3" placeholder="Full street address, shop numbers, market name...">{{ old('address') }}</textarea>
                @error('address')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <!-- Main Headquarters Toggle -->
              <div class="col-12">
                <div class="form-check p-3 bg-light rounded border">
                  <input class="form-check-input" type="checkbox" id="is_main" name="is_main" value="1" {{ old('is_main') ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="is_main">
                    Designate as Primary Headquarters Showroom
                  </label>
                  <small class="text-muted d-block mt-1">If enabled, this will become the default operational location for new administrators.</small>
                </div>
              </div>

              <div class="col-12 pt-3 border-top d-flex justify-content-end gap-2">
                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                  <i class="bi bi-check2-circle me-1"></i>Create Showroom
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
