<x-app-layout title="Account Ledger - {{ $account->name }}">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="fs-4 fw-bold font-monospace text-primary">[{{ $account->code }}]</span>
        <h1 class="h3 fw-bold mb-0">{{ $account->name }}</h1>
        {!! $account->type_badge !!}
      </div>
      <p class="text-muted mb-0">
        Category: <strong>{{ strtoupper(str_replace('_', ' ', $account->category)) }}</strong> &bull;
        Normal Balance: <strong>{{ strtoupper($account->normal_balance) }}</strong> &bull;
        Showroom: {{ $account->branch?->name ?? 'Company Wide' }}
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Statement
      </button>
      <a href="{{ route('accounting.coa') }}" class="btn btn-outline-primary">
        <i class="bi bi-diagram-3 me-1"></i>Chart of Accounts
      </a>
      <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calculator me-1"></i>Trial Balance
      </a>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.account-ledger', $account) }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Statement Start Date</label>
          <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Statement End Date</label>
          <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-4 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Apply Date Filter</button>
          @if($startDate != date('Y-m-01') || $endDate != date('Y-m-d'))
            <a href="{{ route('accounting.account-ledger', $account) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Account Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-secondary">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Opening Balance (as of {{ \Carbon\Carbon::parse($startDate)->format('d-M-Y') }})</span>
          <span class="fs-4 fw-bold text-dark">PKR {{ number_format($ledger['opening_balance'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Period Debits</span>
          <span class="fs-4 fw-bold text-primary">PKR {{ number_format($ledger['period_debit'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Period Credits</span>
          <span class="fs-4 fw-bold text-dark">PKR {{ number_format($ledger['period_credit'], 2) }}</span>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Closing Balance</span>
          <span class="fs-4 fw-bold text-success">PKR {{ number_format($ledger['closing_balance'], 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Running Ledger Statement Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Running Statement of Account</h5>
      <span class="badge bg-light text-dark border">{{ count($ledger['rows']) }} Transactions</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 13px;">
        <thead class="table-light">
          <tr>
            <th style="width: 110px;">Date</th>
            <th style="width: 160px;">Entry #</th>
            <th>Description &amp; Transaction Details</th>
            <th>Memo</th>
            <th class="text-end" style="width: 140px;">Debit (PKR)</th>
            <th class="text-end" style="width: 140px;">Credit (PKR)</th>
            <th class="text-end" style="width: 160px;">Running Balance (PKR)</th>
          </tr>
        </thead>
        <tbody>
          <tr class="table-light text-muted">
            <td>{{ \Carbon\Carbon::parse($startDate)->format('d-M-Y') }}</td>
            <td colspan="3"><strong>OPENING BALANCE FORWARD</strong></td>
            <td class="text-end">-</td>
            <td class="text-end">-</td>
            <td class="text-end font-monospace fw-bold text-dark">PKR {{ number_format($ledger['opening_balance'], 2) }}</td>
          </tr>
          @forelse($ledger['rows'] as $r)
          <tr>
            <td class="small">{{ $r['date'] }}</td>
            <td class="font-monospace fw-bold text-primary">{{ $r['entry_number'] }}</td>
            <td>
              <div class="fw-semibold text-dark">{{ $r['description'] }}</div>
              <span class="badge bg-light text-secondary border text-uppercase" style="font-size: 9px;">
                {{ str_replace('_', ' ', $r['reference_type']) }}
              </span>
            </td>
            <td class="small text-muted">{{ $r['memo'] ?? '-' }}</td>
            <td class="text-end font-monospace {{ $r['debit'] > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
              {{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '-' }}
            </td>
            <td class="text-end font-monospace {{ $r['credit'] > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
              {{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '-' }}
            </td>
            <td class="text-end font-monospace fw-bold {{ $r['balance'] < 0 ? 'text-danger' : 'text-dark' }}">
              PKR {{ number_format($r['balance'], 2) }}
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No transactions recorded for this account during the specified date range.</td>
          </tr>
          @endforelse
        </tbody>
        <tfoot class="table-light fs-6 fw-bold">
          <tr>
            <td colspan="4" class="text-end">PERIOD TOTALS:</td>
            <td class="text-end font-monospace text-primary">PKR {{ number_format($ledger['period_debit'], 2) }}</td>
            <td class="text-end font-monospace text-primary">PKR {{ number_format($ledger['period_credit'], 2) }}</td>
            <td class="text-end font-monospace text-success">PKR {{ number_format($ledger['closing_balance'], 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</x-app-layout>
