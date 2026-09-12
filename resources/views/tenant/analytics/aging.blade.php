<x-app-layout title="Portfolio Aging Buckets & PAR Risk Analysis">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Portfolio Aging &amp; PAR Buckets</h1>
      <p class="text-muted mb-0">Contract-level delinquency tracking &bull; As of {{ \Carbon\Carbon::parse($asOfDate)->format('d F, Y') }} &bull; Total Portfolio: PKR {{ number_format($report['total_portfolio'], 0) }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('analytics.export', array_merge(['type' => 'aging'], request()->all())) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to CSV
      </a>
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Report
      </button>
      <a href="{{ route('analytics.dashboard') }}" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2 me-1"></i>Executive Dashboard
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('analytics.aging') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">As of Date</label>
          <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Showroom Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Appliance Category</label>
          <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Appliance Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Apply Filters</button>
          @if($branchId || $categoryId || $asOfDate != date('Y-m-d'))
            <a href="{{ route('analytics.aging') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- 5 Aging Bucket Metric Cards -->
  <div class="row g-3 mb-4">
    <!-- Current (0-30 Days) -->
    <div class="col-xl">
      <div class="card border-0 shadow-sm h-100 border-top border-success border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-bold text-uppercase">Current (0-30d)</span>
            <span class="badge bg-success bg-opacity-10 text-success">{{ $report['summary']['current']['pct'] }}%</span>
          </div>
          <h4 class="fw-bold mb-0 text-success">PKR {{ number_format($report['summary']['current']['amount'], 0) }}</h4>
          <small class="text-muted">{{ $report['summary']['current']['count'] }} Contracts</small>
        </div>
      </div>
    </div>

    <!-- PAR 30 (31-60 Days) -->
    <div class="col-xl">
      <div class="card border-0 shadow-sm h-100 border-top border-warning border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-bold text-uppercase">PAR 30 (31-60d)</span>
            <span class="badge bg-warning bg-opacity-10 text-warning">{{ $report['summary']['par_30']['pct'] }}%</span>
          </div>
          <h4 class="fw-bold mb-0 text-warning">PKR {{ number_format($report['summary']['par_30']['amount'], 0) }}</h4>
          <small class="text-muted">{{ $report['summary']['par_30']['count'] }} Contracts</small>
        </div>
      </div>
    </div>

    <!-- PAR 60 (61-90 Days) -->
    <div class="col-xl">
      <div class="card border-0 shadow-sm h-100 border-top border-orange border-4" style="border-top-color: #fd7e14 !important;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-bold text-uppercase">PAR 60 (61-90d)</span>
            <span class="badge bg-orange bg-opacity-10" style="color: #fd7e14; background-color: rgba(253, 126, 20, 0.1);">{{ $report['summary']['par_60']['pct'] }}%</span>
          </div>
          <h4 class="fw-bold mb-0" style="color: #fd7e14;">PKR {{ number_format($report['summary']['par_60']['amount'], 0) }}</h4>
          <small class="text-muted">{{ $report['summary']['par_60']['count'] }} Contracts</small>
        </div>
      </div>
    </div>

    <!-- PAR 90 (91-180 Days) -->
    <div class="col-xl">
      <div class="card border-0 shadow-sm h-100 border-top border-danger border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-bold text-uppercase">PAR 90 (91-180d)</span>
            <span class="badge bg-danger bg-opacity-10 text-danger">{{ $report['summary']['par_90']['pct'] }}%</span>
          </div>
          <h4 class="fw-bold mb-0 text-danger">PKR {{ number_format($report['summary']['par_90']['amount'], 0) }}</h4>
          <small class="text-muted">{{ $report['summary']['par_90']['count'] }} Contracts</small>
        </div>
      </div>
    </div>

    <!-- Loss (180+ Days) -->
    <div class="col-xl">
      <div class="card border-0 shadow-sm h-100 border-top border-dark border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small fw-bold text-uppercase">Loss (180+d)</span>
            <span class="badge bg-dark bg-opacity-10 text-dark">{{ $report['summary']['loss']['pct'] }}%</span>
          </div>
          <h4 class="fw-bold mb-0 text-dark">PKR {{ number_format($report['summary']['loss']['amount'], 0) }}</h4>
          <small class="text-muted">{{ $report['summary']['loss']['count'] }} Contracts</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Cumulative Aging Stacked Visual -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-bold small text-uppercase">Portfolio Risk Distribution By Bucket</span>
        <span class="small text-muted">Total Outstanding: PKR {{ number_format($report['total_portfolio'], 0) }}</span>
      </div>
      <div class="progress" style="height: 16px;">
        <div class="progress-bar bg-success" style="width: {{ $report['summary']['current']['pct'] }}%" title="Current: {{ $report['summary']['current']['pct'] }}%"></div>
        <div class="progress-bar bg-warning" style="width: {{ $report['summary']['par_30']['pct'] }}%" title="PAR 30: {{ $report['summary']['par_30']['pct'] }}%"></div>
        <div class="progress-bar" style="width: {{ $report['summary']['par_60']['pct'] }}%; background-color: #fd7e14;" title="PAR 60: {{ $report['summary']['par_60']['pct'] }}%"></div>
        <div class="progress-bar bg-danger" style="width: {{ $report['summary']['par_90']['pct'] }}%" title="PAR 90: {{ $report['summary']['par_90']['pct'] }}%"></div>
        <div class="progress-bar bg-dark" style="width: {{ $report['summary']['loss']['pct'] }}%" title="Loss: {{ $report['summary']['loss']['pct'] }}%"></div>
      </div>
    </div>
  </div>

  <!-- Detailed Aging Ledger Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Contract-Level Aging Breakdown</h5>
      <span class="badge bg-light text-dark border">{{ $report['records']->count() }} Active Accounts</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Agreement</th>
              <th>Customer Information</th>
              <th>Showroom / Category</th>
              <th class="text-end">Total Financed</th>
              <th class="text-end">Balance</th>
              <th class="text-end">Overdue Amount</th>
              <th class="text-center">Days Overdue</th>
              <th class="text-center">Risk Bucket</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($report['records'] as $row)
              <tr>
                <td>
                  <a href="{{ route('agreements.show', $row['agreement_id']) }}" class="fw-bold text-decoration-none">
                    {{ $row['agreement_number'] }}
                  </a>
                </td>
                <td>
                  <div class="fw-semibold">{{ $row['customer_name'] }}</div>
                  <small class="text-muted">{{ $row['customer_cnic'] ?? 'No CNIC' }} &bull; {{ $row['customer_phone'] ?? 'No Phone' }}</small>
                </td>
                <td>
                  <div>{{ $row['branch_name'] }}</div>
                  <small class="text-muted">{{ $row['category_name'] }}</small>
                </td>
                <td class="text-end text-muted">PKR {{ number_format($row['total_financed'], 0) }}</td>
                <td class="text-end fw-semibold">PKR {{ number_format($row['remaining_balance'], 0) }}</td>
                <td class="text-end fw-bold {{ $row['overdue_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                  PKR {{ number_format($row['overdue_amount'], 0) }}
                </td>
                <td class="text-center">
                  @if($row['days_overdue'] > 0)
                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">{{ $row['days_overdue'] }}d</span>
                  @else
                    <span class="badge bg-success bg-opacity-10 text-success">0d</span>
                  @endif
                </td>
                <td class="text-center">
                  @if(str_contains($row['bucket'], 'Current'))
                    <span class="badge bg-success">{{ $row['bucket'] }}</span>
                  @elseif(str_contains($row['bucket'], 'PAR 30'))
                    <span class="badge bg-warning text-dark">{{ $row['bucket'] }}</span>
                  @elseif(str_contains($row['bucket'], 'PAR 60'))
                    <span class="badge text-white" style="background-color: #fd7e14;">{{ $row['bucket'] }}</span>
                  @elseif(str_contains($row['bucket'], 'PAR 90'))
                    <span class="badge bg-danger">{{ $row['bucket'] }}</span>
                  @else
                    <span class="badge bg-dark">{{ $row['bucket'] }}</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('agreements.show', $row['agreement_id']) }}" class="btn btn-sm btn-outline-primary" title="View Agreement">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-5">
                  <i class="bi bi-check-circle fs-2 text-success d-block mb-2"></i>
                  No active installment agreements found for the selected criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
