<x-app-layout title="Draft Installment Agreement">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Draft Installment Sales Agreement</h1>
      <p class="text-muted mb-0">Bind customer, showroom merchandise (IMEI/Serial), pricing plan, and legal guarantors into a legally binding contract.</p>
    </div>
    <div>
      <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Agreements
      </a>
    </div>
  </div>

  @if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('agreements.store') }}" method="POST" id="agreementForm">
    @csrf
    <input type="hidden" name="branch_id" value="{{ $currentBranch->id }}">

    <div class="row g-4">
      <!-- Left Column: Agreement Specifications -->
      <div class="col-lg-8">
        <!-- 1. Customer Particulars -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-person-badge text-primary me-2"></i>1. Customer & Showroom Branch
            </h5>
            <p class="text-muted small mb-0">Select the registered buyer and verify showroom origin.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-7">
                <label for="customer_id" class="form-label fw-semibold text-dark">Registered Customer <span class="text-danger">*</span></label>
                <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                  <option value="">-- Select Customer by Name or CNIC --</option>
                  @foreach($customers as $c)
                    <option value="{{ $c->id }}"
                            {{ (old('customer_id', $selectedCustomer?->id) == $c->id) ? 'selected' : '' }}>
                      {{ $c->full_name }} (CNIC: {{ $c->cnic }}) &bull; {{ $c->mobile_primary }}
                    </option>
                  @endforeach
                </select>
                @error('customer_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold text-dark">Selling Showroom Branch</label>
                <input type="text" class="form-control bg-light" value="{{ $currentBranch->name }} ({{ $currentBranch->code }})" readonly>
                <small class="text-muted">Origin showroom issuing merchandise and handling initial down payment.</small>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Merchandise & Hardware Allocation -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-phone text-primary me-2"></i>2. Merchandise & Serial/IMEI Allocation
            </h5>
            <p class="text-muted small mb-0">Choose product model and lock specific showroom serial unit.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="product_id" class="form-label fw-semibold text-dark">Product Catalog Model <span class="text-danger">*</span></label>
                <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                  <option value="">-- Select Merchandise Model --</option>
                  @foreach($products as $p)
                    <option value="{{ $p->id }}"
                            data-price="{{ $p->base_cash_price }}"
                            data-mindp="{{ $p->min_down_payment_pct }}"
                            data-serialized="{{ $p->is_serialized ? '1' : '0' }}"
                            {{ old('product_id') == $p->id ? 'selected' : '' }}>
                      {{ $p->brand }} {{ $p->model_name }} &bull; Rs. {{ number_format($p->base_cash_price) }}
                    </option>
                  @endforeach
                </select>
                @error('product_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="serialized_item_id" class="form-label fw-semibold text-dark">Showroom Physical Serial / IMEI Unit</label>
                <select name="serialized_item_id" id="serialized_item_id" class="form-select @error('serialized_item_id') is-invalid @enderror">
                  <option value="">-- Select Specific In-Stock Hardware --</option>
                  @foreach($availableItems as $item)
                    <option value="{{ $item->id }}" data-product="{{ $item->product_id }}"
                            {{ old('serialized_item_id') == $item->id ? 'selected' : '' }}>
                      {{ $item->product->brand }} {{ $item->product->model_name }} &bull; {{ $item->identifier_label }}
                    </option>
                  @endforeach
                </select>
                <small class="text-muted">Unit status will be reserved immediately upon drafting.</small>
                @error('serialized_item_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Financing Plan & Down Payment Terms -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-credit-card-2-front text-primary me-2"></i>3. Financing Plan & Payment Terms
            </h5>
            <p class="text-muted small mb-0">Select duration tenure and define initial cash down payment.</p>
          </div>
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="installment_plan_id" class="form-label fw-semibold text-dark">Installment Financing Plan <span class="text-danger">*</span></label>
                <select name="installment_plan_id" id="installment_plan_id" class="form-select @error('installment_plan_id') is-invalid @enderror" required>
                  <option value="">-- Select Installment Plan --</option>
                  @foreach($plans as $plan)
                    <option value="{{ $plan->id }}"
                            data-months="{{ $plan->tenure_months }}"
                            data-rate="{{ $plan->default_markup_rate_pct }}"
                            data-mindp="{{ $plan->min_down_payment_pct }}"
                            data-model="{{ $plan->markup_calculation_model }}"
                            {{ old('installment_plan_id') == $plan->id ? 'selected' : '' }}>
                      {{ $plan->name }} ({{ $plan->tenure_months }} Months @ {{ number_format($plan->default_markup_rate_pct, 1) }}%)
                    </option>
                  @endforeach
                </select>
                @error('installment_plan_id')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="down_payment" class="form-label fw-semibold text-dark">Agreed Down Payment (PKR)</label>
                <div class="input-group">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" name="down_payment" id="down_payment"
                         class="form-control @error('down_payment') is-invalid @enderror"
                         value="{{ old('down_payment') }}" placeholder="Leave blank for policy default">
                </div>
                <small class="text-muted" id="downPaymentHelp">Calculated automatically based on minimum down payment policy.</small>
                @error('down_payment')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="col-md-6">
                <label for="start_date" class="form-label fw-semibold text-dark">Agreement Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control"
                       value="{{ old('start_date', date('Y-m-d')) }}">
              </div>

              <div class="col-md-6">
                <label for="first_due_date" class="form-label fw-semibold text-dark">First Installment Due Date</label>
                <input type="date" name="first_due_date" id="first_due_date" class="form-control"
                       value="{{ old('first_due_date', date('Y-m-d', strtotime('+1 month'))) }}">
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Legal Guarantors -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h5 class="fw-bold mb-1 text-dark">
              <i class="bi bi-shield-check text-primary me-2"></i>4. Legal Guarantors
            </h5>
            <p class="text-muted small mb-0">Select verified guarantors to be bound to this agreement.</p>
          </div>
          <div class="card-body p-4">
            <div id="guarantorsContainer">
              @if($selectedCustomer && $selectedCustomer->guarantors->isNotEmpty())
                <div class="row g-2">
                  @foreach($selectedCustomer->guarantors as $index => $g)
                    <div class="col-md-6">
                      <div class="form-check card p-3 border h-100">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="guarantor_ids[]"
                               value="{{ $g->id }}" id="guar_{{ $g->id }}"
                               {{ $index === 0 ? 'checked' : '' }}>
                        <label class="form-check-label ms-1" for="guar_{{ $g->id }}">
                          <strong class="d-block text-dark">{{ $g->full_name }} ({{ $g->relationship }})</strong>
                          <small class="text-muted d-block">CNIC: {{ $g->cnic }}</small>
                          <small class="text-muted d-block">Phone: {{ $g->mobile }}</small>
                        </label>
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <p class="text-muted small mb-0">
                  <i class="bi bi-info-circle me-1"></i>Select a customer above with registered guarantors, or add guarantors from the customer dossier after drafting.
                </p>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Live Quotation Breakdown & Submit -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm position-sticky" style="top: 80px;">
          <div class="card-header bg-primary text-white py-3 px-4">
            <h5 class="fw-bold mb-0">
              <i class="bi bi-receipt-cutoff me-2"></i>Contract Summary
            </h5>
          </div>
          <div class="card-body p-4">
            <div class="text-center py-2 mb-3 border-bottom">
              <span class="text-muted small d-block text-uppercase">Monthly Installment</span>
              <h2 class="display-6 fw-bold text-primary mb-0" id="summaryMonthly">Rs. 0</h2>
              <small class="text-muted" id="summaryTenure">0 Months Tenure</small>
            </div>

            <ul class="list-group list-group-flush small mb-4">
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Merchandise Retail Cash:</span>
                <strong class="text-dark" id="summaryCash">Rs. 0</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Required Down Payment:</span>
                <strong class="text-dark" id="summaryDownPayment">Rs. 0</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Financed Principal:</span>
                <strong class="text-dark" id="summaryPrincipal">Rs. 0</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2">
                <span class="text-muted">Profit Markup Total:</span>
                <strong class="text-success" id="summaryMarkup">Rs. 0</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-2 bg-light rounded px-2 mt-1">
                <span class="fw-bold text-dark">Total Agreement Value:</span>
                <strong class="fw-bold text-primary fs-6" id="summaryTotal">Rs. 0</strong>
              </li>
            </ul>

            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-check2-circle me-1"></i>Create Agreement Draft
              </button>
              <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary">
                Cancel
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const productSelect = document.getElementById('product_id');
      const planSelect = document.getElementById('installment_plan_id');
      const downPaymentInput = document.getElementById('down_payment');
      const serializedSelect = document.getElementById('serialized_item_id');

      function recalculate() {
        const prodOption = productSelect.options[productSelect.selectedIndex];
        const planOption = planSelect.options[planSelect.selectedIndex];

        const cashPrice = prodOption && prodOption.value ? parseFloat(prodOption.getAttribute('data-price')) : 0;
        const minDpPct = prodOption && prodOption.value ? parseFloat(prodOption.getAttribute('data-mindp')) : 20.0;
        const tenureMonths = planOption && planOption.value ? parseInt(planOption.getAttribute('data-months')) : 12;
        const markupRate = planOption && planOption.value ? parseFloat(planOption.getAttribute('data-rate')) : 25.0;

        let downPayment = downPaymentInput.value ? parseFloat(downPaymentInput.value) : Math.round((cashPrice * (minDpPct / 100.0)) / 100) * 100;
        if (!downPaymentInput.value && cashPrice > 0) {
          downPaymentInput.value = downPayment;
        }

        const financedPrincipal = Math.max(0, cashPrice - downPayment);
        const annualFraction = tenureMonths / 12.0;
        const markupAmount = Math.round(financedPrincipal * (markupRate / 100.0) * annualFraction);
        const totalFinanced = financedPrincipal + markupAmount;
        const totalPayable = downPayment + totalFinanced;
        const monthlyInstallment = tenureMonths > 0 ? Math.round(totalFinanced / tenureMonths) : 0;

        document.getElementById('summaryCash').innerText = 'Rs. ' + cashPrice.toLocaleString();
        document.getElementById('summaryDownPayment').innerText = 'Rs. ' + downPayment.toLocaleString();
        document.getElementById('summaryPrincipal').innerText = 'Rs. ' + financedPrincipal.toLocaleString();
        document.getElementById('summaryMarkup').innerText = 'Rs. ' + markupAmount.toLocaleString();
        document.getElementById('summaryTotal').innerText = 'Rs. ' + totalPayable.toLocaleString();
        document.getElementById('summaryMonthly').innerText = 'Rs. ' + monthlyInstallment.toLocaleString();
        document.getElementById('summaryTenure').innerText = tenureMonths + ' Months Tenure';
      }

      productSelect.addEventListener('change', function() {
        const selectedProdId = this.value;
        // Filter serialized units dropdown
        for (let i = 0; i < serializedSelect.options.length; i++) {
          const opt = serializedSelect.options[i];
          if (!opt.value) continue;
          if (opt.getAttribute('data-product') === selectedProdId) {
            opt.style.display = '';
          } else {
            opt.style.display = 'none';
          }
        }
        recalculate();
      });

      planSelect.addEventListener('change', recalculate);
      downPaymentInput.addEventListener('input', recalculate);

      recalculate();
    });
  </script>
</x-app-layout>
