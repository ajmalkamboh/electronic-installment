<x-app-layout title="Create Installment Plan">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('plans.index') }}">Installment Plans</a></li>
        <li class="breadcrumb-item active" aria-current="page">New Template</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Create Financing Plan Template</h1>
        <p class="text-muted mb-0">Define tenure months, markup calculation model, down payment rules, and repayment frequencies.</p>
      </div>
      <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Plans
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('plans.store') }}">
        @csrf

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="name" class="form-label fw-semibold">Plan Template Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                   placeholder="e.g. 12-Month Showroom Standard, 6-Month Quick Financed" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label for="tenure_months" class="form-label fw-semibold">Tenure (Months) <span class="text-danger">*</span></label>
            <select name="tenure_months" id="tenure_months" class="form-select @error('tenure_months') is-invalid @enderror" required>
              @foreach([3, 6, 9, 12, 18, 24, 36] as $months)
                <option value="{{ $months }}" {{ old('tenure_months', 12) == $months ? 'selected' : '' }}>
                  {{ $months }} Months
                </option>
              @endforeach
            </select>
            @error('tenure_months')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label for="installment_frequency" class="form-label fw-semibold">Repayment Frequency <span class="text-danger">*</span></label>
            <select name="installment_frequency" id="installment_frequency" class="form-select @error('installment_frequency') is-invalid @enderror" required>
              <option value="monthly" {{ old('installment_frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
              <option value="bi_weekly" {{ old('installment_frequency') === 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly (Fortnightly)</option>
              <option value="weekly" {{ old('installment_frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
            </select>
            @error('installment_frequency')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="markup_calculation_model" class="form-label fw-semibold">Markup Calculation Model <span class="text-danger">*</span></label>
            <select name="markup_calculation_model" id="markup_calculation_model" class="form-select @error('markup_calculation_model') is-invalid @enderror" required>
              <option value="flat_percentage" {{ old('markup_calculation_model', 'flat_percentage') === 'flat_percentage' ? 'selected' : '' }}>
                Flat Percentage Markup (Retail Standard)
              </option>
              <option value="reducing_balance" {{ old('markup_calculation_model') === 'reducing_balance' ? 'selected' : '' }}>
                Reducing Balance Amortized (Banking Model)
              </option>
              <option value="fixed_amount" {{ old('markup_calculation_model') === 'fixed_amount' ? 'selected' : '' }}>
                Fixed Markup Amount (Flat Fee)
              </option>
            </select>
            @error('markup_calculation_model')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="default_markup_rate_pct" class="form-label fw-semibold">Default Annual Markup % <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.5" min="0" max="200"
                     name="default_markup_rate_pct" id="default_markup_rate_pct"
                     class="form-control @error('default_markup_rate_pct') is-invalid @enderror"
                     value="{{ old('default_markup_rate_pct', 25.00) }}" required>
              <span class="input-group-text bg-light">%</span>
            </div>
            @error('default_markup_rate_pct')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Standard rate applied over tenure duration</small>
          </div>

          <div class="col-md-4">
            <label for="min_down_payment_pct" class="form-label fw-semibold">Minimum Down Payment % <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.5" min="0" max="100"
                     name="min_down_payment_pct" id="min_down_payment_pct"
                     class="form-control @error('min_down_payment_pct') is-invalid @enderror"
                     value="{{ old('min_down_payment_pct', 20.00) }}" required>
              <span class="input-group-text bg-light">%</span>
            </div>
            @error('min_down_payment_pct')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Mandatory cash upfront payment</small>
          </div>

          <div class="col-md-6" id="fixedAmountGroup">
            <label for="fixed_markup_amount" class="form-label fw-semibold">Fixed Markup Amount (Optional)</label>
            <div class="input-group">
              <span class="input-group-text bg-light">Rs.</span>
              <input type="number" step="100" min="0" max="1000000"
                     name="fixed_markup_amount" id="fixed_markup_amount"
                     class="form-control @error('fixed_markup_amount') is-invalid @enderror"
                     value="{{ old('fixed_markup_amount') }}" placeholder="Applicable if using Fixed Amount model">
            </div>
          </div>

          <div class="col-12">
            <label for="description" class="form-label fw-semibold">Plan Description / Promotion Notes</label>
            <textarea name="description" id="description" rows="3" class="form-control"
                      placeholder="Customer terms, eligibility criteria, promotional benefits...">{{ old('description') }}</textarea>
          </div>

          <div class="col-12">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
              <label class="form-check-label fw-semibold" for="is_active">Active Plan (Available in Showroom & Quotation Simulator)</label>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="{{ route('plans.index') }}" class="btn btn-light px-4">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">Save Plan Template</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
