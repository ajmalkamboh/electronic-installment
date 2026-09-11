<x-app-layout title="Customer Directory">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Customer & Debtor Directory</h1>
      <p class="text-muted mb-0">
        Manage customer identities, Pakistani CNIC profiles, legal guarantors, and credit underwriting dossiers.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Register Customer
      </a>
    </div>
  </div>

  <!-- Metric Summary Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-primary-subtle text-primary rounded p-3 me-3">
            <i class="bi bi-people fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Total Customers</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalCustomers }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success-subtle text-success rounded p-3 me-3">
            <i class="bi bi-patch-check fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Active Approved</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $activeCustomers }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-warning-subtle text-warning rounded p-3 me-3">
            <i class="bi bi-hourglass-split fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Pending Verification</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $pendingVerification }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-danger-subtle text-danger rounded p-3 me-3">
            <i class="bi bi-slash-circle fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Blacklisted</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $blacklisted }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Toolbar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0" placeholder="Search name, CNIC (XXXXX-XXXXXXX-X), mobile, bill #..." value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Verification Statuses</option>
            <option value="pending_verification" {{ request('status') === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Verified)</option>
            <option value="restricted" {{ request('status') === 'restricted' ? 'selected' : '' }}>Restricted</option>
            <option value="blacklisted" {{ request('status') === 'blacklisted' ? 'selected' : '' }}>Blacklisted Defaulter</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="residence_type" class="form-select">
            <option value="">All Residence Types</option>
            <option value="owned" {{ request('residence_type') === 'owned' ? 'selected' : '' }}>Owned House</option>
            <option value="rented" {{ request('residence_type') === 'rented' ? 'selected' : '' }}>Rented Property</option>
            <option value="family" {{ request('residence_type') === 'family' ? 'selected' : '' }}>Family / Inherited</option>
          </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-secondary w-100" title="Apply Filters">
            <i class="bi bi-filter"></i>
          </button>
          @if(request()->anyFilled(['search', 'status', 'residence_type']))
            <a href="{{ route('customers.index') }}" class="btn btn-outline-danger" title="Clear Filters">
              <i class="bi bi-x-lg"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Customers Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Customer Accounts ({{ $customers->total() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Customer Name</th>
              <th>CNIC / Mobile</th>
              <th>Residence & Address</th>
              <th>Credit Score</th>
              <th>Guarantors</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($customers as $c)
              <tr>
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 38px; height: 38px;">
                      {{ strtoupper(substr($c->full_name, 0, 1)) }}
                    </div>
                    <div>
                      <a href="{{ route('customers.show', $c) }}" class="fw-bold text-dark text-decoration-none">
                        {{ $c->full_name }}
                      </a>
                      @if($c->father_or_husband_name)
                        <small class="text-muted d-block">S/O, D/O: {{ $c->father_or_husband_name }}</small>
                      @endif
                    </div>
                  </div>
                </td>
                <td>
                  <div><span class="badge bg-light text-dark border font-monospace">{{ $c->cnic }}</span></div>
                  <small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $c->mobile_primary }}</small>
                </td>
                <td>
                  <div>
                    <span class="badge bg-{{ $c->residence_type === 'owned' ? 'success' : ($c->residence_type === 'rented' ? 'warning' : 'info') }}-subtle text-dark border small text-capitalize">
                      <i class="bi bi-house me-1"></i>{{ $c->residence_type }}
                    </span>
                  </div>
                  <small class="text-muted text-truncate d-block" style="max-width: 220px;" title="{{ $c->present_address }}">
                    {{ $c->present_address }}
                  </small>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="fw-bold fs-6 me-2 {{ $c->credit_score >= 70 ? 'text-success' : ($c->credit_score >= 40 ? 'text-primary' : 'text-danger') }}">
                      {{ $c->credit_score }}/100
                    </div>
                  </div>
                  <small class="text-muted">Limit: Rs. {{ number_format($c->max_authorized_credit) }}</small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-shield-check me-1 text-primary"></i>{{ $c->guarantors->count() }} Guarantor(s)
                  </span>
                </td>
                <td>
                  @if($c->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="bi bi-check-circle me-1"></i>Active (Approved)
                    </span>
                  @elseif($c->status === 'pending_verification')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                      <i class="bi bi-clock-history me-1"></i>Pending Verification
                    </span>
                  @elseif($c->status === 'blacklisted')
                    <span class="badge bg-danger text-white">
                      <i class="bi bi-slash-circle me-1"></i>Blacklisted
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary">
                      {{ ucfirst($c->status) }}
                    </span>
                  @endif
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group">
                    <a href="{{ route('customers.show', $c) }}" class="btn btn-sm btn-outline-primary" title="View Full Dossier">
                      <i class="bi bi-folder2-open me-1"></i>Dossier
                    </a>
                    <a href="{{ route('customers.edit', $c) }}" class="btn btn-sm btn-outline-secondary" title="Edit Customer Profile">
                      <i class="bi bi-pencil"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                  No customers found matching the search criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($customers->hasPages())
      <div class="card-footer bg-transparent border-0 px-4 py-3">
        {{ $customers->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
