<x-app-layout title="Platform Audit Trail - Super Admin">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">Platform Audit Trail & Telemetry</h1>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Platform Admin</span>
      </div>
      <p class="text-muted mb-0">Cross-tenant immutable audit records, data mutation histories, and administrative events.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.health.index') }}" class="btn btn-outline-info btn-sm">
        <i class="bi bi-heart-pulse me-1"></i>System Diagnostics & Health
      </a>
      <form method="POST" action="{{ route('admin.backup.trigger') }}" class="d-inline" onsubmit="return confirm('Generate database backup snapshot now?');">
        @csrf
        <button type="submit" class="btn btn-outline-success btn-sm">
          <i class="bi bi-database-down me-1"></i>Snapshot DB Now
        </button>
      </form>
    </div>
  </div>

  @if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Filters Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Company / Tenant</label>
          <select name="company_id" class="form-select form-select-sm">
            <option value="">All Tenants (Global)</option>
            @foreach($companies as $comp)
              <option value="{{ $comp->id }}" {{ ($filters['company_id'] ?? '') == $comp->id ? 'selected' : '' }}>
                {{ $comp->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Event Action</label>
          <select name="event" class="form-select form-select-sm">
            <option value="">All Events</option>
            <option value="created" {{ ($filters['event'] ?? '') === 'created' ? 'selected' : '' }}>Created</option>
            <option value="updated" {{ ($filters['event'] ?? '') === 'updated' ? 'selected' : '' }}>Updated</option>
            <option value="deleted" {{ ($filters['event'] ?? '') === 'deleted' ? 'selected' : '' }}>Deleted</option>
            <option value="login" {{ ($filters['event'] ?? '') === 'login' ? 'selected' : '' }}>Login</option>
            <option value="permission_denied" {{ ($filters['event'] ?? '') === 'permission_denied' ? 'selected' : '' }}>Permission Denied</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">From Date</label>
          <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-select-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">To Date</label>
          <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-select-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label small text-muted mb-1">Search Keyword</label>
          <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="IP, Model, User..." class="form-control form-select-sm">
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-primary btn-sm w-100" title="Filter"><i class="bi bi-filter"></i></button>
          <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Timestamp</th>
            <th>Tenant</th>
            <th>Event</th>
            <th>Entity Class</th>
            <th>Entity ID</th>
            <th>Actor</th>
            <th>IP Address</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            <tr>
              <td class="ps-3 text-nowrap">
                <span class="fw-medium text-dark">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
                <span class="d-block small text-muted">{{ $log->created_at->diffForHumans() }}</span>
              </td>
              <td>
                @if($log->company)
                  <span class="fw-medium d-block text-dark">{{ $log->company->name }}</span>
                  <span class="small text-muted font-monospace">Tenant #{{ $log->company_id }}</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">System Global</span>
                @endif
              </td>
              <td>
                @php
                  $badgeClass = match($log->event) {
                    'created' => 'bg-success-subtle text-success border-success-subtle',
                    'updated' => 'bg-info-subtle text-info border-info-subtle',
                    'deleted' => 'bg-danger-subtle text-danger border-danger-subtle',
                    'login' => 'bg-primary-subtle text-primary border-primary-subtle',
                    'permission_denied' => 'bg-warning-subtle text-warning border-warning-subtle',
                    default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                  };
                @endphp
                <span class="badge {{ $badgeClass }} border px-2 py-1 text-uppercase small">
                  {{ $log->event }}
                </span>
              </td>
              <td>
                <span class="fw-medium text-break">{{ class_basename($log->auditable_type ?? 'Security Event') }}</span>
              </td>
              <td>
                <span class="text-muted font-monospace small">#{{ $log->auditable_id ?? 'N/A' }}</span>
              </td>
              <td>
                @if($log->user)
                  <span class="fw-medium d-block">{{ $log->user->name }}</span>
                  <span class="small text-muted">{{ $log->user->email }}</span>
                @else
                  <span class="badge bg-light text-dark border">System / Guest</span>
                @endif
              </td>
              <td>
                <span class="font-monospace small text-muted">{{ $log->ip_address ?? '127.0.0.1' }}</span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('admin.audit-logs.show', $log->id) }}" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-eye me-1"></i>Inspect
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-shield-check display-6 d-block mb-2 text-secondary"></i>
                No platform audit entries found matching filter criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($logs->hasPages())
      <div class="card-footer bg-white border-top p-3">
        {{ $logs->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
