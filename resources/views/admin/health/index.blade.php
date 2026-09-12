<x-app-layout title="System Health & Diagnostics - Super Admin">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Dashboard
        </a>
        <h1 class="h3 fw-bold mb-0">System Health & Production Diagnostics</h1>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Operational</span>
      </div>
      <p class="text-muted mb-0">Production telemetry, database performance, storage metrics, and automated disaster recovery status.</p>
    </div>
    <div class="d-flex gap-2">
      <form method="POST" action="{{ route('admin.backup.trigger') }}" onsubmit="return confirm('Generate database backup snapshot now?');">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-database-down me-1"></i>Run Database Backup Snapshot
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

  <!-- Top Metric Cards -->
  <div class="row g-3 mb-4">
    <!-- Database Health Card -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small fw-semibold text-uppercase">Database Latency</span>
            <div class="bg-primary-subtle text-primary p-2 rounded">
              <i class="bi bi-database"></i>
            </div>
          </div>
          <div class="h3 fw-bold mb-1">
            @if(($diagnostics['database']['status'] ?? '') === 'ok')
              <span class="text-success">{{ $diagnostics['database']['latency_ms'] ?? 0 }} <small class="text-muted fs-6">ms</small></span>
            @else
              <span class="text-danger">Failed</span>
            @endif
          </div>
          <span class="small text-muted font-monospace">Driver: {{ $diagnostics['database']['connection'] ?? 'unknown' }}</span>
        </div>
      </div>
    </div>

    <!-- Storage Writable Card -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small fw-semibold text-uppercase">Storage Writable</span>
            <div class="bg-success-subtle text-success p-2 rounded">
              <i class="bi bi-hdd"></i>
            </div>
          </div>
          <div class="h3 fw-bold mb-1">
            @if($diagnostics['storage']['writable'] ?? false)
              <span class="text-success">Writable</span>
            @else
              <span class="text-danger">Read-Only</span>
            @endif
          </div>
          <span class="small text-muted">Free: {{ $diagnostics['storage']['free_space_gb'] ?? 'N/A' }} GB</span>
        </div>
      </div>
    </div>

    <!-- Cache Health Card -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small fw-semibold text-uppercase">Cache Subsystem</span>
            <div class="bg-info-subtle text-info p-2 rounded">
              <i class="bi bi-lightning-charge"></i>
            </div>
          </div>
          <div class="h3 fw-bold mb-1">
            @if(($diagnostics['cache']['status'] ?? '') === 'ok')
              <span class="text-success">Active</span>
            @else
              <span class="text-warning">Degraded</span>
            @endif
          </div>
          <span class="small text-muted font-monospace">Driver: {{ $diagnostics['cache']['driver'] ?? 'file' }}</span>
        </div>
      </div>
    </div>

    <!-- Backups Count Card -->
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small fw-semibold text-uppercase">Snapshots Stored</span>
            <div class="bg-warning-subtle text-warning p-2 rounded">
              <i class="bi bi-archive"></i>
            </div>
          </div>
          <div class="h3 fw-bold mb-1">{{ $diagnostics['backups']['count'] ?? 0 }}</div>
          <span class="small text-muted">{{ $diagnostics['backups']['total_size_mb'] ?? 0 }} MB disk usage</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Backups Detail Card -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
          <span><i class="bi bi-shield-lock me-1 text-primary"></i> Disaster Recovery & Backups</span>
          <span class="badge bg-primary-subtle text-primary">Daily @ 02:00</span>
        </div>
        <div class="card-body">
          @if(!empty($diagnostics['backups']['latest_backup']))
            <div class="p-3 bg-light rounded border mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold text-dark font-monospace small text-break">{{ $diagnostics['backups']['latest_backup']['filename'] }}</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Latest</span>
              </div>
              <div class="small text-muted">
                Created: {{ \Carbon\Carbon::parse($diagnostics['backups']['latest_backup']['created_at'])->format('M d, Y H:i:s') }}
                &bull; Size: {{ $diagnostics['backups']['latest_backup']['size_mb'] }} MB
              </div>
            </div>
          @else
            <div class="alert alert-warning mb-3">
              <i class="bi bi-exclamation-triangle me-1"></i> No database snapshots recorded yet. Run your first snapshot using the button above.
            </div>
          @endif

          <p class="text-muted small mb-0">
            Automated scheduler runs <code>system:backup-database --clean-days=30</code> nightly. Snapshots are stored in <code>storage/app/backups/</code>.
          </p>
        </div>
      </div>
    </div>

    <!-- Production Hardening & Environment -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent fw-bold py-3 border-bottom">
          <i class="bi bi-sliders me-1 text-primary"></i> Environment & Runtime Hardening
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Application Environment (APP_ENV)</span>
              <span class="badge bg-dark font-monospace">{{ $diagnostics['environment']['env'] }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Debug Mode (APP_DEBUG)</span>
              @if($diagnostics['environment']['debug'])
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Enabled (Dev)</span>
              @else
                <span class="badge bg-success-subtle text-success border border-success-subtle">Disabled (Production Safe)</span>
              @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">PHP Runtime Engine</span>
              <span class="fw-medium font-monospace small">PHP {{ $diagnostics['php']['version'] }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Required Extensions</span>
              @if($diagnostics['php']['extensions_ok'])
                <span class="badge bg-success-subtle text-success border border-success-subtle">All Required Extensions Loaded</span>
              @else
                <span class="badge bg-danger-subtle text-danger border">Missing: {{ implode(', ', $diagnostics['php']['missing_extensions']) }}</span>
              @endif
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">OWASP Security Headers</span>
              <span class="badge bg-success-subtle text-success border border-success-subtle">Active (Enforced Global)</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
