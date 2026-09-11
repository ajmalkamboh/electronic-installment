<x-app-layout title="Serialized Hardware Units">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Serialized Hardware Units</h1>
      <p class="text-muted mb-0">Track physical devices by Dual-SIM IMEI numbers, factory serials, and showroom locations.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-shop me-1"></i>Showroom Levels
      </a>
      <a href="{{ route('inventory.receipt.create') }}" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-down me-1"></i>Receive New Stock
      </a>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('inventory.serialized') }}" class="row g-3 align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 ps-0"
                   placeholder="Search by IMEI 1, IMEI 2, Serial, Asset Tag, or Model..."
                   value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="branch_id" class="form-select">
            <option value="">All Showroom Locations</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                {{ $b->name }} ({{ $b->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Physical Statuses</option>
            <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock (Sellable)</option>
            <option value="reserved" {{ request('status') === 'reserved' ? 'selected' : '' }}>Reserved (Pending Down Payment)</option>
            <option value="allocated" {{ request('status') === 'allocated' ? 'selected' : '' }}>Allocated to Agreement</option>
            <option value="disbursed" {{ request('status') === 'disbursed' ? 'selected' : '' }}>Disbursed (Delivered)</option>
            <option value="repossessed" {{ request('status') === 'repossessed' ? 'selected' : '' }}>Repossessed</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
          @if(request()->hasAny(['search', 'branch_id', 'status']))
            <a href="{{ route('inventory.serialized') }}" class="btn btn-outline-secondary" title="Reset">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Serialized Items Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Identifier (IMEI 1 / Serial)</th>
              <th>Secondary IMEI</th>
              <th>Product Model</th>
              <th>Showroom Location</th>
              <th>Wholesale Cost</th>
              <th>Physical Status</th>
              <th>Received Date</th>
              <th class="text-end pe-4">Transfer / Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              <tr>
                <td class="ps-4">
                  @if($item->imei_1)
                    <div class="font-monospace fw-bold text-dark">
                      <i class="bi bi-phone me-1 text-muted"></i>{{ $item->imei_1 }}
                    </div>
                  @endif
                  @if($item->serial_number)
                    <div class="font-monospace text-dark">
                      <i class="bi bi-upc me-1 text-muted"></i>{{ $item->serial_number }}
                    </div>
                  @endif
                  @if($item->asset_tag)
                    <small class="text-muted d-block font-monospace">Tag: {{ $item->asset_tag }}</small>
                  @endif
                </td>
                <td class="font-monospace text-muted">{{ $item->imei_2 ?: '—' }}</td>
                <td>
                  <a href="{{ route('products.show', $item->product) }}" class="fw-bold text-dark text-decoration-none">
                    {{ $item->product->brand }} {{ $item->product->model_name }}
                  </a>
                  <div class="small text-muted">{{ $item->color ?: 'Standard' }}</div>
                </td>
                <td>
                  <span class="fw-semibold text-dark">{{ $item->branch->name }}</span>
                  <small class="text-muted d-block font-monospace">{{ $item->branch->code }}</small>
                </td>
                <td>
                  {{ $item->purchase_cost ? 'Rs. ' . number_format($item->purchase_cost) : '—' }}
                </td>
                <td>
                  @php
                    $statusClasses = [
                      'in_stock' => 'bg-success',
                      'reserved' => 'bg-warning text-dark',
                      'allocated' => 'bg-info text-dark',
                      'disbursed' => 'bg-primary',
                      'repossessed' => 'bg-danger',
                    ];
                  @endphp
                  <span class="badge {{ $statusClasses[$item->status] ?? 'bg-secondary' }} text-uppercase">
                    {{ str_replace('_', ' ', $item->status) }}
                  </span>
                </td>
                <td class="small text-muted">
                  {{ $item->received_at?->format('d M Y') ?? '—' }}
                </td>
                <td class="text-end pe-4">
                  @if($item->status === 'in_stock')
                    <a href="{{ route('inventory.transfer.create', $item) }}" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-arrow-left-right me-1"></i>Transfer
                    </a>
                  @else
                    <span class="badge bg-light text-muted border">Locked</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-upc-scan fs-1 d-block mb-2 text-secondary"></i>
                  No serialized hardware units found matching your filters.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($items->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $items->links() }}
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
