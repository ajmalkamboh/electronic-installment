<x-app-layout title="Installment Quotation Simulator">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Showroom Installment Quotation Simulator</h1>
      <p class="text-muted mb-0">Live interactive pricing, markup calculation engine, and side-by-side tenure comparison.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('plans.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-gear me-1"></i>Manage Plan Templates
      </a>
      <button type="button" class="btn btn-outline-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print Customer Quote
      </button>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Simulation Inputs -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1 text-dark">
            <i class="bi bi-sliders text-primary me-2"></i>Financing Parameters
          </h5>
          <p class="text-muted small mb-0">Select merchandise and adjust down payment or terms.</p>
        </div>
        <div class="card-body p-4">
          <form id="simulatorForm">
            <!-- 1. Select Product -->
            <div class="mb-3">
              <label for="product_select" class="form-label fw-semibold text-dark">Merchandise Model</label>
              <select id="product_select" class="form-select">
                <option value="">Custom Cash Retail Price</option>
                @foreach($products as $p)
                  <option value="{{ $p->id }}" data-price="{{ $p->base_cash_price }}" data-mindp="{{ $p->min_down_payment_pct }}"
                          {{ $selectedProduct?->id === $p->id ? 'selected' : '' }}>
                    {{ $p->brand }} {{ $p->model_name }} (Rs. {{ number_format($p->base_cash_price) }})
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 2. Cash Price Input -->
            <div class="mb-3">
              <label for="cash_price" class="form-label fw-semibold text-dark">Showroom Cash Price (PKR)</label>
              <div class="input-group">
                <span class="input-group-text bg-light">Rs.</span>
                <input type="number" step="500" min="1000" id="cash_price" class="form-control"
                       value="{{ $selectedProduct ? $selectedProduct->base_cash_price : ($calculationResult ? $calculationResult->cashPrice : 150000) }}">
              </div>
            </div>

            <!-- 3. Plan / Tenure Select -->
            <div class="row g-2 mb-3">
              <div class="col-7">
                <label for="plan_select" class="form-label fw-semibold text-dark">Financing Plan Template</label>
                <select id="plan_select" class="form-select">
                  @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" data-tenure="{{ $plan->tenure_months }}" data-rate="{{ $plan->default_markup_rate_pct }}"
                            data-model="{{ $plan->markup_calculation_model }}" data-mindp="{{ $plan->min_down_payment_pct }}"
                            {{ $selectedPlan?->id === $plan->id ? 'selected' : '' }}>
                      {{ $plan->name }} ({{ $plan->tenure_months }}M - {{ $plan->default_markup_rate_pct }}%)
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="col-5">
                <label for="tenure_months" class="form-label fw-semibold text-dark">Tenure</label>
                <select id="tenure_months" class="form-select">
                  @foreach([3, 6, 9, 12, 18, 24, 36] as $m)
                    <option value="{{ $m }}" {{ ($selectedPlan?->tenure_months ?? 12) == $m ? 'selected' : '' }}>
                      {{ $m }} Months
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <!-- 4. Markup Rate -->
            <div class="mb-3">
              <label for="markup_rate" class="form-label fw-semibold text-dark">Annual Markup Rate (%)</label>
              <div class="input-group">
                <input type="number" step="0.5" min="0" max="100" id="markup_rate" class="form-control"
                       value="{{ $selectedPlan ? $selectedPlan->default_markup_rate_pct : 25.00 }}">
                <span class="input-group-text bg-light">% Flat / Year</span>
              </div>
            </div>

            <!-- 5. Interactive Down Payment Slider & Input -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="down_payment" class="form-label fw-semibold text-dark mb-0">Upfront Cash Down Payment</label>
                <span class="badge bg-light text-dark border" id="dpPercentBadge">
                  {{ $calculationResult ? $calculationResult->downPaymentPct : 20 }}% of retail price
                </span>
              </div>
              <div class="input-group mb-2">
                <span class="input-group-text bg-light">Rs.</span>
                <input type="number" step="500" min="0" id="down_payment" class="form-control"
                       value="{{ $calculationResult ? $calculationResult->downPayment : 30000 }}">
              </div>
              <input type="range" class="form-range" id="down_payment_slider" min="0" max="150000" step="1000"
                     value="{{ $calculationResult ? $calculationResult->downPayment : 30000 }}">
              <div class="d-flex justify-content-between small text-muted">
                <span>Min Required: <strong id="minDpDisplay">Rs. 30,000 (20%)</strong></span>
                <span>Full Cash (100%)</span>
              </div>
            </div>

            <!-- Managerial Approval Alert -->
            <div id="approvalAlert" class="alert alert-warning small mb-0 {{ ($calculationResult && $calculationResult->requiresManagerApproval) ? '' : 'd-none' }}">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <strong>Three-Tier Policy Notice:</strong> <span id="approvalAlertText">{{ $calculationResult?->approvalReason }}</span>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Right Column: Live Quotation Summary Card -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm border-top border-4 border-primary mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <span class="badge bg-primary-subtle text-primary text-uppercase small fw-bold">Official Quotation</span>
            <h4 class="fw-bold mb-0 text-dark mt-1" id="summaryProductName">
              {{ $selectedProduct ? $selectedProduct->brand . ' ' . $selectedProduct->model_name : 'Electronic Merchandise' }}
            </h4>
          </div>
          <div class="text-end">
            <span class="text-muted small d-block">Scheduled Tenure</span>
            <strong class="fs-5 text-dark" id="summaryTenureBadge">
              {{ $calculationResult ? $calculationResult->tenureMonths : 12 }} Months
            </strong>
          </div>
        </div>

        <div class="card-body p-4">
          <!-- Primary Monthly Installment KPI Card -->
          <div class="p-4 rounded bg-primary-subtle bg-opacity-25 border border-primary-subtle text-center mb-4">
            <span class="text-muted text-uppercase fw-semibold small d-block mb-1">Equal Monthly Installment</span>
            <h2 class="display-5 fw-bold text-primary mb-0" id="summaryMonthlyInstallment">
              Rs. {{ number_format($calculationResult ? $calculationResult->installmentAmount : 11875) }}
            </h2>
            <small class="text-muted d-block mt-1">per month across <span id="summaryTenureText">{{ $calculationResult ? $calculationResult->tenureMonths : 12 }}</span> installments</small>
          </div>

          <!-- Breakdown Grid -->
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Showroom Cash Retail Price</span>
                <h5 class="fw-bold text-dark mb-0" id="summaryCashPrice">
                  Rs. {{ number_format($calculationResult ? $calculationResult->cashPrice : 150000) }}
                </h5>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Upfront Down Payment</span>
                <h5 class="fw-bold text-success mb-0" id="summaryDownPayment">
                  Rs. {{ number_format($calculationResult ? $calculationResult->downPayment : 30000) }}
                </h5>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Financed Principal (Capital)</span>
                <h5 class="fw-bold text-dark mb-0" id="summaryFinancedPrincipal">
                  Rs. {{ number_format($calculationResult ? $calculationResult->financedPrincipal : 120000) }}
                </h5>
              </div>
            </div>

            <div class="col-sm-6">
              <div class="p-3 bg-light rounded">
                <span class="text-muted small fw-semibold d-block mb-1">Total Markup (Financing Profit)</span>
                <h5 class="fw-bold text-primary mb-0" id="summaryMarkupAmount">
                  Rs. {{ number_format($calculationResult ? $calculationResult->markupAmount : 30000) }}
                </h5>
              </div>
            </div>

            <div class="col-12">
              <div class="p-3 bg-primary text-white rounded d-flex justify-content-between align-items-center">
                <div>
                  <span class="small d-block text-white-50 fw-semibold text-uppercase">Total Agreement Amount Payable</span>
                  <h4 class="fw-bold mb-0 text-white" id="summaryTotalPayable">
                    Rs. {{ number_format($calculationResult ? $calculationResult->totalPayable : 172500) }}
                  </h4>
                </div>
                <div class="text-end">
                  <span class="badge bg-white text-primary fs-6" id="summaryMarginBadge">
                    {{ $calculationResult ? $calculationResult->profitMarginPct : 20 }}% Profit Margin
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section: Side-by-Side Multi-Tenure Comparison Cards -->
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-grid-3x3-gap text-primary me-2"></i>Multi-Tenure Side-by-Side Comparison Matrix
          </h5>
          <p class="text-muted small mb-0">Compare monthly installment quotas and total agreement costs across 3, 6, 12, 18, and 24 months.</p>
        </div>
        <div class="card-body p-4">
          <div class="row g-3" id="tenureComparisonRow">
            @foreach([3, 6, 12, 18, 24] as $t)
              @php $comp = $tenureComparisons[$t] ?? null; @endphp
              <div class="col-md" id="compCardCol{{ $t }}">
                <div class="card border h-100 {{ $t == 12 ? 'border-primary border-2 bg-primary-subtle bg-opacity-10' : '' }}">
                  <div class="card-body text-center p-3">
                    @if($t == 12)
                      <span class="badge bg-primary mb-1">Most Popular</span>
                    @else
                      <span class="badge bg-light text-dark border mb-1">{{ $t }} Months</span>
                    @endif
                    <h5 class="fw-bold text-dark mt-1 mb-2">{{ $t }}M Plan</h5>
                    <div class="my-2">
                      <span class="text-muted small d-block">Monthly Payment</span>
                      <strong class="fs-5 text-primary comp-monthly" data-tenure="{{ $t }}">
                        Rs. {{ number_format($comp ? $comp->installmentAmount : 0) }}
                      </strong>
                    </div>
                    <ul class="list-group list-group-flush small text-start border-top pt-2">
                      <li class="list-group-item d-flex justify-content-between px-0 py-1 bg-transparent">
                        <span class="text-muted">Markup:</span>
                        <strong class="comp-markup" data-tenure="{{ $t }}">Rs. {{ number_format($comp ? $comp->markupAmount : 0) }}</strong>
                      </li>
                      <li class="list-group-item d-flex justify-content-between px-0 py-1 bg-transparent">
                        <span class="text-muted">Total:</span>
                        <strong class="comp-total" data-tenure="{{ $t }}">Rs. {{ number_format($comp ? $comp->totalPayable : 0) }}</strong>
                      </li>
                    </ul>
                    <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-3 select-tenure-btn" data-tenure="{{ $t }}">
                      Select {{ $t }}M
                    </button>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const productSelect = document.getElementById('product_select');
      const cashPriceInput = document.getElementById('cash_price');
      const planSelect = document.getElementById('plan_select');
      const tenureSelect = document.getElementById('tenure_months');
      const markupRateInput = document.getElementById('markup_rate');
      const downPaymentInput = document.getElementById('down_payment');
      const dpSlider = document.getElementById('down_payment_slider');

      const dpPercentBadge = document.getElementById('dpPercentBadge');
      const minDpDisplay = document.getElementById('minDpDisplay');
      const approvalAlert = document.getElementById('approvalAlert');
      const approvalAlertText = document.getElementById('approvalAlertText');

      const summaryProductName = document.getElementById('summaryProductName');
      const summaryTenureBadge = document.getElementById('summaryTenureBadge');
      const summaryMonthlyInstallment = document.getElementById('summaryMonthlyInstallment');
      const summaryTenureText = document.getElementById('summaryTenureText');
      const summaryCashPrice = document.getElementById('summaryCashPrice');
      const summaryDownPayment = document.getElementById('summaryDownPayment');
      const summaryFinancedPrincipal = document.getElementById('summaryFinancedPrincipal');
      const summaryMarkupAmount = document.getElementById('summaryMarkupAmount');
      const summaryTotalPayable = document.getElementById('summaryTotalPayable');
      const summaryMarginBadge = document.getElementById('summaryMarginBadge');

      let currentMinDpPct = 20;

      function onProductChanged() {
        const selected = productSelect.options[productSelect.selectedIndex];
        if (selected && selected.value) {
          const price = parseFloat(selected.getAttribute('data-price')) || 0;
          currentMinDpPct = parseFloat(selected.getAttribute('data-mindp')) || 20;
          cashPriceInput.value = price;
          dpSlider.max = price;
          const minDp = Math.round(price * (currentMinDpPct / 100));
          downPaymentInput.value = minDp;
          dpSlider.value = minDp;
          summaryProductName.innerText = selected.text.split('(')[0].trim();
        }
        recalculate();
      }

      function onPlanChanged() {
        const selected = planSelect.options[planSelect.selectedIndex];
        if (selected && selected.value) {
          const tenure = selected.getAttribute('data-tenure');
          const rate = selected.getAttribute('data-rate');
          tenureSelect.value = tenure;
          markupRateInput.value = rate;
        }
        recalculate();
      }

      function recalculate() {
        const cashPrice = parseFloat(cashPriceInput.value) || 0;
        const tenure = parseInt(tenureSelect.value) || 12;
        const rate = parseFloat(markupRateInput.value) || 0;
        const downPayment = parseFloat(downPaymentInput.value) || 0;
        const productId = productSelect.value || null;

        dpSlider.max = cashPrice;
        dpSlider.value = downPayment;

        const minDpVal = Math.round(cashPrice * (currentMinDpPct / 100));
        minDpDisplay.innerText = 'Rs. ' + minDpVal.toLocaleString() + ' (' + currentMinDpPct + '%)';

        const dpPct = cashPrice > 0 ? ((downPayment / cashPrice) * 100).toFixed(1) : 0;
        dpPercentBadge.innerText = dpPct + '% of retail price';

        // AJAX live calculate
        fetch("{{ route('pricing.calculate') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            cash_price: cashPrice,
            product_id: productId,
            tenure_months: tenure,
            markup_rate: rate,
            down_payment: downPayment
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const res = data.result;
            summaryMonthlyInstallment.innerText = 'Rs. ' + Math.round(res.installment_amount).toLocaleString();
            summaryCashPrice.innerText = 'Rs. ' + Math.round(res.cash_price).toLocaleString();
            summaryDownPayment.innerText = 'Rs. ' + Math.round(res.down_payment).toLocaleString();
            summaryFinancedPrincipal.innerText = 'Rs. ' + Math.round(res.financed_principal).toLocaleString();
            summaryMarkupAmount.innerText = 'Rs. ' + Math.round(res.markup_amount).toLocaleString();
            summaryTotalPayable.innerText = 'Rs. ' + Math.round(res.total_payable).toLocaleString();
            summaryMarginBadge.innerText = res.profit_margin_pct + '% Profit Margin';
            summaryTenureBadge.innerText = res.tenure_months + ' Months';
            summaryTenureText.innerText = res.tenure_months;

            if (res.requires_manager_approval) {
              approvalAlert.classList.remove('d-none');
              approvalAlertText.innerText = res.approval_reason;
            } else {
              approvalAlert.classList.add('d-none');
            }

            // Update comparison matrix
            if (data.comparisons) {
              Object.keys(data.comparisons).forEach(t => {
                const comp = data.comparisons[t];
                const monthlyElem = document.querySelector(`.comp-monthly[data-tenure="${t}"]`);
                const markupElem = document.querySelector(`.comp-markup[data-tenure="${t}"]`);
                const totalElem = document.querySelector(`.comp-total[data-tenure="${t}"]`);
                if (monthlyElem) monthlyElem.innerText = 'Rs. ' + Math.round(comp.installment_amount).toLocaleString();
                if (markupElem) markupElem.innerText = 'Rs. ' + Math.round(comp.markup_amount).toLocaleString();
                if (totalElem) totalElem.innerText = 'Rs. ' + Math.round(comp.total_payable).toLocaleString();
              });
            }
          }
        })
        .catch(err => console.error(err));
      }

      productSelect.addEventListener('change', onProductChanged);
      planSelect.addEventListener('change', onPlanChanged);
      cashPriceInput.addEventListener('input', recalculate);
      tenureSelect.addEventListener('change', recalculate);
      markupRateInput.addEventListener('input', recalculate);

      downPaymentInput.addEventListener('input', function () {
        dpSlider.value = downPaymentInput.value;
        recalculate();
      });

      dpSlider.addEventListener('input', function () {
        downPaymentInput.value = dpSlider.value;
        recalculate();
      });

      document.querySelectorAll('.select-tenure-btn').forEach(btn => {
        btn.addEventListener('click', function () {
          const t = this.getAttribute('data-tenure');
          tenureSelect.value = t;
          recalculate();
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
      });
    });
  </script>
  @endpush
</x-app-layout>
