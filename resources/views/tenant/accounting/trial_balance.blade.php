<x-app-layout title="Trial Balance Statement">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Trial Balance</h1>
      <p class="text-muted mb-0">General ledger closing balances verification &bull; As of {{ \Carbon\Carbon::parse($report['as_of_date'])->format('d F, Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Statement
      </button>
      <a href="{{ route('accounting.profit-loss') }}" class="btn btn-outline-secondary">
        <i class="bi bi-graph-up me-1"></i>Profit &amp; Loss
      </a>
      <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-outline-secondary">
        <i class="bi bi-bank me-1"></i>Balance Sheet
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.trial-balance') }}" class="row g-2 align-items-center">
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
          <button type="submit" class="btn btn-sm btn-primary me-1">Apply Filter</button>
          @if($branchId || $asOfDate != date('Y-m-d'))
            <a href="{{ route('accounting.trial-balance') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Balanced Banner -->
  @if($report['is_balanced'])
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4 py-3" role="alert">
      <i class="bi bi-patch-check-fill fs-3 me-3 text-success"></i>
      <div>
        <h6 class="fw-bold mb-0 text-success">Trial Balance is Perfectly Balanced</h6>
        <small class="text-muted">Total Debits (PKR {{ number_format($report['total_debit'], 2) }}) equal Total Credits (PKR {{ number_format($report['total_credit'], 2) }}). Zero accounting discrepancy.</small>
      </div>
      <span class="badge bg-success ms-auto fs-6 py-2 px-3">BALANCED</span>
    </div>
  @else
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 py-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
      <div>
        <h6 class="fw-bold mb-0 text-danger">Ledger Imbalance Detected</h6>
        <small class="text-dark">Discrepancy of PKR {{ number_format($report['difference'], 2) }} between debits and credits. Review recent unposted manual entries.</small>
      </div>
      <span class="badge bg-danger ms-auto fs-6 py-2 px-3">OUT OF BALANCE</span>
    </div>
  @endif

  <!-- Statement Document Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold mb-0 text-uppercase">{{ Auth::user()->company->name }}</h5>
        <small class="text-muted">Trial Balance Summary &bull; Generated on {{ now()->format('d M, Y H:i') }}</small>
      </div>
      <span class="badge bg-light text-dark border">{{ count($report['rows']) }} Active Accounts</span>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light">
          <tr>
            <th style="width: 100px;">Code</th>
            <th>Account Title</th>
            <th style="width: 130px;">Class</th>
            <th class="text-end" style="width: 170px;">Debit Balance (PKR)</th>
            <th class="text-end" style="width: 170px;">Credit Balance (PKR)</th>
          </tr>
        </thead>
        <tbody>
          @forelse($report['rows'] as $r)
          <tr>
            <td class="font-monospace fw-bold text-primary">
              <a href="{{ route('accounting.account-ledger', $r['account_id']) }}" class="text-decoration-none">
                {{ $r['code'] }}
              </a>
            </td>
            <td>
              <a href="{{ route('accounting.account-ledger', $r['account_id']) }}" class="text-dark fw-semibold text-decoration-none">
                {{ $r['name'] }}
              </a>
            </td>
            <td>
              <span class="badge bg-light text-secondary border text-uppercase" style="font-size: 10px;">{{ $r['type'] }}</span>
            </td>
            <td class="text-end font-monospace {{ $r['debit'] > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
              {{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '-' }}
            </td>
            <td class="text-end font-monospace {{ $r['credit'] > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
              {{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '-' }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center py-4 text-muted">No accounting transactions recorded up to this date.</td>
          </tr>
          @endforelse
        </tbody>
        <tfoot class="table-light fs-6 fw-bold">
          <tr>
            <td colspan="3" class="text-end">TRIAL BALANCE TOTALS:</td>
            <td class="text-end font-monospace text-primary">PKR {{ number_format($report['total_debit'], 2) }}</td>
            <td class="text-end font-monospace text-primary">PKR {{ number_format($report['total_credit'], 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</x-app-layout>
