<x-app-layout title="Branch Showrooms">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Branch Showrooms & Outlets</h1>
      <p class="text-muted mb-0">
        Manage physical retail showrooms, outlet codes, and local branch assignments for <strong>{{ $company->name }}</strong>.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('branches.create') }}" class="btn btn-primary d-flex align-items-center">
        <i class="bi bi-plus-lg me-2"></i>Add Showroom
      </a>
    </div>
  </div>

  <!-- Branch List Card -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="fw-bold mb-0">All Configured Showrooms ({{ $branches->count() }})</h5>
      <div class="text-muted small">
        Current Operational Focus: <strong>{{ $activeBranch->name ?? 'None' }}</strong>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4" style="width: 250px;">Branch / Showroom</th>
              <th>Code</th>
              <th>City / Location</th>
              <th>Contact Details</th>
              <th>Assigned Staff</th>
              <th>Status</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($branches as $branch)
              <tr class="{{ ($activeBranch && $activeBranch->id === $branch->id) ? 'table-primary-subtle' : '' }}">
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar bg-light border text-primary rounded p-2 me-3">
                      <i class="bi {{ $branch->is_main ? 'bi-star-fill text-warning' : 'bi-shop' }} fs-5"></i>
                    </div>
                    <div>
                      <div class="fw-bold text-dark">
                        {{ $branch->name }}
                        @if ($branch->is_main)
                          <span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1 small">Main HQ</span>
                        @endif
                        @if ($activeBranch && $activeBranch->id === $branch->id)
                          <span class="badge bg-primary ms-1 small">Active Focus</span>
                        @endif
                      </div>
                      <small class="text-muted text-truncate d-block" style="max-width: 200px;">{{ $branch->address ?? 'No physical address' }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <code class="fw-bold fs-6">{{ $branch->code }}</code>
                </td>
                <td>
                  <span class="fw-medium">{{ $branch->city ?? 'N/A' }}</span>
                </td>
                <td>
                  <div class="small">
                    <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $branch->phone ?? 'N/A' }}</div>
                    @if ($branch->email)
                      <div class="text-muted"><i class="bi bi-envelope me-1"></i>{{ $branch->email }}</div>
                    @endif
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <i class="bi bi-people me-1"></i>{{ $branch->users_count }} Staff
                  </span>
                </td>
                <td>
                  @if ($branch->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                      <i class="bi bi-slash-circle me-1"></i>Inactive
                    </span>
                  @endif
                </td>
                <td class="text-end pe-4">
                  <div class="d-inline-flex gap-2">
                    @if ($branch->status === 'active' && (!$activeBranch || $activeBranch->id !== $branch->id))
                      <form method="POST" action="{{ route('tenant.switch-branch') }}">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Switch active focus to this showroom">
                          <i class="bi bi-arrow-repeat me-1"></i>Switch
                        </button>
                      </form>
                    @endif

                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary" title="Edit Showroom Details">
                      <i class="bi bi-pencil"></i>
                    </a>

                    @if (!$branch->is_main)
                      <form method="POST" action="{{ route('branches.toggle-status', $branch) }}" onsubmit="return confirm('Change status for {{ $branch->name }}?')">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $branch->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $branch->status === 'active' ? 'Deactivate Showroom' : 'Activate Showroom' }}">
                          <i class="bi {{ $branch->status === 'active' ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-shop fs-1 d-block mb-2 text-secondary"></i>
                  No showrooms registered yet. Click <strong>Add Showroom</strong> to begin.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
