<x-app-layout title="Add New Product">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active" aria-current="page">New Product</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Add Product to Catalog</h1>
        <p class="text-muted mb-0">Define specifications, pricing model, down payment requirements, and IMEI tracking mode.</p>
      </div>
      <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Catalog
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('products.store') }}">
        @csrf

        <!-- Section 1: Classification & Identification -->
        <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-4">
          <i class="bi bi-tag me-1"></i>1. Product Identity & Classification
        </h6>

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label for="brand" class="form-label fw-semibold">Manufacturer Brand <span class="text-danger">*</span></label>
            <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror"
                   placeholder="e.g. Samsung, Haier, Apple, Dawlance, Pel" value="{{ old('brand') }}" required>
            @error('brand')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-5">
            <label for="model_name" class="form-label fw-semibold">Model Name & Number <span class="text-danger">*</span></label>
            <input type="text" name="model_name" id="model_name" class="form-control @error('model_name') is-invalid @enderror"
                   placeholder="e.g. Galaxy S25 Ultra 512GB, 1.5 Ton T3 DC Inverter AC" value="{{ old('model_name') }}" required>
            @error('model_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label for="sku" class="form-label fw-semibold">Internal SKU (Optional)</label>
            <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror"
                   placeholder="Auto-generated if empty" value="{{ old('sku') }}">
            @error('sku')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">Unique per company</small>
          </div>

          <div class="col-md-6">
            <label for="category_id" class="form-label fw-semibold">Product Category <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
              <option value="">Select Category...</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
            @error('category_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="supplier_id" class="form-label fw-semibold">Primary Wholesale Supplier</label>
            <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
              <option value="">Select Primary Supplier (Optional)...</option>
              @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                  {{ $supplier->name }}
                </option>
              @endforeach
            </select>
            @error('supplier_id')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <!-- Section 2: Pricing & Installment Policy -->
        <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-4">
          <i class="bi bi-cash-coin me-1"></i>2. Retail Pricing & Installment Terms
        </h6>

        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label for="base_cash_price" class="form-label fw-semibold">Showroom Cash Retail Price (PKR) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light">Rs.</span>
              <input type="number" step="100" min="500" max="10000000"
                     name="base_cash_price" id="base_cash_price"
                     class="form-control @error('base_cash_price') is-invalid @enderror"
                     placeholder="e.g. 185000" value="{{ old('base_cash_price') }}" required>
            </div>
            @error('base_cash_price')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Standard outright cash purchase price</small>
          </div>

          <div class="col-md-6">
            <label for="min_down_payment_pct" class="form-label fw-semibold">Minimum Down Payment % <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.5" min="0" max="100"
                     name="min_down_payment_pct" id="min_down_payment_pct"
                     class="form-control @error('min_down_payment_pct') is-invalid @enderror"
                     value="{{ old('min_down_payment_pct', 20.00) }}" required>
              <span class="input-group-text bg-light">%</span>
            </div>
            @error('min_down_payment_pct')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <small class="text-muted">Institutional retail floor (standard: 20%)</small>
          </div>
        </div>

        <!-- Section 3: Hardware Tracking Mode & Specifications -->
        <h6 class="fw-bold text-primary text-uppercase small border-bottom pb-2 mb-4">
          <i class="bi bi-upc-scan me-1"></i>3. Serialization & Technical Specifications
        </h6>

        <div class="row g-3 mb-4">
          <div class="col-12">
            <div class="form-check form-switch p-3 border rounded bg-light">
              <input type="hidden" name="is_serialized" value="0">
              <input class="form-check-input ms-0 me-3" type="checkbox" role="switch"
                     name="is_serialized" id="is_serialized" value="1"
                     {{ old('is_serialized', '1') === '1' ? 'checked' : '' }}>
              <label class="form-check-label fw-bold text-dark" for="is_serialized">
                Enable Serialized Hardware Tracking (Mandatory IMEI 1, IMEI 2 & Serial Numbers)
              </label>
              <small class="text-muted d-block mt-1">
                Required for high-value appliances, air conditioners, LED TVs, and smartphones. Each physical item will be assigned a unique serial/IMEI upon showroom receipt.
              </small>
            </div>
          </div>

          <div class="col-md-4">
            <label for="color" class="form-label fw-semibold">Color / Finish</label>
            <input type="text" name="color" id="color" class="form-control"
                   placeholder="e.g. Titanium Black, White, Silver" value="{{ old('color') }}">
          </div>

          <div class="col-md-4">
            <label for="storage" class="form-label fw-semibold">Storage / Capacity</label>
            <input type="text" name="storage" id="storage" class="form-control"
                   placeholder="e.g. 512GB, 1.5 Ton, 550 Liters" value="{{ old('storage') }}">
          </div>

          <div class="col-md-4">
            <label for="ram" class="form-label fw-semibold">RAM / Engine / Voltage</label>
            <input type="text" name="ram" id="ram" class="form-control"
                   placeholder="e.g. 12GB RAM, Inverter, 4K HDR" value="{{ old('ram') }}">
          </div>

          <div class="col-12">
            <label for="description" class="form-label fw-semibold">Description / Showroom Notes</label>
            <textarea name="description" id="description" rows="3" class="form-control"
                      placeholder="Product features, warranty period, official distributor details...">{{ old('description') }}</textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="{{ route('products.index') }}" class="btn btn-light px-4">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check2-circle me-1"></i>Save Product
          </button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
