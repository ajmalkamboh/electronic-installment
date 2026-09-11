<x-app-layout title="Inter-Branch Hardware Transfer">
  <!-- Breadcrumb & Header -->
  <div class="mb-4">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
        <li class="breadcrumb-item"><a href="{{ route('inventory.serialized') }}">Serialized Units</a></li>
        <li class="breadcrumb-item active" aria-current="page">Transfer Hardware</li>
      </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1 class="h3 fw-bold mb-1">Inter-Branch Hardware Transfer</h1>
        <p class="text-muted mb-0">Relocate a physical serialized device from one showroom branch to another.</p>
      </div>
      <a href="{{ route('inventory.serialized') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Units
      </a>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <!-- Device Information Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1 text-dark">
            <i class="bi bi-upc-scan text-primary me-2"></i>Unit to Transfer
          </h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <span class="text-muted small d-block">Product Model</span>
              <h5 class="fw-bold text-dark mb-0">{{ $item->product->brand }} {{ $item->product->model_name }}</h5>
              <span class="text-muted small font-monospace">SKU: {{ $item->product->sku }}</span>
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">Hardware Identifiers</span>
              @if($item->imei_1)
                <div class="font-monospace fw-bold text-dark">IMEI 1: {{ $item->imei_1 }}</div>
              @endif
              @if($item->imei_2)
                <div class="font-monospace text-muted small">IMEI 2: {{ $item->imei_2 }}</div>
              @endif
              @if($item->serial_number)
                <div class="font-monospace text-dark">S/N: {{ $item->serial_number }}</div>
              @endif
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">Current Showroom Branch</span>
              <strong class="text-primary fs-6">{{ $item->branch->name }}</strong>
              <span class="text-muted small font-monospace">({{ $item->branch->code }})</span>
            </div>
            <div class="col-md-6">
              <span class="text-muted small d-block">Current Physical Status</span>
              <span class="badge bg-success text-uppercase">{{ str_replace('_', ' ', $item->status) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Transfer Form Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-1 text-dark">
            <i class="bi bi-arrow-left-right text-success me-2"></i>Transfer Destination & Dispatch Notes
          </h5>
        </div>
        <div class="card-body p-4">
          <form method="POST" action="{{ route('inventory.transfer.store', $item) }}">
            @csrf

            <div class="mb-4">
              <label for="destination_branch_id" class="form-label fw-semibold text-dark">
                Destination Showroom Branch <span class="text-danger">*</span>
              </label>
              <select name="destination_branch_id" id="destination_branch_id"
                      class="form-select form-select-lg @error('destination_branch_id') is-invalid @enderror" required>
                <option value="">Choose receiving branch showroom...</option>
                @foreach($destinationBranches as $branch)
                  <option value="{{ $branch->id }}" {{ old('destination_branch_id') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }} ({{ $branch->code }}) - {{ $branch->city }}
                  </option>
                @endforeach
              </select>
              @error('destination_branch_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label for="notes" class="form-label fw-semibold text-dark">Transfer Notes / Reason</label>
              <textarea name="notes" id="notes" rows="3"
                        class="form-control @error('notes') is-invalid @enderror"
                        placeholder="e.g. Stock replenishment for approved customer application, branch reallocation...">{{ old('notes') }}</textarea>
              @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <a href="{{ route('inventory.serialized') }}" class="btn btn-light px-4">Cancel</a>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i>Execute Showroom Transfer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
