<x-app-layout title="Executive Analytics & Portfolio Intelligence">
  <!-- Header & Filter Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Portfolio Analytics &amp; Risk Intelligence</h1>
      <p class="text-muted mb-0">Real-time microfinance portfolio monitoring &bull; PAR 30/60/90 tracking &bull; Pakistan Hire-Purchase Engine</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <form method="GET" action="{{ route('analytics.dashboard') }}" class="d-flex gap-2 align-items-center">
        <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Showrooms (Consolidated)</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
          @endforeach
        </select>
        @if($branchId)
          <a href="{{ route('analytics.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        @endif
      </form>
      <a href="{{ route('analytics.aging') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-clock-history me-1"></i>Aging Buckets
      </a>
      <a href="{{ route('analytics.collections') }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-cash-stack me-1"></i>Collections
      </a>
    </div>
  </div>

  <!-- Primary Executive Metric Cards -->
  <div class="row g-3 mb-4">
    <!-- Active Portfolio -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Total Active Portfolio</p>
              <h3 class="fw-bold mb-0 text-primary">PKR {{ number_format($kpis['total_portfolio'], 0) }}</h3>
              <small class="text-muted">{{ number_format($kpis['active_accounts']) }} Active Contracts</small>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
              <i class="bi bi-wallet2 fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Collection Efficiency -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Monthly Collection Efficiency</p>
              <h3 class="fw-bold mb-0 {{ $kpis['collection_efficiency'] >= 90 ? 'text-success' : ($kpis['collection_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">
                {{ $kpis['collection_efficiency'] }}%
              </h3>
              <small class="text-muted">PKR {{ number_format($kpis['monthly_collected'], 0) }} / {{ number_format($kpis['monthly_billed'], 0) }}</small>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
              <i class="bi bi-check2-circle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAR 30 (Risk 30+) -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Portfolio At Risk (PAR 30)</p>
              <h3 class="fw-bold mb-0 text-warning">{{ $kpis['par_30_pct'] }}%</h3>
              <small class="text-muted">PKR {{ number_format($kpis['par_30_amount'], 0) }} at risk (>30d)</small>
            </div>
            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
              <i class="bi bi-exclamation-triangle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- PAR 90 (Default Risk) -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">NPL / PAR 90 (Loss Risk)</p>
              <h3 class="fw-bold mb-0 text-danger">{{ $kpis['par_90_pct'] }}%</h3>
              <small class="text-muted">PKR {{ number_format($kpis['par_90_amount'], 0) }} critical (>90d)</small>
            </div>
            <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
              <i class="bi bi-shield-slash fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PAR Distribution Breakdown & Recovery Velocity -->
  <div class="row g-4 mb-4">
    <!-- PAR Risk Exposure Spectrum -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>PAR Risk Distribution</h5>
          <a href="{{ route('analytics.aging') }}" class="btn btn-sm btn-link text-decoration-none">View Full Aging &rarr;</a>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span>Current / Healthy (&le; 30 days overdue)</span>
              <span class="text-success">{{ number_format(max(0, 100 - $kpis['par_30_pct']), 1) }}%</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ max(0, 100 - $kpis['par_30_pct']) }}%"></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span>PAR 30 (31 - 60 days overdue)</span>
              <span class="text-warning">{{ $kpis['par_30_pct'] }}% (PKR {{ number_format($kpis['par_30_amount'], 0) }})</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min(100, $kpis['par_30_pct']) }}%"></div>
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span>PAR 60 (61 - 90 days overdue)</span>
              <span class="text-orange" style="color: #fd7e14;">{{ $kpis['par_60_pct'] }}% (PKR {{ number_format($kpis['par_60_amount'], 0) }})</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-danger bg-opacity-75" role="progressbar" style="width: {{ min(100, $kpis['par_60_pct']) }}%"></div>
            </div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between small fw-semibold mb-1">
              <span>PAR 90 / Non-Performing (&gt; 90 days overdue)</span>
              <span class="text-danger">{{ $kpis['par_90_pct'] }}% (PKR {{ number_format($kpis['par_90_amount'], 0) }})</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min(100, $kpis['par_90_pct']) }}%"></div>
            </div>
          </div>

          <div class="p-3 bg-light rounded-3 d-flex justify-content-around text-center">
            <div>
              <div class="text-muted small">Cumulative Sales Volume</div>
              <div class="fw-bold fs-6">PKR {{ number_format($kpis['gross_sales_volume'], 0) }}</div>
            </div>
            <div class="border-start"></div>
            <div>
              <div class="text-muted small">Matured / Completed</div>
              <div class="fw-bold fs-6 text-success">{{ number_format($kpis['total_completed_contracts']) }} Contracts</div>
            </div>
            <div class="border-start"></div>
            <div>
              <div class="text-muted small">Defaulted Contracts</div>
              <div class="fw-bold fs-6 text-danger">{{ number_format($kpis['total_defaulted_contracts']) }} Contracts</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Collection Velocity (6 Months) -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Monthly Collection Trend</h5>
          <a href="{{ route('analytics.collections') }}" class="btn btn-sm btn-link text-decoration-none">Full Collections &rarr;</a>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Month</th>
                  <th class="text-end">Due / Billed</th>
                  <th class="text-end">Collected</th>
                  <th class="text-end">Efficiency</th>
                </tr>
              </thead>
              <tbody>
                @forelse($collectionEfficiency['monthly_trend'] as $trend)
                  <tr>
                    <td class="fw-medium">{{ $trend['label'] }}</td>
                    <td class="text-end text-muted">PKR {{ number_format($trend['target_billed'], 0) }}</td>
                    <td class="text-end fw-semibold text-success">PKR {{ number_format($trend['actual_collected'], 0) }}</td>
                    <td class="text-end">
                      <span class="badge {{ $trend['efficiency_pct'] >= 90 ? 'bg-success' : ($trend['efficiency_pct'] >= 70 ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ $trend['efficiency_pct'] }}%
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-3">No recent billing data found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Branch Leaderboard & Appliance Category Intelligence -->
  <div class="row g-4">
    <!-- Multi-Branch Performance Leaderboard -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0"><i class="bi bi-buildings me-2 text-info"></i>Showroom Recovery Leaderboard</h5>
            <small class="text-muted">Comparative recovery performance across retail showrooms</small>
          </div>
          <a href="{{ route('analytics.branches') }}" class="btn btn-sm btn-outline-secondary">Leaderboard Details</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Showroom Branch</th>
                  <th class="text-center">Accounts</th>
                  <th class="text-end">Portfolio</th>
                  <th class="text-center">PAR 30 %</th>
                  <th class="text-end">This Month Recovered</th>
                  <th class="text-center">Efficiency</th>
                </tr>
              </thead>
              <tbody>
                @forelse($branchPerformance as $bp)
                  <tr>
                    <td>
                      <div class="fw-bold">{{ $bp['branch_name'] }}</div>
                      <small class="text-muted">{{ $bp['branch_code'] }} &bull; {{ $bp['city'] ?? 'Showroom' }}</small>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark">{{ $bp['active_accounts'] }}</span>
                    </td>
                    <td class="text-end fw-semibold">PKR {{ number_format($bp['total_portfolio'], 0) }}</td>
                    <td class="text-center">
                      <span class="badge {{ $bp['par_30_pct'] > 15 ? 'bg-danger' : ($bp['par_30_pct'] > 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                        {{ $bp['par_30_pct'] }}%
                      </span>
                    </td>
                    <td class="text-end text-success fw-medium">PKR {{ number_format($bp['monthly_collected'], 0) }}</td>
                    <td class="text-center">
                      <span class="badge {{ $bp['recovery_efficiency'] >= 85 ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $bp['recovery_efficiency'] }}%
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">No active showroom branches registered.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Appliance Category Breakdown -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0"><i class="bi bi-tv me-2 text-primary"></i>Category Portfolio Risk</h5>
            <small class="text-muted">Appliance categories &amp; default propensity</small>
          </div>
          <a href="{{ route('analytics.products') }}" class="btn btn-sm btn-outline-secondary">Category Details</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Category</th>
                  <th class="text-center">Sold</th>
                  <th class="text-end">Financed Vol</th>
                  <th class="text-center">Default %</th>
                </tr>
              </thead>
              <tbody>
                @forelse($productStats as $cat)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $cat['category_name'] }}</div>
                      <small class="text-muted">Avg PKR {{ number_format($cat['average_ticket_size'], 0) }}</small>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-light text-dark">{{ $cat['units_sold'] }}</span>
                    </td>
                    <td class="text-end fw-semibold">PKR {{ number_format($cat['total_financed_volume'], 0) }}</td>
                    <td class="text-center">
                      <span class="badge {{ $cat['default_rate_pct'] > 10 ? 'bg-danger' : ($cat['default_rate_pct'] > 0 ? 'bg-warning text-dark' : 'bg-success') }}">
                        {{ $cat['default_rate_pct'] }}%
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">No appliance category installment records.</td>
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
