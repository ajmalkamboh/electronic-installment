<x-app-layout title="Receive Wholesale Stock">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
        <li class="breadcrumb-item active" aria-current="page">Receive Stock</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Receive Wholesale Stock Intake</h1>
        <p class="text-muted mb-0">Record shipment receipts, assign IMEIs / Serial Numbers, and increment showroom on-hand quantities.</p>
      </div>
      <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Inventory
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('inventory.receipt.store') }}" id="receiptForm">
        @csrf

        <!-- Section 1: Intake Metadata -->
        <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-4">
          <i class="bi bi-geo-alt me-1"></i>1. Destination Showroom & Product Line
        </h6>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="branch_id" class="form-label fw-semibold">Receiving Showroom Branch <span class="text-danger">*</span></label>
            <select name="branch_id" id="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
              <option value="">Select Showroom...</option>
              @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ old('branch_id', request('branch_id')) == $b->id ? 'selected' : '' }}>
                  {{ $b->name }} ({{ $b->code }})
                </option>
              @endforeach
            </select>
            @error('branch_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="product_id" class="form-label fw-semibold">Product Model <span class="text-danger">*</span></label>
            <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
              <option value="">Select Product...</option>
              @foreach($products as $p)
                <option value="{{ $p->id }}" {{ old('product_id', $selectedProduct?->id) == $p->id ? 'selected' : '' }}>
                  {{ $p->brand }} {{ $p->model_name }} (SKU: {{ $p->sku }}) - Rs. {{ number_format($p->base_cash_price) }}
                </option>
              @endforeach
            </select>
            @error('product_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="supplier_id" class="form-label fw-semibold">Wholesale Supplier</label>
            <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
              <option value="">Direct / Showroom Stock Purchase</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                  {{ $s->name }}
                </option>
              @endforeach
            </select>
            @error('supplier_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-4">
            <label for="purchase_cost" class="form-label fw-semibold">Wholesale Unit Cost (PKR)</label>
            <div class="input-group">
              <span class="input-group-text bg-light">Rs.</span>
              <input type="number" step="100" min="0" max="10000000"
                     name="purchase_cost" id="purchase_cost"
                     class="form-control @error('purchase_cost') is-invalid @enderror"
                     placeholder="e.g. 140000" value="{{ old('purchase_cost') }}">
            </div>
            @error('purchase_cost')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Procurement cost basis per unit</small>
          </div>

          <div class="col-md-4">
            <label for="reference_number" class="form-label fw-semibold">Delivery Challan / Invoice #</label>
            <input type="text" name="reference_number" id="reference_number"
                   class="form-control @error('reference_number') is-invalid @enderror"
                   placeholder="e.g. PO-84920, DC-1049" value="{{ old('reference_number') }}">
            @error('reference_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <!-- Section 2: Barcode / IMEI Fast Scanner Input -->
        <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-4">
          <i class="bi bi-upc-scan me-1"></i>2. Hardware Identifiers Entry (Barcode & IMEI Scanner)
        </h6>

        <div class="alert alert-info small mb-3">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Fast Scanner Mode:</strong> Use your physical handheld barcode scanner to scan units directly into the box below.
          Scan one IMEI or Serial Number per line. For Dual-SIM handsets, scan or type: <code>IMEI1,IMEI2</code> on each line.
        </div>

        <div class="mb-4">
          <label for="bulk_imei_input" class="form-label fw-semibold text-dark">
            Bulk Scan / Paste Box (One Device per Line)
          </label>
          <textarea name="bulk_imei_input" id="bulk_imei_input" rows="6"
                    class="form-control font-monospace @error('bulk_imei_input') is-invalid @enderror"
                    placeholder="352819001234567&#10;352819001234568,352819001234569&#10;AC-HAIER-2026-98124">{{ old('bulk_imei_input') }}</textarea>
          @error('bulk_imei_input')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted">Supports 15-digit IMEIs, Dual IMEIs (comma-separated), and factory serial numbers.</small>
            <small class="fw-semibold text-primary" id="scannedCountBadge">0 units detected</small>
          </div>
        </div>

        <!-- Section 3: Optional Manual Detailed Row Entry -->
        <div class="accordion mb-4" id="detailedAccordion">
          <div class="accordion-item border">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold text-dark py-2" type="button" data-bs-toggle="collapse" data-bs-target="#detailedCollapse">
                <i class="bi bi-list-ul me-2"></i>Or Add Devices with Colors & Custom Asset Tags
              </button>
            </h2>
            <div id="detailedCollapse" class="accordion-collapse collapse">
              <div class="accordion-body">
                <div id="manualRowsContainer">
                  <div class="row g-2 mb-2 manual-row">
                    <div class="col-md-3">
                      <input type="text" name="items[0][imei_1]" class="form-control form-control-sm font-monospace" placeholder="Primary IMEI 1">
                    </div>
                    <div class="col-md-3">
                      <input type="text" name="items[0][imei_2]" class="form-control form-control-sm font-monospace" placeholder="Secondary IMEI 2">
                    </div>
                    <div class="col-md-2">
                      <input type="text" name="items[0][serial_number]" class="form-control form-control-sm font-monospace" placeholder="Serial Number">
                    </div>
                    <div class="col-md-2">
                      <input type="text" name="items[0][color]" class="form-control form-control-sm" placeholder="Color Variant">
                    </div>
                    <div class="col-md-2">
                      <input type="text" name="items[0][asset_tag]" class="form-control form-control-sm" placeholder="Asset Tag">
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="addRowBtn">
                  <i class="bi bi-plus me-1"></i>Add Row
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="{{ route('inventory.index') }}" class="btn btn-light px-4">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-box-arrow-in-down me-1"></i>Receive & Commit to Showroom
          </button>
        </div>
      </form>
    </div>
  </div>

  @push('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const textarea = document.getElementById('bulk_imei_input');
      const countBadge = document.getElementById('scannedCountBadge');

      function updateCount() {
        const text = textarea.value.trim();
        if (!text) {
          countBadge.innerText = '0 units detected';
          return;
        }
        const lines = text.split(/\r?\n/).filter(line => line.trim().length > 0);
        countBadge.innerText = lines.length + ' device(s) ready to intake';
      }

      textarea.addEventListener('input', updateCount);
      updateCount();

      let rowIndex = 1;
      const container = document.getElementById('manualRowsContainer');
      const addRowBtn = document.getElementById('addRowBtn');

      addRowBtn.addEventListener('click', function () {
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 manual-row';
        div.innerHTML = `
          <div class="col-md-3">
            <input type="text" name="items[${rowIndex}][imei_1]" class="form-control form-control-sm font-monospace" placeholder="Primary IMEI 1">
          </div>
          <div class="col-md-3">
            <input type="text" name="items[${rowIndex}][imei_2]" class="form-control form-control-sm font-monospace" placeholder="Secondary IMEI 2">
          </div>
          <div class="col-md-2">
            <input type="text" name="items[${rowIndex}][serial_number]" class="form-control form-control-sm font-monospace" placeholder="Serial Number">
          </div>
          <div class="col-md-2">
            <input type="text" name="items[${rowIndex}][color]" class="form-control form-control-sm" placeholder="Color Variant">
          </div>
          <div class="col-md-2">
            <input type="text" name="items[${rowIndex}][asset_tag]" class="form-control form-control-sm" placeholder="Asset Tag">
          </div>
        `;
        container.appendChild(div);
        rowIndex++;
      });
    });
  </script>
  @endpush
</x-app-layout>
