<x-app-layout title="Transfer Order {{ $transfer->transfer_number }}">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">{{ $transfer->transfer_number }}</h1>
        {!! $transfer->status_badge !!}
      </div>
      <p class="text-muted mb-0">
        Initiated by {{ $transfer->creator?->name }} on {{ $transfer->created_at->format('d F, Y \a\t h:i A') }}
      </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
      </a>

      @if($transfer->isGatePassReady())
        <a href="{{ route('transfers.gate-pass', $transfer) }}" target="_blank" class="btn btn-outline-success">
          <i class="bi bi-printer me-1"></i>Print Security Gate Pass
        </a>
      @endif

      @if($transfer->canBeApproved())
        <form method="POST" action="{{ route('transfers.approve', $transfer) }}" class="d-inline" onsubmit="return confirm('Approve this transfer order for showroom dispatch?');">
          @csrf
          <button type="submit" class="btn btn-info text-dark">
            <i class="bi bi-check2-circle me-1"></i>Approve Transfer
          </button>
        </form>
      @endif

      @if($transfer->canBeDispatched())
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#dispatchModal">
          <i class="bi bi-truck me-1"></i>Dispatch &amp; Generate Gate Pass
        </button>
      @endif

      @if($transfer->canBeReceived())
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal">
          <i class="bi bi-box-arrow-in-down me-1"></i>Inspect &amp; Receive Items
        </button>
      @endif

      @if($transfer->canBeCancelled() || $transfer->status === 'dispatched')
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="bi bi-x-circle me-1"></i>Cancel
        </button>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show mb-4" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Progress Tracker -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-4">
      <div class="position-relative m-4">
        <div class="progress" style="height: 4px;">
          @php
            $progressPct = match($transfer->status) {
              'draft', 'requested' => 20,
              'approved' => 50,
              'dispatched' => 75,
              'received' => 100,
              default => 0,
            };
          @endphp
          <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPct }}%;"></div>
        </div>
        <button type="button" class="position-absolute top-0 start-0 translate-middle btn btn-sm {{ in_array($transfer->status, ['requested', 'approved', 'dispatched', 'received']) ? 'btn-success' : 'btn-secondary' }} rounded-pill" style="width: 2rem; height:2rem;">1</button>
        <button type="button" class="position-absolute top-0 start-50 translate-middle btn btn-sm {{ in_array($transfer->status, ['approved', 'dispatched', 'received']) ? 'btn-success' : 'btn-secondary' }} rounded-pill" style="width: 2rem; height:2rem;">2</button>
        <button type="button" class="position-absolute top-0 start-75 translate-middle btn btn-sm {{ in_array($transfer->status, ['dispatched', 'received']) ? 'btn-success' : 'btn-secondary' }} rounded-pill" style="width: 2rem; height:2rem;">3</button>
        <button type="button" class="position-absolute top-0 start-100 translate-middle btn btn-sm {{ $transfer->status === 'received' ? 'btn-success' : 'btn-secondary' }} rounded-pill" style="width: 2rem; height:2rem;">4</button>
      </div>
      <div class="d-flex justify-content-between text-center small text-muted px-2">
        <div style="width: 25%;">
          <div class="fw-bold text-dark">1. Requested</div>
          <div>{{ $transfer->created_at->format('d-M h:i A') }}</div>
        </div>
        <div style="width: 25%;">
          <div class="fw-bold text-dark">2. Approved</div>
          <div>{{ $transfer->approved_by_id ? 'By ' . $transfer->approver?->name : 'Pending' }}</div>
        </div>
        <div style="width: 25%;">
          <div class="fw-bold text-dark">3. Dispatched (In Transit)</div>
          <div>{{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d-M h:i A') : 'Pending' }}</div>
        </div>
        <div style="width: 25%;">
          <div class="fw-bold text-dark">4. Received &amp; Stocked</div>
          <div>{{ $transfer->received_at ? $transfer->received_at->format('d-M h:i A') : 'Pending' }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Routing Details -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0">
          <h5 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Showroom Routing</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-sm-6 border-end">
              <span class="badge bg-light text-muted border text-uppercase mb-2">Origin (Source)</span>
              <h6 class="fw-bold mb-1">{{ $transfer->sourceBranch?->name }}</h6>
              <div class="small text-muted mb-1">{{ $transfer->sourceBranch?->address }}</div>
              <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $transfer->sourceBranch?->phone ?? 'N/A' }}</div>
            </div>
            <div class="col-sm-6">
              <span class="badge bg-light text-muted border text-uppercase mb-2">Destination (Receiving)</span>
              <h6 class="fw-bold mb-1">{{ $transfer->destinationBranch?->name }}</h6>
              <div class="small text-muted mb-1">{{ $transfer->destinationBranch?->address }}</div>
              <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $transfer->destinationBranch?->phone ?? 'N/A' }}</div>
            </div>
          </div>

          @if($transfer->notes)
            <div class="mt-3 p-3 bg-light rounded-3">
              <span class="text-muted small fw-semibold">Transfer Purpose:</span>
              <p class="mb-0 small text-dark">{{ $transfer->notes }}</p>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Logistics & Security Gate Pass -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0"><i class="bi bi-shield-check me-2 text-success"></i>Logistics &amp; Gate Pass</h5>
          @if($transfer->isGatePassReady())
            <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ $transfer->gate_pass_number }}</span>
          @endif
        </div>
        <div class="card-body">
          @if($transfer->gate_pass_number)
            <div class="row g-2 small">
              <div class="col-sm-6">
                <span class="text-muted">Security Gate Pass:</span>
                <div class="fw-bold text-dark fs-6">{{ $transfer->gate_pass_number }}</div>
              </div>
              <div class="col-sm-6">
                <span class="text-muted">Vehicle Registration #:</span>
                <div class="fw-bold text-dark fs-6 font-monospace">{{ $transfer->vehicle_number ?? 'N/A' }}</div>
              </div>
              <div class="col-sm-6">
                <span class="text-muted">Driver Full Name:</span>
                <div class="fw-semibold text-dark">{{ $transfer->driver_name ?? 'N/A' }}</div>
              </div>
              <div class="col-sm-6">
                <span class="text-muted">Driver CNIC / Phone:</span>
                <div class="fw-semibold text-dark">{{ $transfer->driver_cnic ?? 'N/A' }} &bull; {{ $transfer->driver_phone ?? 'N/A' }}</div>
              </div>
              <div class="col-sm-6">
                <span class="text-muted">Carrier / Transporter:</span>
                <div>{{ $transfer->transport_company ?? 'Showroom Transit' }}</div>
              </div>
              <div class="col-sm-6">
                <span class="text-muted">Dispatched By:</span>
                <div>{{ $transfer->dispatcher?->name ?? 'N/A' }}</div>
              </div>
            </div>
          @else
            <div class="text-center py-4 text-muted">
              <i class="bi bi-truck fs-3 d-block mb-1 text-muted"></i>
              Driver and vehicle details will be recorded upon showroom dispatch.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Consignment Items Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Appliance Consignment Ledger</h5>
        <small class="text-muted">Physical hardware units allocated to this transfer order</small>
      </div>
      <span class="badge bg-light text-dark border">{{ $transfer->items->count() }} Total Units</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Appliance Model &amp; Brand</th>
              <th>Category</th>
              <th>Hardware Serial / IMEI</th>
              <th class="text-center">Dispatch Status</th>
              <th class="text-center">Arrival Condition</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            @foreach($transfer->items as $idx => $item)
              <tr>
                <td class="text-muted fw-bold">{{ $idx + 1 }}</td>
                <td>
                  <div class="fw-bold">{{ $item->product?->brand }} {{ $item->product?->model_name }}</div>
                  <small class="text-muted">SKU: {{ $item->product?->sku ?? 'N/A' }}</small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">{{ $item->product?->category?->name ?? 'Appliances' }}</span>
                </td>
                <td>
                  @if($item->serializedItem)
                    <span class="badge bg-light text-dark border font-monospace">
                      {{ $item->serializedItem->identifier_label }}
                    </span>
                    @if($item->serializedItem->color)
                      <small class="text-muted d-block">Color: {{ $item->serializedItem->color }}</small>
                    @endif
                  @else
                    <span class="text-muted small">Bulk Quantity: {{ $item->quantity }}</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($item->status === 'received')
                    <span class="badge bg-success">Received</span>
                  @elseif($item->status === 'dispatched')
                    <span class="badge bg-primary">Dispatched</span>
                  @elseif($item->status === 'rejected')
                    <span class="badge bg-danger">Missing / Rejected</span>
                  @else
                    <span class="badge bg-secondary">Pending</span>
                  @endif
                </td>
                <td class="text-center">
                  {!! $item->condition_badge !!}
                </td>
                <td>
                  <span class="text-muted small">{{ $item->item_notes ?? '&mdash;' }}</span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Dispatch Modal -->
  @if($transfer->canBeDispatched())
    <div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('transfers.dispatch', $transfer) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2 text-primary"></i>Showroom Dispatch &amp; Gate Pass</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">
              Record driver and cargo vehicle details to generate an official Security Gate Pass for showroom exit.
            </p>

            <div class="mb-3">
              <label class="form-label fw-semibold">Driver Full Name <span class="text-danger">*</span></label>
              <input type="text" name="driver_name" class="form-control" placeholder="e.g. Muhammad Aslam" required>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Driver CNIC <span class="text-danger">*</span></label>
                <input type="text" name="driver_cnic" class="form-control" placeholder="35201-1234567-1" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Driver Phone <span class="text-danger">*</span></label>
                <input type="text" name="driver_phone" class="form-control" placeholder="03001234567" required>
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Vehicle Reg Number <span class="text-danger">*</span></label>
                <input type="text" name="vehicle_number" class="form-control text-uppercase" placeholder="LEA-24-9182" required>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Transport Provider</label>
                <input type="text" name="transport_company" class="form-control" placeholder="e.g. Showroom Van, TCS Cargo">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Gate Pass Security Notes</label>
              <textarea name="gate_pass_notes" class="form-control" rows="2" placeholder="Security seal numbers, carton conditions, packing slips..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-shield-check me-1"></i>Authorize Dispatch &amp; Gate Pass
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <!-- Receive Verification Modal -->
  @if($transfer->canBeReceived())
    <div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('transfers.receive', $transfer) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Inspect &amp; Receive Inward Stock</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">
              Verify each serialized hardware unit against Gate Pass <strong>{{ $transfer->gate_pass_number }}</strong>. Mark condition to accept into destination showroom stock.
            </p>

            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Appliance</th>
                    <th>IMEI / Serial</th>
                    <th style="width: 25%;">Condition</th>
                    <th style="width: 35%;">Inspection Notes</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($transfer->items as $item)
                    <tr>
                      <td>
                        <div class="fw-semibold">{{ $item->product?->brand }} {{ $item->product?->model_name }}</div>
                      </td>
                      <td>
                        <span class="font-monospace small">{{ $item->serializedItem?->identifier_label ?? 'Bulk' }}</span>
                      </td>
                      <td>
                        <select name="items[{{ $item->id }}][condition]" class="form-select form-select-sm">
                          <option value="good">Good / Undamaged</option>
                          <option value="damaged">Damaged in Transit</option>
                          <option value="missing">Missing / Not in Carton</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" name="items[{{ $item->id }}][notes]" class="form-control form-control-sm" placeholder="Seal intact, minor carton scratch...">
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">
              <i class="bi bi-check2-circle me-1"></i>Verify &amp; Accept Stock into Showroom
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  <!-- Cancel Modal -->
  @if($transfer->canBeCancelled() || $transfer->status === 'dispatched')
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold text-danger"><i class="bi bi-slash-circle me-2"></i>Cancel Transfer Order</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small">
              Are you sure you want to cancel transfer <strong>{{ $transfer->transfer_number }}</strong>?
              @if($transfer->status === 'dispatched')
                <div class="alert alert-warning small py-2">
                  <i class="bi bi-exclamation-triangle me-1"></i>Notice: Items have already been dispatched. Cancelling will revert hardware stock back to the origin showroom.
                </div>
              @endif
            </p>
            <div class="mb-3">
              <label class="form-label fw-semibold">Cancellation / Rejection Reason <span class="text-danger">*</span></label>
              <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Provide reason for cancellation..." required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Transfer</button>
            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
          </div>
        </form>
      </div>
    </div>
  @endif
</x-app-layout>
