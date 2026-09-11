<x-app-layout title="Company Profile & Settings">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Company Profile & Configuration</h1>
      <p class="text-muted mb-0">Manage legal registration details, billing currency, and thermal receipt notes for <strong>{{ $company->name }}</strong>.</p>
    </div>
    <div class="d-flex gap-2">
      <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
        <i class="bi bi-shield-check me-1"></i>Tenant ULID: <code>{{ $company->ulid }}</code>
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- Main Settings Form -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <ul class="nav nav-tabs card-header-tabs" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-semibold" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                <i class="bi bi-building me-1"></i>Corporate Profile
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-semibold" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receipts" type="button" role="tab">
                <i class="bi bi-receipt me-1"></i>Receipt & Print Settings
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body p-4">
          <form method="POST" action="{{ route('company.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="tab-content" id="settingsTabContent">
              <!-- Tab 1: Corporate Profile -->
              <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label for="name" class="form-label fw-semibold">Commercial Brand Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $company->name) }}" required>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="legal_name" class="form-label fw-semibold">Registered Legal Entity Name</label>
                    <input type="text" class="form-control @error('legal_name') is-invalid @enderror" id="legal_name" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" placeholder="e.g. Premier Electronics SMC-Pvt Ltd">
                    @error('legal_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="ntn_strn" class="form-label fw-semibold">Tax Registration (NTN / STRN)</label>
                    <input type="text" class="form-control @error('ntn_strn') is-invalid @enderror" id="ntn_strn" name="ntn_strn" value="{{ old('ntn_strn', $company->ntn_strn) }}" placeholder="e.g. 7382910-4">
                    @error('ntn_strn')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="currency" class="form-label fw-semibold">Operating Currency <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency" value="{{ old('currency', $company->currency) }}" required>
                    @error('currency')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="phone" class="form-label fw-semibold">Primary Business Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $company->phone) }}" placeholder="+92 42 37210000">
                    @error('phone')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="email" class="form-label fw-semibold">Official Contact Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $company->email) }}" placeholder="info@company.pk">
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-4">
                    <label for="city" class="form-label fw-semibold">Headquarters City</label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $company->city) }}" placeholder="Lahore">
                    @error('city')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12 col-md-8">
                    <label for="address" class="form-label fw-semibold">Corporate Office Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $company->address) }}" placeholder="Street address, commercial zone...">
                    @error('address')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <!-- Tab 2: Receipts & Printing -->
              <div class="tab-pane fade" id="receipts" role="tabpanel">
                <div class="row g-3">
                  <div class="col-12">
                    <label for="receipt_header" class="form-label fw-semibold">Receipt Header Greeting / Subtitle</label>
                    <input type="text" class="form-control @error('receipt_header') is-invalid @enderror" id="receipt_header" name="receipt_header" value="{{ old('receipt_header', $company->receipt_header) }}" placeholder="e.g. Pakistan's Leading Electronics Installment Retailer">
                    <small class="text-muted">Printed at the top of POS thermal receipts under the company name.</small>
                    @error('receipt_header')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12">
                    <label for="receipt_footer" class="form-label fw-semibold">Receipt Footer Message</label>
                    <textarea class="form-control @error('receipt_footer') is-invalid @enderror" id="receipt_footer" name="receipt_footer" rows="2" placeholder="e.g. Please pay installments by the 5th of every month to avoid late fees. Thank you for your business.">{{ old('receipt_footer', $company->receipt_footer) }}</textarea>
                    <small class="text-muted">Printed at the bottom of thermal receipts.</small>
                    @error('receipt_footer')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-12">
                    <label for="terms_conditions" class="form-label fw-semibold">Standard Contract Terms & Conditions</label>
                    <textarea class="form-control @error('terms_conditions') is-invalid @enderror" id="terms_conditions" name="terms_conditions" rows="4" placeholder="Standard legal terms printed on legal agreement contracts...">{{ old('terms_conditions', $company->terms_conditions) }}</textarea>
                    @error('terms_conditions')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-4 mt-4 border-top d-flex justify-content-end">
              <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-check2-circle me-1"></i>Save Company Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Sidebar Info & SaaS Usage Card -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1">SaaS Subscription & Capacity</h5>
          <span class="badge bg-primary">Phase 03 Active</span>
        </div>
        <div class="card-body p-4">
          <div class="p-3 bg-light rounded mb-3 border">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small text-uppercase fw-semibold">Current Plan</span>
              <span class="badge bg-success">Enterprise Trial</span>
            </div>
            <h5 class="fw-bold mb-0">Electronic Retailer Plan</h5>
          </div>

          <ul class="list-group list-group-flush small">
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Registered Showrooms</span>
              <strong>{{ $stats['branches_count'] }} Outlets</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Active Staff Accounts</span>
              <strong>{{ $stats['users_count'] }} Accounts</strong>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Multi-Branch Context</span>
              <span class="badge bg-success-subtle text-success">Enabled</span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0 py-2">
              <span class="text-muted">Tenant Isolation</span>
              <span class="badge bg-primary-subtle text-primary">Strict Scoped</span>
            </li>
          </ul>

          <div class="mt-3 pt-3 border-top text-center">
            <a href="{{ route('branches.index') }}" class="btn btn-outline-primary btn-sm w-100">
              <i class="bi bi-shop me-1"></i>Manage Branch Showrooms
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
