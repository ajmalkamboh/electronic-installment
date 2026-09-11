<x-app-layout title="Edit Product - {{ $product->brand }} {{ $product->model_name }}">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.show', $product) }}">{{ $product->brand }} {{ $product->model_name }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Product</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Edit Product Details</h1>
        <p class="text-muted mb-0">Modify catalog specifications, pricing, and active status.</p>
      </div>
      <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Product
      </a>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <label for="brand" class="form-label fw-semibold">Manufacturer Brand <span class="text-danger">*</span></label>
            <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror"
                   value="{{ old('brand', $product->brand) }}" required>
            @error('brand')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-5">
            <label for="model_name" class="form-label fw-semibold">Model Name & Number <span class="text-danger">*</span></label>
            <input type="text" name="model_name" id="model_name" class="form-control @error('model_name') is-invalid @enderror"
                   value="{{ old('model_name', $product->model_name) }}" required>
            @error('model_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-3">
            <label for="sku" class="form-label fw-semibold">SKU</label>
            <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror"
                   value="{{ old('sku', $product->sku) }}">
            @error('sku')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="category_id" class="form-label fw-semibold">Product Category <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
              <option value="">None / Direct Procurement</option>
              @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                  {{ $supplier->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label for="base_cash_price" class="form-label fw-semibold">Base Cash Price (PKR) <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light">Rs.</span>
              <input type="number" step="100" min="500" max="10000000"
                     name="base_cash_price" id="base_cash_price"
                     class="form-control @error('base_cash_price') is-invalid @enderror"
                     value="{{ old('base_cash_price', $product->base_cash_price) }}" required>
            </div>
            @error('base_cash_price')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-md-6">
            <label for="min_down_payment_pct" class="form-label fw-semibold">Min Down Payment % <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" step="0.5" min="0" max="100"
                     name="min_down_payment_pct" id="min_down_payment_pct"
                     class="form-control @error('min_down_payment_pct') is-invalid @enderror"
                     value="{{ old('min_down_payment_pct', $product->min_down_payment_pct) }}" required>
              <span class="input-group-text bg-light">%</span>
            </div>
            @error('min_down_payment_pct')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="col-12">
            <label for="description" class="form-label fw-semibold">Description</label>
            <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
          </div>

          <div class="col-12">
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ $product->is_active ? 'checked' : '' }}>
              <label class="form-check-label fw-semibold" for="is_active">Active in Catalog & Showrooms</label>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="{{ route('products.show', $product) }}" class="btn btn-light px-4">Cancel</a>
          <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
