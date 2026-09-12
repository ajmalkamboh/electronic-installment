<x-app-layout title="Showroom Cash Book">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Showroom Daily Cash Book</h1>
      <p class="text-muted mb-0">Daily cash receipts, disbursements, and physical counter drawer reconciliation for {{ \Carbon\Carbon::parse($report['date'])->format('d F, Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Cash Sheet
      </button>
      <a href="{{ route('accounting.journal') }}" class="btn btn-outline-secondary">
        <i class="bi bi-journal-bookmark me-1"></i>Journal Entries
      </a>
      <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calculator me-1"></i>Trial Balance
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.cash-book') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Reconciliation Date</label>
          <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-5">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Branches / Central Drawer</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($branchId || $date != date('Y-m-d'))
            <a href="{{ route('accounting.cash-book') }}" class="btn btn-sm btn-outline-secondary">Today</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Opening Drawer Balance</span>
          <span class="fs-4 fw-bold text-dark">PKR {{ number_format($report['opening_balance'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Total Cash Inflow (Receipts)</span>
          <span class="fs-4 fw-bold text-success">+ PKR {{ number_format($report['total_inflow'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Total Outflow (Disbursements)</span>
          <span class="fs-4 fw-bold text-danger">- PKR {{ number_format($report['total_outflow'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Closing Cash in Drawer</span>
          <span class="fs-4 fw-bold text-primary">PKR {{ number_format($report['closing_balance'], 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Cash Flow Detailed Ledger (Two Columns) -->
  <div class="row g-4 mb-4">
    <!-- Left: Cash Receipts (Inflows) -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="bi bi-arrow-down-left-circle me-2"></i>Cash Receipts (Inflows)</h5>
          <span class="badge bg-white text-success fw-bold">{{ count($report['inflows']) }} Transactions</span>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered mb-0 align-middle" style="font-size: 12.5px;">
            <thead class="table-light">
              <tr>
                <th style="width: 70px;">Time</th>
                <th style="width: 130px;">Entry #</th>
                <th>Description</th>
                <th class="text-end" style="width: 120px;">Amount (PKR)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($report['inflows'] as $in)
              <tr>
                <td class="small text-muted">{{ $in['time'] }}</td>
                <td class="font-monospace fw-bold text-primary small">{{ $in['entry_number'] }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $in['description'] }}</div>
                  @if($in['memo'])
                    <small class="text-muted d-block">{{ $in['memo'] }}</small>
                  @endif
                  <small class="badge bg-light text-secondary border text-uppercase" style="font-size: 9px;">{{ str_replace('_', ' ', $in['reference_type']) }}</small>
                </td>
                <td class="text-end font-monospace fw-bold text-success">{{ number_format($in['debit'], 2) }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">No cash receipts recorded on this date.</td>
              </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr class="table-success">
                <td colspan="3" class="text-end">TOTAL INFLOWS:</td>
                <td class="text-end font-monospace text-success">PKR {{ number_format($report['total_inflow'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Right: Cash Disbursements (Outflows) -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="bi bi-arrow-up-right-circle me-2"></i>Cash Payments (Outflows)</h5>
          <span class="badge bg-white text-danger fw-bold">{{ count($report['outflows']) }} Transactions</span>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered mb-0 align-middle" style="font-size: 12.5px;">
            <thead class="table-light">
              <tr>
                <th style="width: 70px;">Time</th>
                <th style="width: 130px;">Entry #</th>
                <th>Description</th>
                <th class="text-end" style="width: 120px;">Amount (PKR)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($report['outflows'] as $out)
              <tr>
                <td class="small text-muted">{{ $out['time'] }}</td>
                <td class="font-monospace fw-bold text-primary small">{{ $out['entry_number'] }}</td>
                <td>
                  <div class="fw-semibold text-dark">{{ $out['description'] }}</div>
                  @if($out['memo'])
                    <small class="text-muted d-block">{{ $out['memo'] }}</small>
                  @endif
                  <small class="badge bg-light text-secondary border text-uppercase" style="font-size: 9px;">{{ str_replace('_', ' ', $out['reference_type']) }}</small>
                </td>
                <td class="text-end font-monospace fw-bold text-danger">{{ number_format($out['credit'], 2) }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">No cash disbursements recorded on this date.</td>
              </tr>
              @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
              <tr class="table-danger">
                <td colspan="3" class="text-end">TOTAL OUTFLOWS:</td>
                <td class="text-end font-monospace text-danger">PKR {{ number_format($report['total_outflow'], 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Physical Cash Drawer Count Reconciliation Note -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <h5 class="fw-bold mb-0"><i class="bi bi-safe me-2 text-primary"></i>Daily Cashier Drawer Physical Verification &amp; Sign-off</h5>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="p-3 bg-light rounded border">
            <small class="text-muted d-block fw-bold text-uppercase">System Closing Balance</small>
            <span class="fs-4 fw-bold text-dark">PKR {{ number_format($report['closing_balance'], 2) }}</span>
          </div>
        </div>
        <div class="col-md-4 text-center">
          <div class="p-3 border rounded border-dashed" style="min-height: 80px;">
            <div class="small fw-bold mb-3">Head Cashier Signature</div>
            <div class="border-top pt-1 small text-muted">Physical Count Verified</div>
          </div>
        </div>
        <div class="col-md-4 text-center">
          <div class="p-3 border rounded border-dashed" style="min-height: 80px;">
            <div class="small fw-bold mb-3">Branch Manager Seal &amp; Approval</div>
            <div class="border-top pt-1 small text-muted">Drawer Locked &amp; Reconciled</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
