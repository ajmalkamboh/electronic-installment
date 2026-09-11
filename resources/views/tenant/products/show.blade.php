<x-app-layout title="{{ $product->brand }} {{ $product->model_name }} - Product Dossier">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $product->brand }} {{ $product->model_name }}</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <h1 class="h3 fw-bold mb-0">{{ $product->brand }} {{ $product->model_name }}</h1>
          <span class="badge bg-light text-dark border font-monospace">SKU: {{ $product->sku }}</span>
          @if($product->is_serialized)
            <span class="badge bg-primary-subtle text-primary"><i class="bi bi-upc-scan me-1"></i>IMEI Serialized</span>
          @endif
        </div>
        <p class="text-muted mb-0">
          Category: <strong>{{ $product->category?->name ?? 'Uncategorized' }}</strong> &bull;
          Supplier: <strong>{{ $product->supplier?->name ?? 'Direct' }}</strong>
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('inventory.receipt.create', ['product_id' => $product->id]) }}" class="btn btn-outline-success">
          <i class="bi bi-box-arrow-in-down me-1"></i>Receive Wholesale Stock
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary">
          <i class="bi bi-pencil me-1"></i>Edit Specifications
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
          <i class="bi bi-arrow-left me-1"></i>Catalog
        </a>
      </div>
    </div>
  </div>

  <!-- Pricing & Stock Metrics -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Base Cash Retail Price</span>
          <h3 class="fw-bold mb-0 text-dark">Rs. {{ number_format($product->base_cash_price) }}</h3>
          <small class="text-muted d-block mt-2">Standard Showroom Price</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Min Down Payment Required</span>
          <h3 class="fw-bold mb-0 text-primary">{{ $product->min_down_payment_pct }}%</h3>
          <small class="text-muted d-block mt-2">
            Minimum: Rs. {{ number_format($product->base_cash_price * ($product->min_down_payment_pct / 100)) }}
          </small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Total Showroom Units</span>
          <h3 class="fw-bold mb-0 {{ $product->total_stock_on_hand > 0 ? 'text-success' : 'text-danger' }}">
            {{ $product->total_stock_on_hand }} <span class="fs-6 fw-normal text-muted">Units On Hand</span>
          </h3>
          <small class="text-muted d-block mt-2">{{ $product->total_stock_available }} Available for Contract</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <span class="text-muted small fw-semibold d-block mb-1">Reserved Units</span>
          <h3 class="fw-bold mb-0 text-warning">
            {{ $product->total_stock_on_hand - $product->total_stock_available }} <span class="fs-6 fw-normal text-muted">Pending Delivery</span>
          </h3>
          <small class="text-muted d-block mt-2">Allocated to approved agreements</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Showroom Stock Distribution -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
      <h5 class="fw-bold mb-0 text-dark">
        <i class="bi bi-shop text-primary me-2"></i>Showroom Stock Distribution Breakdown
      </h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Branch Showroom</th>
              <th>Showroom Code</th>
              <th>Physical On Hand</th>
              <th>Reserved Pending Contract</th>
              <th>Net Available for Sale</th>
              <th class="text-end pe-4">Availability</th>
            </tr>
          </thead>
          <tbody>
            @forelse($product->branchInventories as $inventory)
              <tr>
                <td class="ps-4 fw-bold text-dark">{{ $inventory->branch->name }}</td>
                <td class="font-monospace text-muted">{{ $inventory->branch->code }}</td>
                <td><strong class="text-dark">{{ $inventory->quantity_on_hand }}</strong> Units</td>
                <td><span class="text-warning fw-semibold">{{ $inventory->quantity_reserved }}</span> Units</td>
                <td>
                  <strong class="{{ $inventory->quantity_available > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $inventory->quantity_available }}
                  </strong> Units
                </td>
                <td class="text-end pe-4">
                  @if($inventory->quantity_available > 2)
                    <span class="badge bg-success-subtle text-success">In Stock</span>
                  @elseif($inventory->quantity_available > 0)
                    <span class="badge bg-warning-subtle text-warning">Low Stock</span>
                  @else
                    <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                  No stock received in any showroom branch yet.
                  <a href="{{ route('inventory.receipt.create', ['product_id' => $product->id]) }}" class="ms-1 fw-semibold">Receive Stock Now &rarr;</a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Physical Serialized Hardware Units -->
  @if($product->is_serialized)
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
          <i class="bi bi-upc-scan text-primary me-2"></i>Physical Hardware Units (IMEI / Serial Numbers)
        </h5>
        <span class="badge bg-light text-dark fs-6">{{ $serializedItems->total() }} Total Units</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
              <tr>
                <th class="ps-4">Identifiers (IMEI 1 / Serial)</th>
                <th>Secondary IMEI</th>
                <th>Showroom Location</th>
                <th>Color / Variant</th>
                <th>Wholesale Cost</th>
                <th>Physical Status</th>
                <th class="text-end pe-4">Transfer / Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($serializedItems as $item)
                <tr>
                  <td class="ps-4">
                    @if($item->imei_1)
                      <div class="font-monospace fw-bold text-dark">IMEI: {{ $item->imei_1 }}</div>
                    @endif
                    @if($item->serial_number)
                      <div class="font-monospace text-dark">S/N: {{ $item->serial_number }}</div>
                    @endif
                    @if($item->asset_tag)
                      <div class="text-muted small">Asset: {{ $item->asset_tag }}</div>
                    @endif
                  </td>
                  <td class="font-monospace text-muted">{{ $item->imei_2 ?: '—' }}</td>
                  <td>
                    <span class="fw-semibold text-dark">{{ $item->branch->name }}</span>
                    <small class="text-muted d-block font-monospace">{{ $item->branch->code }}</small>
                  </td>
                  <td>{{ $item->color ?: 'Standard' }}</td>
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
                  <td class="text-end pe-4">
                    @if($item->status === 'in_stock')
                      <a href="{{ route('inventory.transfer.create', $item) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Transfer Showroom">
                        <i class="bi bi-arrow-left-right me-1"></i>Transfer
                      </a>
                    @else
                      <span class="text-muted small">Locked ({{ $item->status }})</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    No individual serialized units registered in inventory.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($serializedItems->hasPages())
          <div class="px-4 py-3 border-top">
            {{ $serializedItems->links() }}
          </div>
        @endif
      </div>
    </div>
  @endif
</x-app-layout>
