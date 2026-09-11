<x-app-layout title="Stock Movements Ledger">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Stock Movements Audit Ledger</h1>
      <p class="text-muted mb-0">Immutable tracking of wholesale receipts, showroom transfers, customer disbursements, and repossession returns.</p>
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

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('inventory.movements') }}" class="row g-3 align-items-center">
        <div class="col-md-5">
          <select name="movement_type" class="form-select">
            <option value="">All Movement Types</option>
            <option value="purchase_receipt" {{ request('movement_type') === 'purchase_receipt' ? 'selected' : '' }}>Wholesale Purchase Receipts</option>
            <option value="branch_transfer_out" {{ request('movement_type') === 'branch_transfer_out' ? 'selected' : '' }}>Branch Transfers (Outbound)</option>
            <option value="branch_transfer_in" {{ request('movement_type') === 'branch_transfer_in' ? 'selected' : '' }}>Branch Transfers (Inbound)</option>
            <option value="sale_disbursement" {{ request('movement_type') === 'sale_disbursement' ? 'selected' : '' }}>Agreement Sale Disbursements</option>
            <option value="repossession_return" {{ request('movement_type') === 'repossession_return' ? 'selected' : '' }}>Repossession Returns</option>
            <option value="manual_adjustment" {{ request('movement_type') === 'manual_adjustment' ? 'selected' : '' }}>Manual Adjustments</option>
          </select>
        </div>
        <div class="col-md-5">
          <select name="product_id" class="form-select">
            <option value="">All Product Models</option>
            @foreach($products as $product)
              <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                {{ $product->brand }} {{ $product->model_name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i>Filter</button>
          @if(request()->hasAny(['movement_type', 'product_id']))
            <a href="{{ route('inventory.movements') }}" class="btn btn-outline-secondary" title="Reset">
              <i class="bi bi-x-circle"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Movements Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Timestamp</th>
              <th>Movement Type</th>
              <th>Product Model</th>
              <th>Hardware Identifier</th>
              <th>Source / Destination</th>
              <th>Authorized Staff</th>
              <th class="text-end pe-4">Ref / Notes</th>
            </tr>
          </thead>
          <tbody>
            @forelse($movements as $m)
              <tr>
                <td class="ps-4 text-muted">
                  <span class="text-dark fw-semibold d-block">{{ $m->created_at->format('d M Y') }}</span>
                  {{ $m->created_at->format('h:i A') }}
                </td>
                <td>
                  @php
                    $typeBadges = [
                      'purchase_receipt' => 'bg-success-subtle text-success',
                      'branch_transfer_out' => 'bg-warning-subtle text-warning',
                      'branch_transfer_in' => 'bg-info-subtle text-info',
                      'sale_disbursement' => 'bg-primary-subtle text-primary',
                      'repossession_return' => 'bg-danger-subtle text-danger',
                      'manual_adjustment' => 'bg-secondary-subtle text-secondary',
                    ];
                  @endphp
                  <span class="badge {{ $typeBadges[$m->movement_type] ?? 'bg-secondary' }} text-uppercase">
                    {{ str_replace('_', ' ', $m->movement_type) }}
                  </span>
                </td>
                <td>
                  <strong class="text-dark">{{ $m->product->brand }} {{ $m->product->model_name }}</strong>
                </td>
                <td>
                  @if($m->serializedItem)
                    <span class="font-monospace text-dark">{{ $m->serializedItem->identifier_label }}</span>
                  @else
                    <span class="text-muted">Quantity: {{ $m->quantity }}</span>
                  @endif
                </td>
                <td>
                  @if($m->sourceBranch && $m->destinationBranch)
                    <span>{{ $m->sourceBranch->name }} &rarr; <strong>{{ $m->destinationBranch->name }}</strong></span>
                  @elseif($m->destinationBranch)
                    <span>Intake: <strong>{{ $m->destinationBranch->name }}</strong></span>
                  @elseif($m->sourceBranch)
                    <span>Outbound: <strong>{{ $m->sourceBranch->name }}</strong></span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>{{ $m->user->name }}</td>
                <td class="text-end pe-4">
                  @if($m->reference_number)
                    <span class="badge bg-light text-dark font-monospace border">{{ $m->reference_number }}</span>
                  @endif
                  @if($m->notes)
                    <small class="text-muted d-block">{{ Str::limit($m->notes, 30) }}</small>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary"></i>
                  No stock movements recorded yet.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($movements->hasPages())
        <div class="px-4 py-3 border-top">
          {{ $movements->links() }}
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
