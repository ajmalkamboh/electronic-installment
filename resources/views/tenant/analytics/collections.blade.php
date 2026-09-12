<x-app-layout title="Collection Efficiency & Recovery Intelligence">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Collection Efficiency &amp; Cashier Recovery</h1>
      <p class="text-muted mb-0">Billed vs. Collected tracking &bull; Multi-channel payment split &bull; Staff recovery leaderboard</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('analytics.export', array_merge(['type' => 'collections'], request()->all())) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
      </a>
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print
      </button>
      <a href="{{ route('analytics.dashboard') }}" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2 me-1"></i>Dashboard
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('analytics.collections') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Showroom Branches (Consolidated)</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Time Horizon</label>
          <select name="months" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="3" {{ $months == 3 ? 'selected' : '' }}>Past 3 Months</option>
            <option value="6" {{ $months == 6 ? 'selected' : '' }}>Past 6 Months</option>
            <option value="12" {{ $months == 12 ? 'selected' : '' }}>Past 12 Months (1 Year)</option>
          </select>
        </div>
        <div class="col-md-3 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Apply</button>
          @if($branchId || $months != 6)
            <a href="{{ route('analytics.collections') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Top Metrics Summary -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-secondary border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Period Target Billed</p>
          <h3 class="fw-bold mb-0 text-dark">PKR {{ number_format($report['period_target'], 0) }}</h3>
          <small class="text-muted">Total scheduled installments in period</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Actual Cash Recovered</p>
          <h3 class="fw-bold mb-0 text-success">PKR {{ number_format($report['period_collected'], 0) }}</h3>
          <small class="text-muted">Net cash inflows across channels</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Overall Period Efficiency</p>
          <h3 class="fw-bold mb-0 {{ $report['overall_efficiency'] >= 85 ? 'text-primary' : ($report['overall_efficiency'] >= 65 ? 'text-warning' : 'text-danger') }}">
            {{ $report['overall_efficiency'] }}%
          </h3>
          <small class="text-muted">Realization rate against billing targets</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Trend Breakdown -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>Monthly Billed vs. Collected Performance</h5>
      <span class="badge bg-light text-dark border">Past {{ $months }} Months</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Billing Month</th>
              <th class="text-end">Target Due / Billed</th>
              <th class="text-end">Actual Collected</th>
              <th class="text-end">Variance / Shortfall</th>
              <th style="width: 25%;">Recovery Progress</th>
              <th class="text-center">Efficiency %</th>
            </tr>
          </thead>
          <tbody>
            @forelse($report['monthly_trend'] as $trend)
              @php
                $variance = $trend['actual_collected'] - $trend['target_billed'];
              @endphp
              <tr>
                <td class="fw-bold">{{ $trend['label'] }}</td>
                <td class="text-end text-muted">PKR {{ number_format($trend['target_billed'], 0) }}</td>
                <td class="text-end fw-semibold text-success">PKR {{ number_format($trend['actual_collected'], 0) }}</td>
                <td class="text-end fw-medium {{ $variance >= 0 ? 'text-success' : 'text-danger' }}">
                  {{ $variance >= 0 ? '+' : '' }}PKR {{ number_format($variance, 0) }}
                </td>
                <td>
                  <div class="progress" style="height: 10px;">
                    <div class="progress-bar {{ $trend['efficiency_pct'] >= 90 ? 'bg-success' : ($trend['efficiency_pct'] >= 70 ? 'bg-warning' : 'bg-danger') }}"
                         role="progressbar"
                         style="width: {{ min(100, $trend['efficiency_pct']) }}%"></div>
                  </div>
                </td>
                <td class="text-center">
                  <span class="badge {{ $trend['efficiency_pct'] >= 90 ? 'bg-success' : ($trend['efficiency_pct'] >= 70 ? 'bg-warning text-dark' : 'bg-danger') }} fs-6">
                    {{ $trend['efficiency_pct'] }}%
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No billing history available for this horizon.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Payment Methods Breakdown -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0">
          <h5 class="fw-bold mb-0"><i class="bi bi-credit-card-2-back me-2 text-info"></i>Collections By Payment Channel</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Method</th>
                  <th class="text-center">Transactions</th>
                  <th class="text-end">Total Collected</th>
                </tr>
              </thead>
              <tbody>
                @forelse($report['payment_methods'] as $pm)
                  <tr>
                    <td>
                      <span class="badge bg-light text-dark border text-uppercase">{{ $pm['method'] }}</span>
                    </td>
                    <td class="text-center">{{ number_format($pm['count']) }}</td>
                    <td class="text-end fw-bold text-success">PKR {{ number_format($pm['total'], 0) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No payments recorded in this period.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Cashier Recovery Performance -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0">
          <h5 class="fw-bold mb-0"><i class="bi bi-person-check me-2 text-success"></i>Cashier &amp; Recovery Officer Breakdown</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Staff Member</th>
                  <th class="text-center">Receipts Issued</th>
                  <th class="text-end">Total Recovered</th>
                </tr>
              </thead>
              <tbody>
                @forelse($report['cashier_performance'] as $cashier)
                  <tr>
                    <td class="fw-semibold">
                      <i class="bi bi-person me-1 text-muted"></i>{{ $cashier['name'] }}
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark">{{ $cashier['transactions_count'] }}</span>
                    </td>
                    <td class="text-end fw-bold text-success">PKR {{ number_format($cashier['total_collected'], 0) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No cashier collections in this period.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
