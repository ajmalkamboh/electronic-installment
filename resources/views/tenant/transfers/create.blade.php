<x-app-layout title="Create Showroom Stock Transfer Request">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Create Showroom Stock Transfer Order</h1>
      <p class="text-muted mb-0">Initiate inter-branch inventory transfer and request dispatch sign-off</p>
    </div>
    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back to Transfers
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-4">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('transfers.store') }}" id="transferForm">
    @csrf

    <div class="row g-4">
      <!-- Origin & Destination Showrooms -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Showroom Routing</h5>
          </div>
          <div class="card-body">
            <!-- Source Branch -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Origin Showroom (Source Branch) <span class="text-danger">*</span></label>
              <select name="source_branch_id" id="sourceBranchSelect" class="form-select" onchange="window.location.href='{{ route('transfers.create') }}?source_branch_id=' + this.value" required>
                @foreach($branches as $b)
                  <option value="{{ $b->id }}" {{ $sourceBranchId == $b->id ? 'selected' : '' }}>
                    {{ $b->name }} ({{ $b->code }}) &bull; {{ $b->city }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted">Stock will be dispatched from this branch.</small>
            </div>

            <!-- Destination Branch -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Destination Showroom (Receiving Branch) <span class="text-danger">*</span></label>
              <select name="destination_branch_id" class="form-select" required>
                <option value="">Select Receiving Showroom...</option>
                @foreach($branches as $b)
                  @if($b->id != $sourceBranchId)
                    <option value="{{ $b->id }}" {{ old('destination_branch_id') == $b->id ? 'selected' : '' }}>
                      {{ $b->name }} ({{ $b->code }}) &bull; {{ $b->city }}
                    </option>
                  @endif
                @endforeach
              </select>
              <small class="text-muted">Showroom that will inspect and intake the appliances.</small>
            </div>

            <!-- Notes -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Transfer Purpose / Notes</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Urgent customer booking request for Haier Inverter AC; showroom replenishment...">{{ old('notes') }}</textarea>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm border-start border-primary border-4">
          <div class="card-body">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Transfer &amp; Security Process</h6>
            <ol class="small text-muted ps-3 mb-0">
              <li class="mb-1">Submit request for showroom manager sign-off.</li>
              <li class="mb-1">Once approved, dispatch with driver &amp; vehicle details.</li>
              <li class="mb-1">System automatically generates an official Security Gate Pass.</li>
              <li>Receiving branch verifies IMEIs/serials upon physical arrival.</li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Item Selection from Source Showroom -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
              <h5 class="fw-bold mb-0"><i class="bi bi-boxes me-2 text-primary"></i>Available Stock at Origin</h5>
              <small class="text-muted">Select serialized units or enter bulk quantities</small>
            </div>
            <span class="badge bg-light text-dark border">{{ $availableItems->count() }} Available Units</span>
          </div>
          <div class="card-body p-0">
            @if($availableItems->isEmpty())
              <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                No serialized appliances are currently in stock at the selected origin showroom.
                <div class="mt-2">
                  <a href="{{ route('inventory.receipt.create') }}" class="btn btn-sm btn-outline-primary">Receive Stock into Origin</a>
                </div>
              </div>
            @else
              <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th style="width: 5%;">Select</th>
                      <th>Appliance / Hardware Model</th>
                      <th>Serial / IMEI Identifier</th>
                      <th class="text-end">Cost Basis</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($availableItems as $idx => $item)
                      <tr>
                        <td class="text-center">
                          <input type="checkbox" name="items[{{ $idx }}][serialized_item_id]" value="{{ $item->id }}" class="form-check-input item-checkbox" id="item_{{ $item->id }}">
                          <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item->product_id }}">
                          <input type="hidden" name="items[{{ $idx }}][quantity]" value="1">
                        </td>
                        <td>
                          <label for="item_{{ $item->id }}" class="mb-0 fw-semibold cursor-pointer">
                            {{ $item->product?->brand }} {{ $item->product?->model_name }}
                          </label>
                          <div class="text-muted small">SKU: {{ $item->product?->sku ?? 'N/A' }}</div>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark border font-monospace">
                            {{ $item->identifier_label }}
                          </span>
                          @if($item->color)
                            <small class="text-muted d-block">Color: {{ $item->color }}</small>
                          @endif
                        </td>
                        <td class="text-end text-muted">
                          @if($item->purchase_cost)
                            PKR {{ number_format($item->purchase_cost, 0) }}
                          @else
                            &mdash;
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
          <div class="card-footer bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <span class="text-muted small" id="selectedCountText">0 items selected</span>
            <button type="submit" class="btn btn-primary" id="submitBtn" {{ $availableItems->isEmpty() ? 'disabled' : '' }}>
              <i class="bi bi-check-circle me-1"></i>Submit Transfer Request
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxes = document.querySelectorAll('.item-checkbox');
      const countText = document.getElementById('selectedCountText');

      function updateCount() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        countText.textContent = checked + ' ' + (checked === 1 ? 'item' : 'items') + ' selected';
      }

      checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
      updateCount();
    });
  </script>
</x-app-layout>
