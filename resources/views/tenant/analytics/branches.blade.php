<x-app-layout title="Multi-Branch Showroom Comparative Leaderboard">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Multi-Branch Showroom Comparative Leaderboard</h1>
      <p class="text-muted mb-0">Cross-branch portfolio risk, delinquency comparison &bull; Month-to-date recovery efficiency ranking</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('analytics.export', ['type' => 'branches']) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Branches CSV
      </a>
      <button onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Leaderboard
      </button>
      <a href="{{ route('analytics.dashboard') }}" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2 me-1"></i>Executive Dashboard
      </a>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Total Network Branches</p>
          <h3 class="fw-bold mb-0 text-primary">{{ count($branches) }} Showrooms</h3>
          <small class="text-muted">Total retail showroom network</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Combined Portfolio</p>
          <h3 class="fw-bold mb-0 text-success">PKR {{ number_format(collect($branches)->sum('total_portfolio'), 0) }}</h3>
          <small class="text-muted">{{ number_format(collect($branches)->sum('active_accounts')) }} Combined Active Accounts</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Network Month Recovery</p>
          <h3 class="fw-bold mb-0 text-dark">PKR {{ number_format(collect($branches)->sum('monthly_collected'), 0) }}</h3>
          <small class="text-muted">Current calendar month cash receipts</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Showrooms Comparative Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Showroom Operational &amp; Risk Metrics</h5>
      <span class="badge bg-light text-dark border">Sorted by Performance</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 5%;">Rank</th>
              <th>Showroom Branch</th>
              <th class="text-center">Active Contracts</th>
              <th class="text-end">Total Outstanding Portfolio</th>
              <th class="text-center">PAR 30 % (Amount)</th>
              <th class="text-center">PAR 90 % (Amount)</th>
              <th class="text-end">Current Month Recovered</th>
              <th class="text-center">Recovery Efficiency</th>
            </tr>
          </thead>
          <tbody>
            @forelse($branches as $index => $b)
              <tr>
                <td class="text-muted fw-bold">
                  @if($index === 0)
                    <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill me-1"></i>#1</span>
                  @elseif($index === 1)
                    <span class="badge bg-secondary"><i class="bi bi-award-fill me-1"></i>#2</span>
                  @elseif($index === 2)
                    <span class="badge bg-light text-dark border">#3</span>
                  @else
                    <span class="text-muted ms-2">#{{ $index + 1 }}</span>
                  @endif
                </td>
                <td>
                  <div class="fw-bold fs-6">{{ $b['branch_name'] }}</div>
                  <small class="text-muted">{{ $b['branch_code'] }} &bull; {{ $b['city'] ?? 'Showroom' }}</small>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark border">{{ number_format($b['active_accounts']) }}</span>
                </td>
                <td class="text-end fw-bold">PKR {{ number_format($b['total_portfolio'], 0) }}</td>
                <td class="text-center">
                  <span class="badge {{ $b['par_30_pct'] > 15 ? 'bg-danger' : ($b['par_30_pct'] > 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                    {{ $b['par_30_pct'] }}%
                  </span>
                  <div class="text-muted small">PKR {{ number_format($b['par_30_amount'], 0) }}</div>
                </td>
                <td class="text-center">
                  <span class="badge {{ $b['par_90_pct'] > 5 ? 'bg-danger' : 'bg-secondary' }}">
                    {{ $b['par_90_pct'] }}%
                  </span>
                  <div class="text-muted small">PKR {{ number_format($b['par_90_amount'], 0) }}</div>
                </td>
                <td class="text-end fw-semibold text-success">PKR {{ number_format($b['monthly_collected'], 0) }}</td>
                <td class="text-center">
                  <span class="badge {{ $b['recovery_efficiency'] >= 85 ? 'bg-success' : ($b['recovery_efficiency'] >= 65 ? 'bg-warning text-dark' : 'bg-danger') }} fs-6">
                    {{ $b['recovery_efficiency'] }}%
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-5">
                  <i class="bi bi-buildings fs-2 text-muted d-block mb-2"></i>
                  No showroom branches registered in this company.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
