<x-app-layout title="Showroom Stock Inventory">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Showroom Stock Inventory</h1>
      <p class="text-muted mb-0">Aggregated physical stock levels across all showroom branches and warehouses.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('inventory.movements') }}" class="btn btn-outline-secondary">
        <i class="bi bi-clock-history me-1"></i>Movement Ledger
      </a>
      <a href="{{ route('inventory.serialized') }}" class="btn btn-outline-primary">
        <i class="bi bi-upc-scan me-1"></i>Serialized Units (IMEI)
      </a>
      <a href="{{ route('inventory.receipt.create') }}" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-down me-1"></i>Receive Stock
      </a>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary-subtle text-primary rounded p-3 me-3">
            <i class="bi bi-boxes fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Physical Stock On Hand</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalOnHand }} Units</h4>
            <small class="text-muted">Total physical inventory</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning-subtle text-warning rounded p-3 me-3">
            <i class="bi bi-bookmark-check fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Reserved for Contracts</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalReserved }} Units</h4>
            <small class="text-muted">Pending down payment/delivery</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success-subtle text-success rounded p-3 me-3">
            <i class="bi bi-check-circle fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Net Available for Sale</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalAvailable }} Units</h4>
            <small class="text-muted">Ready for new agreements</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info-subtle text-info rounded p-3 me-3">
            <i class="bi bi-shop fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Showroom Locations</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $branches->count() }} Showrooms</h4>
            <small class="text-muted">Multi-branch network</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('inventory.index') }}" class="row g-3 align-items-center">
        <div class="col-md-5">
          <select name="branch_id" class="form-select">
            <option value="">All Showroom Branches</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                {{ $branch->name }} ({{ $branch->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-5">
          <select name="product_id" class="form-select">
            <option value="">All Product Models</option>
            @foreach($products as $product)
              <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                {{ $product->brand }} {{ $product->model_name }} ({{ $product->sku }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
          @if(request()->hasAny(['branch_id', 'product_id']))
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Showroom Stock Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Showroom Branch</th>
              <th>Product Model</th>
              <th>Category</th>
              <th>Physical On Hand</th>
              <th>Reserved Units</th>
              <th>Available for Sale</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($inventories as $inv)
              <tr>
                <td class="ps-4">
                  <span class="fw-bold text-dark d-block">{{ $inv->branch->name }}</span>
                  <small class="text-muted font-monospace">{{ $inv->branch->code }}</small>
                </td>
                <td>
                  <a href="{{ route('products.show', $inv->product) }}" class="fw-bold text-dark text-decoration-none">
                    {{ $inv->product->brand }} {{ $inv->product->model_name }}
                  </a>
                  <div class="small font-monospace text-muted">SKU: {{ $inv->product->sku }}</div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ $inv->product->category?->name ?? 'General' }}
                  </span>
                </td>
                <td>
                  <strong class="fs-6 text-dark">{{ $inv->quantity_on_hand }}</strong> Units
                </td>
                <td>
                  <span class="text-warning fw-semibold">{{ $inv->quantity_reserved }}</span> Units
                </td>
                <td>
                  <strong class="fs-6 {{ $inv->quantity_available > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $inv->quantity_available }}
                  </strong> Units
                </td>
                <td class="text-end pe-4">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('inventory.serialized', ['branch_id' => $inv->branch_id, 'search' => $inv->product->model_name]) }}"
                       class="btn btn-sm btn-outline-primary" title="View Serialized Items">
                      <i class="bi bi-upc-scan me-1"></i>View Units
                    </a>
                    <a href="{{ route('inventory.receipt.create', ['product_id' => $inv->product_id, 'branch_id' => $inv->branch_id]) }}"
                       class="btn btn-sm btn-outline-success" title="Add Stock">
                      <i class="bi bi-plus-lg"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-boxes fs-1 d-block mb-2 text-secondary"></i>
                  No showroom inventory records found matching your filters.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($inventories->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $inventories->links() }}
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
