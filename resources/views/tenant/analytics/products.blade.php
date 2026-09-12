<x-app-layout title="Appliance Category & Brand Analytics">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Appliance Category &amp; Brand Analytics</h1>
      <p class="text-muted mb-0">Product category installment volume, average ticket size, and default rate distribution</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <form method="GET" action="{{ route('analytics.products') }}" class="d-flex gap-2 align-items-center">
        <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">All Branches (Consolidated)</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
          @endforeach
        </select>
        @if($branchId)
          <a href="{{ route('analytics.products') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        @endif
      </form>
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print
      </button>
      <a href="{{ route('analytics.dashboard') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-speedometer2 me-1"></i>Dashboard
      </a>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Appliance Categories</p>
          <h3 class="fw-bold mb-0 text-primary">{{ count($categories) }} Categories</h3>
          <small class="text-muted">Total product lines offered on installment</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Cumulative Financed Volume</p>
          <h3 class="fw-bold mb-0 text-success">PKR {{ number_format(collect($categories)->sum('total_financed_volume'), 0) }}</h3>
          <small class="text-muted">{{ number_format(collect($categories)->sum('units_sold')) }} Total Appliance Units Sold</small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
        <div class="card-body">
          <p class="text-muted small fw-semibold text-uppercase mb-1">Active Appliance Contracts</p>
          <h3 class="fw-bold mb-0 text-dark">{{ number_format(collect($categories)->sum('active_contracts')) }} Active</h3>
          <small class="text-muted">{{ number_format(collect($categories)->sum('defaulted_contracts')) }} Defaulted Units</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Appliance Categories Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Category Installment Performance &amp; Default Exposure</h5>
      <span class="badge bg-light text-dark border">Sorted by Financed Volume</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Appliance Category</th>
              <th class="text-center">Units Sold</th>
              <th class="text-end">Financed Sales Volume</th>
              <th class="text-end">Avg Ticket Size</th>
              <th class="text-center">Active Contracts</th>
              <th class="text-center">Defaulted Units</th>
              <th class="text-center">Default Rate %</th>
            </tr>
          </thead>
          <tbody>
            @forelse($categories as $cat)
              <tr>
                <td>
                  <div class="fw-bold fs-6">{{ $cat['category_name'] }}</div>
                  <small class="text-muted">Code: {{ $cat['code'] ?? 'N/A' }}</small>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark border fs-6">{{ number_format($cat['units_sold']) }}</span>
                </td>
                <td class="text-end fw-bold text-dark">
                  PKR {{ number_format($cat['total_financed_volume'], 0) }}
                </td>
                <td class="text-end text-muted">
                  PKR {{ number_format($cat['average_ticket_size'], 0) }}
                </td>
                <td class="text-center">
                  <span class="badge bg-success bg-opacity-10 text-success">{{ number_format($cat['active_contracts']) }}</span>
                </td>
                <td class="text-center">
                  @if($cat['defaulted_contracts'] > 0)
                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">{{ number_format($cat['defaulted_contracts']) }}</span>
                  @else
                    <span class="badge bg-light text-muted">0</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge {{ $cat['default_rate_pct'] > 10 ? 'bg-danger' : ($cat['default_rate_pct'] > 0 ? 'bg-warning text-dark' : 'bg-success') }} fs-6">
                    {{ $cat['default_rate_pct'] }}%
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-5">
                  <i class="bi bi-box-seam fs-2 text-muted d-block mb-2"></i>
                  No appliance categories or installment sales registered yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
