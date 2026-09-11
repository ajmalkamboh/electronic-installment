<x-app-layout title="Roles & Permissions">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('staff.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Staff Directory
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Roles & Access Permissions</h1>
      <p class="text-muted mb-0">
        Role-Based Access Control (RBAC) defining capabilities, security boundaries, and authorization levels.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('roles.create') }}" class="btn btn-primary">
        <i class="bi bi-shield-plus me-1"></i>Create Custom Role
      </a>
    </div>
  </div>

  <!-- Roles Grid & List -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Configured System & Tenant Roles ({{ $roles->count() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Role Title / Identifier</th>
              <th>Description</th>
              <th>Type</th>
              <th>Granted Permissions</th>
              <th>Assigned Staff</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($roles as $role)
              <tr>
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar {{ $role->is_system ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} rounded p-2 me-3">
                      <i class="bi {{ $role->is_system ? 'bi-shield-shaded' : 'bi-shield-check' }} fs-5"></i>
                    </div>
                    <div>
                      <div class="fw-bold text-dark">{{ $role->display_name }}</div>
                      <small class="text-muted font-monospace">{{ $role->name }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="text-muted small" style="max-width: 320px; display: block;">
                    {{ $role->description ?? 'No formal description provided.' }}
                  </span>
                </td>
                <td>
                  @if($role->is_system)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                      <i class="bi bi-lock me-1"></i>Predefined System
                    </span>
                  @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="bi bi-person-gear me-1"></i>Custom Tenant
                    </span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ $role->permissions_count }} Permissions
                  </span>
                </td>
                <td>
                  <span class="badge bg-info-subtle text-info border border-info-subtle">
                    {{ $role->primary_users_count }} Staff
                  </span>
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group">
                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary" title="Configure Permissions">
                      <i class="bi bi-sliders me-1"></i>Permissions
                    </a>

                    @if(!$role->is_system)
                      <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete custom role \'{{ $role->display_name }}\'?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Custom Role" {{ $role->primary_users_count > 0 ? 'disabled' : '' }}>
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
