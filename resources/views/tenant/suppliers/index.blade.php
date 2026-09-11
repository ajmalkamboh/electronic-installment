<x-app-layout title="Wholesale Suppliers">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Wholesale Distributors & Suppliers</h1>
      <p class="text-muted mb-0">Manage authorized electronics distributors supplying stock to showroom branches.</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
        <i class="bi bi-building-add me-1"></i>Register Supplier
      </button>
    </div>
  </div>

  <div class="row g-4">
    @forelse($suppliers as $supplier)
      <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="fw-bold mb-0 text-dark">{{ $supplier->name }}</h5>
                <small class="text-muted font-monospace">{{ $supplier->city ?: 'Pakistan' }}</small>
              </div>
              <span class="badge {{ $supplier->is_active ? 'bg-success-subtle text-success' : 'bg-secondary' }}">
                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <ul class="list-group list-group-flush small mb-3">
              <li class="list-group-item d-flex justify-content-between px-0 py-1">
                <span class="text-muted">Contact Person:</span>
                <strong class="text-dark">{{ $supplier->contact_person ?: '—' }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-1">
                <span class="text-muted">Phone:</span>
                <strong class="text-dark">{{ $supplier->phone ?: '—' }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-1">
                <span class="text-muted">Email:</span>
                <strong class="text-dark">{{ $supplier->email ?: '—' }}</strong>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-1">
                <span class="text-muted">NTN / Tax ID:</span>
                <span class="font-monospace text-dark">{{ $supplier->ntn_number ?: '—' }}</span>
              </li>
              <li class="list-group-item d-flex justify-content-between px-0 py-1">
                <span class="text-muted">Supplied Products:</span>
                <span class="badge bg-light text-dark">{{ $supplier->products_count }} lines</span>
              </li>
            </ul>

            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
              <button type="button" class="btn btn-sm btn-outline-secondary"
                      data-bs-toggle="modal" data-bs-target="#editModal{{ $supplier->id }}">
                <i class="bi bi-pencil me-1"></i>Edit
              </button>
              @if($supplier->products_count === 0)
                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('Delete this supplier record?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>Delete
                  </button>
                </form>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Modal: Edit Supplier -->
      <div class="modal fade" id="editModal{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content text-start">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Supplier: {{ $supplier->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Company / Trading Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control" value="{{ $supplier->name }}" required>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ $supplier->contact_person }}">
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ $supplier->phone }}">
                  </div>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $supplier->email }}">
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">NTN / Tax ID</label>
                    <input type="text" name="ntn_number" class="form-control" value="{{ $supplier->ntn_number }}">
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">City</label>
                  <input type="text" name="city" class="form-control" value="{{ $supplier->city }}">
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Warehouse / Office Address</label>
                  <textarea name="address" class="form-control" rows="2">{{ $supplier->address }}</textarea>
                </div>
                <div class="form-check">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActiveSupp{{ $supplier->id }}" {{ $supplier->is_active ? 'checked' : '' }}>
                  <label class="form-check-label fw-semibold" for="editActiveSupp{{ $supplier->id }}">Active Supplier</label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-building-slash fs-1 d-block mb-2 text-secondary"></i>
        No wholesale distributors registered yet. Click "Register Supplier" to track procurement sources.
      </div>
    @endforelse
  </div>

  <!-- Modal: Create Supplier -->
  <div class="modal fade" id="createSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start">
        <form action="{{ route('suppliers.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Register Wholesale Supplier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Company / Trading Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Haier Pakistan Official, Samsung Tech Distributors" required>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" placeholder="Representative Name">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="042-31234567">
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="sales@distributor.com">
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold">NTN / Tax ID</label>
                <input type="text" name="ntn_number" class="form-control" placeholder="1234567-8">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">City</label>
              <input type="text" name="city" class="form-control" placeholder="e.g. Lahore, Karachi, Rawalpindi">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Warehouse / Office Address</label>
              <textarea name="address" class="form-control" rows="2" placeholder="Full physical location"></textarea>
            </div>
            <div class="form-check">
              <input type="hidden" name="is_active" value="0">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createActiveSupp" checked>
              <label class="form-check-label fw-semibold" for="createActiveSupp">Active Supplier</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Register Supplier</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
