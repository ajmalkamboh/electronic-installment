<x-app-layout title="Platform Audit Entry #{{ $log->id }}">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Back
        </a>
        <h1 class="h3 fw-bold mb-0">Platform Audit Entry #{{ $log->id }}</h1>
        @php
          $badgeClass = match($log->event) {
            'created' => 'bg-success-subtle text-success border-success-subtle',
            'updated' => 'bg-info-subtle text-info border-info-subtle',
            'deleted' => 'bg-danger-subtle text-danger border-danger-subtle',
            'login' => 'bg-primary-subtle text-primary border-primary-subtle',
            default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
          };
        @endphp
        <span class="badge {{ $badgeClass }} border px-2 py-1 text-uppercase">{{ $log->event }}</span>
      </div>
      <p class="text-muted mb-0">Immutable snapshot recorded on {{ $log->created_at->format('F d, Y \a\t H:i:s T') }}</p>
    </div>
  </div>

  <div class="row g-4">
    <!-- Telemetry Column -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3 border-bottom">
          <i class="bi bi-info-circle me-1 text-primary"></i> Context & Telemetry
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Tenant Company</span>
              <span class="fw-medium small text-end">
                @if($log->company)
                  {{ $log->company->name }} <span class="text-muted">(#{{ $log->company_id }})</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">Global</span>
                @endif
              </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Actor User</span>
              <span class="fw-medium small text-end">
                {{ $log->user ? $log->user->name : 'System / Automated' }}
              </span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Actor Email</span>
              <span class="small font-monospace">{{ $log->user ? $log->user->email : 'N/A' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Entity Class</span>
              <span class="small font-monospace text-break">{{ $log->auditable_type ?? 'N/A' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Entity ID</span>
              <span class="small font-monospace">#{{ $log->auditable_id ?? 'N/A' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">IP Address</span>
              <span class="small font-monospace">{{ $log->ip_address ?? '127.0.0.1' }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
              <span class="text-muted small">Request URL</span>
              <span class="small font-monospace text-break">{{ $log->url ?? 'N/A' }}</span>
            </li>
            <li class="list-group-item py-2 px-3">
              <div class="text-muted small mb-1">User Agent</div>
              <div class="small font-monospace text-muted text-break bg-light p-2 rounded">{{ $log->user_agent ?? 'N/A' }}</div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Data Diffs -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
          <span><i class="bi bi-code-slash me-1 text-primary"></i> Data Mutations & Diff</span>
          <span class="badge bg-light text-secondary border font-monospace small">Immutable Record</span>
        </div>
        <div class="card-body p-3">
          @php
            $diff = $log->diff;
          @endphp

          @if(!empty($diff))
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 25%;">Field</th>
                    <th style="width: 37.5%;" class="text-danger">Before (Old State)</th>
                    <th style="width: 37.5%;" class="text-success">After (New State)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($diff as $field => $change)
                    <tr>
                      <td class="font-monospace fw-bold small text-secondary">{{ $field }}</td>
                      <td class="bg-danger-subtle bg-opacity-25 font-monospace small text-break">
                        @if(is_array($change['old']))
                          <pre class="mb-0 text-dark font-monospace small">{{ json_encode($change['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @elseif($change['old'] === null)
                          <em class="text-muted">null</em>
                        @elseif(is_bool($change['old']))
                          <em>{{ $change['old'] ? 'true' : 'false' }}</em>
                        @else
                          {{ $change['old'] }}
                        @endif
                      </td>
                      <td class="bg-success-subtle bg-opacity-25 font-monospace small text-break">
                        @if(is_array($change['new']))
                          <pre class="mb-0 text-dark font-monospace small">{{ json_encode($change['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @elseif($change['new'] === null)
                          <em class="text-muted">null</em>
                        @elseif(is_bool($change['new']))
                          <em>{{ $change['new'] ? 'true' : 'false' }}</em>
                        @else
                          {{ $change['new'] }}
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @elseif(!empty($log->new_values))
            <h6 class="fw-bold text-success mb-2">Payload State:</h6>
            <pre class="bg-light p-3 rounded font-monospace small border mb-0 text-dark">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
          @elseif(!empty($log->old_values))
            <h6 class="fw-bold text-danger mb-2">Deleted Prior State:</h6>
            <pre class="bg-light p-3 rounded font-monospace small border mb-0 text-dark">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
          @else
            <p class="text-muted mb-0">No attribute state changes recorded for this entry.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
