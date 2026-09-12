<x-app-layout title="Multi-Branch Inventory Transfers & Gate Passes">
  <!-- Header & Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Showroom Stock Transfers &amp; Gate Passes</h1>
      <p class="text-muted mb-0">Inter-branch appliance reallocation &bull; Driver &amp; vehicle transit tracking &bull; Security gate passes</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('transfers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Transfer Request
      </a>
      <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-upc-scan me-1"></i>Showroom Inventory
      </a>
    </div>
  </div>

  <!-- Operational Metric Cards -->
  <div class="row g-3 mb-4">
    <!-- Active Transfers -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Active Transfers</p>
              <h3 class="fw-bold mb-0 text-primary">{{ number_format($metrics['active_transfers']) }}</h3>
              <small class="text-muted">In-flight &amp; requested consignments</small>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
              <i class="bi bi-arrow-left-right fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- In-Transit Units -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">In-Transit Appliances</p>
              <h3 class="fw-bold mb-0 text-warning">{{ number_format($metrics['in_transit_units']) }} Units</h3>
              <small class="text-muted">Currently in cargo vehicles</small>
            </div>
            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
              <i class="bi bi-truck fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Approval -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Pending Sign-off</p>
              <h3 class="fw-bold mb-0 text-info">{{ number_format($metrics['pending_approval']) }} Orders</h3>
              <small class="text-muted">Awaiting showroom manager approval</small>
            </div>
            <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
              <i class="bi bi-hourglass-split fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Received This Month -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Received This Month</p>
              <h3 class="fw-bold mb-0 text-success">{{ number_format($metrics['received_this_month']) }} Orders</h3>
              <small class="text-muted">Successfully inspected &amp; stocked</small>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
              <i class="bi bi-check2-circle fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('transfers.index') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Showrooms</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Status Filter</label>
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="all">All Statuses</option>
            <option value="requested" {{ $status === 'requested' ? 'selected' : '' }}>Requested</option>
            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="dispatched" {{ $status === 'dispatched' ? 'selected' : '' }}>Dispatched / In Transit</option>
            <option value="received" {{ $status === 'received' ? 'selected' : '' }}>Received</option>
            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Movement View</label>
          <select name="tab" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="all" {{ $tab === 'all' ? 'selected' : '' }}>All Transfers</option>
            <option value="in_transit" {{ $tab === 'in_transit' ? 'selected' : '' }}>Only In Transit (On Road)</option>
            <option value="completed" {{ $tab === 'completed' ? 'selected' : '' }}>Completed Transfers</option>
          </select>
        </div>

        <div class="col-md-2 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($branchId || ($status && $status !== 'all') || ($tab && $tab !== 'all'))
            <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Transfers Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Transfer Orders &amp; Gate Passes</h5>
      <span class="badge bg-light text-dark border">{{ $transfers->total() }} Records</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Transfer Order #</th>
              <th>Origin Showroom</th>
              <th>Destination Showroom</th>
              <th class="text-center">Items</th>
              <th class="text-center">Status</th>
              <th>Gate Pass / Logistics</th>
              <th>Timeline</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($transfers as $t)
              <tr>
                <td>
                  <a href="{{ route('transfers.show', $t) }}" class="fw-bold text-decoration-none">
                    {{ $t->transfer_number }}
                  </a>
                  <div class="text-muted small">Req by {{ $t->creator?->name }}</div>
                </td>
                <td>
                  <div class="fw-semibold">{{ $t->sourceBranch?->name }}</div>
                  <small class="text-muted">{{ $t->sourceBranch?->code }} &bull; {{ $t->sourceBranch?->city }}</small>
                </td>
                <td>
                  <div class="fw-semibold">{{ $t->destinationBranch?->name }}</div>
                  <small class="text-muted">{{ $t->destinationBranch?->code }} &bull; {{ $t->destinationBranch?->city }}</small>
                </td>
                <td class="text-center">
                  <span class="badge bg-light text-dark border fs-6">
                    {{ $t->total_items_count }} {{ Str::plural('Unit', $t->total_items_count) }}
                  </span>
                </td>
                <td class="text-center">
                  {!! $t->status_badge !!}
                </td>
                <td>
                  @if($t->gate_pass_number)
                    <div class="fw-medium text-dark">
                      <i class="bi bi-shield-check text-success me-1"></i>{{ $t->gate_pass_number }}
                    </div>
                    <small class="text-muted">
                      {{ $t->vehicle_number ?? 'No Vehicle' }} &bull; {{ $t->driver_name ?? 'No Driver' }}
                    </small>
                  @else
                    <span class="text-muted small">Awaiting Dispatch</span>
                  @endif
                </td>
                <td>
                  @if($t->received_at)
                    <div class="text-success small fw-semibold">Received {{ $t->received_at->format('d-M-Y') }}</div>
                  @elseif($t->dispatched_at)
                    <div class="text-primary small fw-semibold">Dispatched {{ $t->dispatched_at->format('d-M-Y') }}</div>
                  @else
                    <div class="text-muted small">Created {{ $t->created_at->format('d-M-Y') }}</div>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="{{ route('transfers.show', $t) }}" class="btn btn-outline-primary" title="View Order">
                      <i class="bi bi-eye"></i>
                    </a>
                    @if($t->isGatePassReady())
                      <a href="{{ route('transfers.gate-pass', $t) }}" target="_blank" class="btn btn-outline-success" title="Print Security Gate Pass">
                        <i class="bi bi-printer"></i>
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-5">
                  <i class="bi bi-truck fs-2 text-muted d-block mb-2"></i>
                  No stock transfer orders found matching your criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($transfers->hasPages())
      <div class="card-footer bg-white py-3 border-0">
        {{ $transfers->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
