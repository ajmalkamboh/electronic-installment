<x-app-layout title="Product Catalog">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Product Master Catalog</h1>
      <p class="text-muted mb-0">Manage financed electronics, retail pricing, down payment requirements, and IMEI tracking.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-tags me-1"></i>Categories
      </a>
      <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-building me-1"></i>Suppliers
      </a>
      <a href="{{ route('inventory.receipt.create') }}" class="btn btn-outline-success">
        <i class="bi bi-box-arrow-in-down me-1"></i>Receive Stock
      </a>
      <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add Product
      </a>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary-subtle text-primary rounded p-3 me-3">
            <i class="bi bi-box-seam fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Catalog Products</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalProducts }}</h4>
            <small class="text-muted">Master SKU models</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success-subtle text-success rounded p-3 me-3">
            <i class="bi bi-upc-scan fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Serialized Tracking</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $serializedCount }}</h4>
            <small class="text-muted">IMEI / Serial numbered</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning-subtle text-warning rounded p-3 me-3">
            <i class="bi bi-tags fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Active Categories</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $categories->count() }}</h4>
            <small class="text-muted">Smartphones, ACs, TVs...</small>
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
            <span class="text-muted small fw-semibold">Showroom Inventory</span>
            <h4 class="fw-bold mb-0 text-dark">
              <a href="{{ route('inventory.index') }}" class="text-decoration-none text-dark">Manage &rarr;</a>
            </h4>
            <small class="text-muted">Physical stock overview</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('products.index') }}" class="row g-3 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control bg-light border-start-0 ps-0"
                   placeholder="Search by brand, model name, or SKU..."
                   value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="category_id" class="form-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="is_serialized" class="form-select">
            <option value="">All Tracking Modes</option>
            <option value="1" {{ request('is_serialized') === '1' ? 'selected' : '' }}>Serialized (IMEI)</option>
            <option value="0" {{ request('is_serialized') === '0' ? 'selected' : '' }}>Non-Serialized</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
          @if(request()->hasAny(['search', 'category_id', 'is_serialized']))
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Reset">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Products Catalog Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Product / Model</th>
              <th>Category</th>
              <th>Base Cash Price</th>
              <th>Min Down Payment</th>
              <th>Tracking Mode</th>
              <th>Total Stock On Hand</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($products as $product)
              <tr>
                <td class="ps-4">
                  <a href="{{ route('products.show', $product) }}" class="fw-bold text-dark text-decoration-none">
                    {{ $product->brand }} {{ $product->model_name }}
                  </a>
                  <div class="small font-monospace text-muted">SKU: {{ $product->sku }}</div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <i class="bi {{ $product->category?->icon ?: 'bi-box-seam' }} me-1"></i>{{ $product->category?->name ?? 'Uncategorized' }}
                  </span>
                </td>
                <td>
                  <strong class="text-dark">Rs. {{ number_format($product->base_cash_price) }}</strong>
                </td>
                <td>
                  <span class="badge bg-info-subtle text-info fw-bold">
                    {{ $product->min_down_payment_pct }}%
                  </span>
                  <small class="text-muted d-block mt-1">
                    Rs. {{ number_format($product->base_cash_price * ($product->min_down_payment_pct / 100)) }}
                  </small>
                </td>
                <td>
                  @if($product->is_serialized)
                    <span class="badge bg-primary-subtle text-primary">
                      <i class="bi bi-upc-scan me-1"></i>IMEI / Serial
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary">Non-Serialized</span>
                  @endif
                </td>
                <td>
                  <span class="fw-bold fs-6 {{ $product->total_stock_on_hand > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $product->total_stock_on_hand }} Units
                  </span>
                  <small class="text-muted d-block">{{ $product->total_stock_available }} Available</small>
                </td>
                <td class="text-end pe-4">
                  <div class="d-flex justify-content-end gap-1">
                    <a href="{{ route('inventory.receipt.create', ['product_id' => $product->id]) }}"
                       class="btn btn-sm btn-outline-success" title="Receive Stock">
                      <i class="bi bi-box-arrow-in-down"></i>
                    </a>
                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-primary" title="View Dossier">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-secondary" title="Edit Product">
                      <i class="bi bi-pencil"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-boxes fs-1 d-block mb-2 text-secondary"></i>
                  No catalog products found. Click "Add Product" to create your first merchandise model.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($products->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $products->links() }}
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
