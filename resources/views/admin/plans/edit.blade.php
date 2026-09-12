<x-app-layout title="Edit {{ $plan->name }} Plan - Super Admin">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
          <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Edit SaaS Plan: {{ $plan->name }}</h1>
      </div>
      <p class="text-muted mb-0">Update pricing &bull; Modify quota limits &bull; Toggle feature flags</p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-9">
      <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1">Plan Identity &amp; Pricing</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $plan->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Monthly Price (PKR) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price_monthly" class="form-control @error('price_monthly') is-invalid @enderror" value="{{ old('price_monthly', $plan->price_monthly) }}" required>
                @error('price_monthly') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Yearly Price (PKR) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="price_yearly" class="form-control @error('price_yearly') is-invalid @enderror" value="{{ old('price_yearly', $plan->price_yearly) }}" required>
                @error('price_yearly') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $plan->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- Quota Limits -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1">Resource Quota Enforcements</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fw-semibold">Max Users <span class="text-danger">*</span></label>
                <input type="number" name="max_users" class="form-control @error('max_users') is-invalid @enderror" value="{{ old('max_users', $plan->max_users) }}" min="1" required>
                @error('max_users') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Max Branches <span class="text-danger">*</span></label>
                <input type="number" name="max_branches" class="form-control @error('max_branches') is-invalid @enderror" value="{{ old('max_branches', $plan->max_branches) }}" min="1" required>
                @error('max_branches') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Max Active Contracts <span class="text-danger">*</span></label>
                <input type="number" name="max_active_agreements" class="form-control @error('max_active_agreements') is-invalid @enderror" value="{{ old('max_active_agreements', $plan->max_active_agreements) }}" min="1" required>
                @error('max_active_agreements') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-3">
                <label class="form-label fw-semibold">Max Monthly Receipts <span class="text-danger">*</span></label>
                <input type="number" name="max_monthly_transactions" class="form-control @error('max_monthly_transactions') is-invalid @enderror" value="{{ old('max_monthly_transactions', $plan->max_monthly_transactions) }}" min="1" required>
                @error('max_monthly_transactions') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Trial Days</label>
                <input type="number" name="trial_days" class="form-control @error('trial_days') is-invalid @enderror" value="{{ old('trial_days', $plan->trial_days) }}" min="0" max="90" required>
                @error('trial_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold">Display Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $plan->sort_order) }}">
              </div>

              <div class="col-md-4 d-flex align-items-center">
                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="is_active">
                    Enable for new subscriptions
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Feature Flags -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-3 pb-0">
            <h5 class="fw-bold mb-1">Feature Entitlements</h5>
          </div>
          <div class="card-body">
            <div class="row g-2">
              @php
                $activeFeatures = old('features', $plan->features ?? []);
              @endphp
              @foreach($features as $key => $label)
                <div class="col-md-6">
                  <div class="form-check p-2 border rounded">
                    <input class="form-check-input ms-0 me-2" type="checkbox" name="features[]" value="{{ $key }}" id="feat_{{ $key }}" {{ in_array($key, $activeFeatures) || in_array('*', $activeFeatures) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="feat_{{ $key }}">
                      {{ $label }}
                    </label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
          <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Update SaaS Plan
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
