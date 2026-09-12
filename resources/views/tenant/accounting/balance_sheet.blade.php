<x-app-layout title="Balance Sheet">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Balance Sheet</h1>
      <p class="text-muted mb-0">Statement of Financial Position &bull; As of {{ \Carbon\Carbon::parse($report['as_of_date'])->format('d F, Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Balance Sheet
      </button>
      <a href="{{ route('accounting.profit-loss') }}" class="btn btn-outline-secondary">
        <i class="bi bi-graph-up me-1"></i>Profit &amp; Loss
      </a>
      <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calculator me-1"></i>Trial Balance
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.balance-sheet') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">As of Date</label>
          <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-5">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches / Consolidated</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($branchId || $asOfDate != date('Y-m-d'))
            <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Balanced Verification Banner -->
  @if($report['is_balanced'])
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4 py-3" role="alert">
      <i class="bi bi-patch-check-fill fs-3 me-3 text-success"></i>
      <div>
        <h6 class="fw-bold mb-0 text-success">Fundamental Accounting Equation Verified</h6>
        <small class="text-muted">Total Assets (PKR {{ number_format($report['total_assets'], 2) }}) exactly equals Total Liabilities &amp; Equity (PKR {{ number_format($report['total_liabilities_and_equity'], 2) }}).</small>
      </div>
      <span class="badge bg-success ms-auto fs-6 py-2 px-3">ASSETS = LIABILITIES + EQUITY</span>
    </div>
  @else
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 py-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
      <div>
        <h6 class="fw-bold mb-0 text-danger">Balance Sheet Imbalance Detected</h6>
        <small class="text-dark">Discrepancy of PKR {{ number_format($report['difference'], 2) }}.</small>
      </div>
      <span class="badge bg-danger ms-auto fs-6 py-2 px-3">DISCREPANCY</span>
    </div>
  @endif

  <!-- Statement Tables -->
  <div class="row g-4">
    <!-- Left Column: Assets -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-primary text-white py-3">
          <h5 class="fw-bold mb-0 text-uppercase"><i class="bi bi-wallet2 me-2"></i>Assets (1000 Series)</h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered mb-0 align-middle" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th style="width: 90px;">Code</th>
                <th>Asset Account</th>
                <th class="text-end" style="width: 140px;">Balance (PKR)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($report['assets'] as $asset)
              <tr>
                <td class="font-monospace text-primary fw-bold">{{ $asset['code'] }}</td>
                <td>
                  <a href="{{ route('accounting.account-ledger', $asset['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                    {{ $asset['name'] }}
                  </a>
                </td>
                <td class="text-end font-monospace fw-bold text-dark">{{ number_format($asset['amount'], 2) }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-3">No active assets booked.</td>
              </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fs-6 fw-bold">
              <tr class="table-primary">
                <td colspan="2" class="text-end text-primary">TOTAL ASSETS:</td>
                <td class="text-end font-monospace text-primary">PKR {{ number_format($report['total_assets'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column: Liabilities & Equity -->
    <div class="col-lg-6">
      <!-- Liabilities Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark py-3">
          <h5 class="fw-bold mb-0 text-uppercase"><i class="bi bi-clock-history me-2"></i>Liabilities (2000 Series)</h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered mb-0 align-middle" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th style="width: 90px;">Code</th>
                <th>Liability Account</th>
                <th class="text-end" style="width: 140px;">Balance (PKR)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($report['liabilities'] as $liab)
              <tr>
                <td class="font-monospace text-warning text-dark fw-bold">{{ $liab['code'] }}</td>
                <td>
                  <a href="{{ route('accounting.account-ledger', $liab['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                    {{ $liab['name'] }}
                  </a>
                </td>
                <td class="text-end font-monospace fw-bold text-dark">{{ number_format($liab['amount'], 2) }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-2">No liabilities recorded.</td>
              </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fs-6 fw-bold">
              <tr>
                <td colspan="2" class="text-end">TOTAL LIABILITIES:</td>
                <td class="text-end font-monospace">PKR {{ number_format($report['total_liabilities'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Equity Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-info text-dark py-3">
          <h5 class="fw-bold mb-0 text-uppercase"><i class="bi bi-shield-check me-2"></i>Equity &amp; Surplus (3000 Series)</h5>
        </div>
        <div class="card-body p-0">
          <table class="table table-bordered mb-0 align-middle" style="font-size: 13px;">
            <thead class="table-light">
              <tr>
                <th style="width: 90px;">Code</th>
                <th>Equity Account</th>
                <th class="text-end" style="width: 140px;">Balance (PKR)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($report['equity_items'] as $eq)
              <tr>
                <td class="font-monospace text-info text-dark fw-bold">{{ $eq['code'] }}</td>
                <td>
                  <a href="{{ route('accounting.account-ledger', $eq['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                    {{ $eq['name'] }}
                  </a>
                </td>
                <td class="text-end font-monospace fw-bold text-dark">{{ number_format($eq['amount'], 2) }}</td>
              </tr>
              @endforeach
              <tr class="table-light">
                <td class="font-monospace text-muted">RET-EARN</td>
                <td>Net Income / (Loss) Accumulated to Date</td>
                <td class="text-end font-monospace fw-bold {{ $report['net_income_to_date'] >= 0 ? 'text-success' : 'text-danger' }}">
                  {{ number_format($report['net_income_to_date'], 2) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light fs-6 fw-bold">
              <tr>
                <td colspan="2" class="text-end">TOTAL EQUITY:</td>
                <td class="text-end font-monospace">PKR {{ number_format($report['total_equity'], 2) }}</td>
              </tr>
              <tr class="table-dark fs-6">
                <td colspan="2" class="text-end text-warning">TOTAL LIABILITIES &amp; EQUITY:</td>
                <td class="text-end font-monospace text-warning">PKR {{ number_format($report['total_liabilities_and_equity'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
