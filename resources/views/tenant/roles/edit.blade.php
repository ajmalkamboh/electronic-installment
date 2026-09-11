<x-app-layout title="Configure Role Permissions">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="mb-1">
        <a href="{{ route('roles.index') }}" class="text-decoration-none text-muted small">
          <i class="bi bi-arrow-left me-1"></i>Back to Roles
        </a>
      </div>
      <h1 class="h3 fw-bold mb-1">Configure Role: {{ $role->display_name }}</h1>
      <p class="text-muted mb-0">
        Adjust assigned permissions and capabilities for this role.
      </p>
    </div>
  </div>

  <form method="POST" action="{{ route('roles.update', $role) }}">
    @csrf
    @method('PUT')

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
        <h5 class="fw-bold mb-1"><i class="bi bi-shield-check me-2 text-primary"></i>Role Identity</h5>
      </div>
      <div class="card-body px-4 pb-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Role Display Title <span class="text-danger">*</span></label>
            <input type="text" name="display_name" class="form-control @error('display_name') is-invalid @enderror" value="{{ old('display_name', $role->display_name) }}" {{ $role->is_system ? 'readonly' : 'required' }}>
            @if($role->is_system)
              <small class="text-muted">Predefined system role title cannot be renamed.</small>
            @endif
            @error('display_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Role Description</label>
            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $role->description) }}">
            @error('description')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions Matrix -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>Configured Permissions</h5>
      <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllCheckboxes(true)">Select All</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes(false)">Deselect All</button>
      </div>
    </div>

    <div class="row g-3 mb-4">
      @foreach($permissionsGrouped as $groupName => $perms)
        <div class="col-md-6 col-xl-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-light border-0 py-2 px-3 d-flex justify-content-between align-items-center">
              <span class="fw-bold text-dark text-capitalize">
                <i class="bi bi-folder2 me-1 text-primary"></i>{{ $groupName }}
              </span>
              <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="toggleGroupCheckboxes('{{ $groupName }}')">
                Toggle
              </button>
            </div>
            <div class="card-body p-3">
              @foreach($perms as $perm)
                <div class="form-check mb-2">
                  <input class="form-check-input perm-check perm-group-{{ $groupName }}" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" {{ in_array($perm->id, old('permissions', $assignedPermissionIds)) ? 'checked' : '' }}>
                  <label class="form-check-label small" for="perm_{{ $perm->id }}">
                    <strong>{{ $perm->display_name }}</strong>
                    <span class="d-block text-muted font-monospace" style="font-size: 0.75rem;">{{ $perm->name }}</span>
                  </label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="d-flex gap-2 mb-5">
      <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
        <i class="bi bi-save me-1"></i>Update Permissions
      </button>
      <a href="{{ route('roles.index') }}" class="btn btn-light px-4 py-2">
        Cancel
      </a>
    </div>
  </form>

  <script>
    function toggleAllCheckboxes(check) {
      document.querySelectorAll('.perm-check').forEach(el => el.checked = check);
    }
    function toggleGroupCheckboxes(group) {
      const inputs = document.querySelectorAll('.perm-group-' + group);
      const anyUnchecked = Array.from(inputs).some(el => !el.checked);
      inputs.forEach(el => el.checked = anyUnchecked);
    }
  </script>
</x-app-layout>
