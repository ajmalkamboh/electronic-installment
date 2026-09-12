<x-app-layout title="Profit & Loss Statement">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Profit &amp; Loss Statement</h1>
      <p class="text-muted mb-0">Income Statement from {{ \Carbon\Carbon::parse($report['start_date'])->format('d M, Y') }} to {{ \Carbon\Carbon::parse($report['end_date'])->format('d M, Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print P&amp;L
      </button>
      <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-outline-secondary">
        <i class="bi bi-bank me-1"></i>Balance Sheet
      </a>
      <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calculator me-1"></i>Trial Balance
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.profit-loss') }}" class="row g-2 align-items-center">
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Start Date</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">End Date</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches / Consolidated</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($branchId || $startDate != date('Y-m-01') || $endDate != date('Y-m-d'))
            <a href="{{ route('accounting.profit-loss') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Gross Revenue</span>
          <span class="fs-4 fw-bold text-success">PKR {{ number_format($report['total_revenue'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Cost of Goods Sold (COGS)</span>
          <span class="fs-4 fw-bold text-dark">PKR {{ number_format($report['cogs'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-4 {{ $report['net_profit'] >= 0 ? 'border-primary' : 'border-danger' }}">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Net Profit / (Loss)</span>
          <span class="fs-4 fw-bold {{ $report['net_profit'] >= 0 ? 'text-primary' : 'text-danger' }}">
            PKR {{ number_format($report['net_profit'], 2) }}
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Financial Statement Document -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <h5 class="fw-bold mb-0 text-uppercase">{{ Auth::user()->company->name }} &bull; Income Statement</h5>
      <small class="text-muted">Period: {{ \Carbon\Carbon::parse($report['start_date'])->format('d M, Y') }} to {{ \Carbon\Carbon::parse($report['end_date'])->format('d M, Y') }}</small>
    </div>
    <div class="card-body p-0">
      <table class="table table-bordered mb-0 align-middle" style="font-size: 13.5px;">
        <!-- 1. Operating Revenue -->
        <thead class="table-light">
          <tr class="fw-bold">
            <th colspan="2" class="text-uppercase text-success">1. Operating Revenue &amp; Income</th>
            <th class="text-end" style="width: 200px;">Amount (PKR)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($report['revenue_items'] as $rev)
          <tr>
            <td style="width: 100px;" class="font-monospace text-muted">{{ $rev['code'] }}</td>
            <td>
              <a href="{{ route('accounting.account-ledger', $rev['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                {{ $rev['name'] }}
              </a>
            </td>
            <td class="text-end font-monospace">{{ number_format($rev['amount'], 2) }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center text-muted py-2">No operating revenue realized in this period.</td>
          </tr>
          @endforelse
          <tr class="table-light fw-bold">
            <td colspan="2" class="text-end text-success">TOTAL REVENUE (A):</td>
            <td class="text-end font-monospace text-success fs-6">PKR {{ number_format($report['total_revenue'], 2) }}</td>
          </tr>
        </tbody>

        <!-- 2. Cost of Goods Sold -->
        <thead class="table-light">
          <tr class="fw-bold">
            <th colspan="2" class="text-uppercase text-secondary">2. Direct Costs (Cost of Goods Sold)</th>
            <th class="text-end">Amount (PKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="font-monospace text-muted">5010</td>
            <td>Cost of Goods Sold (Wholesale Merchandise Cost)</td>
            <td class="text-end font-monospace">{{ number_format($report['cogs'], 2) }}</td>
          </tr>
          <tr class="table-light fw-bold">
            <td colspan="2" class="text-end">GROSS PROFIT (A - B):</td>
            <td class="text-end font-monospace text-dark fs-6">PKR {{ number_format($report['gross_profit'], 2) }}</td>
          </tr>
        </tbody>

        <!-- 3. Operating & Administrative Expenses -->
        <thead class="table-light">
          <tr class="fw-bold">
            <th colspan="2" class="text-uppercase text-danger">3. Operating Expenses &amp; Concessions</th>
            <th class="text-end">Amount (PKR)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($report['expense_items'] as $exp)
          @if($exp['code'] !== '5010')
          <tr>
            <td class="font-monospace text-muted">{{ $exp['code'] }}</td>
            <td>
              <a href="{{ route('accounting.account-ledger', $exp['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                {{ $exp['name'] }}
              </a>
            </td>
            <td class="text-end font-monospace">{{ number_format($exp['amount'], 2) }}</td>
          </tr>
          @endif
          @empty
          <tr>
            <td colspan="3" class="text-center text-muted py-2">No operating expenses recorded.</td>
          </tr>
          @endforelse
          <tr class="table-light fw-bold">
            <td colspan="2" class="text-end text-danger">TOTAL OPERATING EXPENSES (C):</td>
            <td class="text-end font-monospace text-danger">PKR {{ number_format($report['operating_expenses'], 2) }}</td>
          </tr>
        </tbody>

        <!-- Net Profit Line -->
        <tfoot>
          <tr class="table-dark fs-5 fw-bold">
            <td colspan="2" class="text-end">NET PROFIT / (LOSS):</td>
            <td class="text-end font-monospace {{ $report['net_profit'] >= 0 ? 'text-warning' : 'text-danger' }}">
              PKR {{ number_format($report['net_profit'], 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</x-app-layout>
