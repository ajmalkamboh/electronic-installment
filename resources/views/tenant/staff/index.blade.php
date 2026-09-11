<x-app-layout title="Staff Directory">
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Staff & Employee Management</h1>
      <p class="text-muted mb-0">
        Manage corporate personnel, showroom branch assignments, and role-based security access.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-shield-lock me-1"></i>Roles & Permissions
      </a>
      <a href="{{ route('staff.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Register Staff Member
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
            <span class="text-muted small fw-semibold">Total Personnel</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $totalStaff }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-success-subtle text-success rounded p-3 me-3">
            <i class="bi bi-person-check fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Active Staff</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $activeStaff }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-danger-subtle text-danger rounded p-3 me-3">
            <i class="bi bi-person-x fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Suspended</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $suspendedStaff }}</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body d-flex align-items-center">
          <div class="bg-info-subtle text-info rounded p-3 me-3">
            <i class="bi bi-shop fs-4"></i>
          </div>
          <div>
            <span class="text-muted small fw-semibold">Showrooms Staffed</span>
            <h4 class="fw-bold mb-0 text-dark">{{ $branchesCovered }}</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Toolbar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('staff.index') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-start-0" placeholder="Search name, email, code, CNIC..." value="{{ request('search') }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="branch_id" class="form-select">
            <option value="">All Branch Showrooms</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                {{ $branch->name }} ({{ $branch->code }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="role_id" class="form-select">
            <option value="">All Roles</option>
            @foreach($roles as $r)
              <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                {{ $r->display_name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
          <button type="submit" class="btn btn-secondary w-100" title="Apply Filters">
            <i class="bi bi-filter"></i>
          </button>
          @if(request()->anyFilled(['search', 'branch_id', 'role_id', 'status']))
            <a href="{{ route('staff.index') }}" class="btn btn-outline-danger" title="Clear Filters">
              <i class="bi bi-x-lg"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Staff Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Staff Members ({{ $staffMembers->total() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Employee</th>
              <th>Code / CNIC</th>
              <th>Branch Showroom</th>
              <th>System Role</th>
              <th>Designation</th>
              <th>Status</th>
              <th>Security / Last Login</th>
              <th class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($staffMembers as $member)
              <tr>
                <td class="ps-4">
                  <div class="d-flex align-items-center">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 38px; height: 38px;">
                      {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                    <div>
                      <div class="fw-bold text-dark">{{ $member->name }}</div>
                      <small class="text-muted">{{ $member->email }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <div><span class="badge bg-light text-dark border">{{ $member->employee_code ?? 'N/A' }}</span></div>
                  <small class="text-muted">{{ $member->cnic ?? 'No CNIC' }}</small>
                </td>
                <td>
                  @if($member->branch)
                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                      <i class="bi bi-shop me-1"></i>{{ $member->branch->name }}
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary">
                      <i class="bi bi-globe me-1"></i>All Branches (HQ)
                    </span>
                  @endif
                </td>
                <td>
                  @if($member->roleRecord)
                    <span class="badge bg-primary-subtle text-primary">
                      {{ $member->roleRecord->display_name }}
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-dark">
                      {{ ucfirst(str_replace('_', ' ', $member->role)) }}
                    </span>
                  @endif
                </td>
                <td>
                  <span class="text-dark">{{ $member->designation ?? 'Staff Member' }}</span>
                </td>
                <td>
                  @if($member->status === 'active')
                    <span class="badge bg-success-subtle text-success">Active</span>
                  @elseif($member->status === 'suspended')
                    <span class="badge bg-danger-subtle text-danger">Suspended</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                  @endif
                </td>
                <td>
                  @if($member->last_login_at)
                    <small class="text-dark d-block">{{ $member->last_login_at->diffForHumans() }}</small>
                    <small class="text-muted font-monospace">{{ $member->last_login_ip ?? '' }}</small>
                  @else
                    <small class="text-muted">Never Logged In</small>
                  @endif
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group">
                    <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-outline-secondary" title="Edit Staff Profile">
                      <i class="bi bi-pencil"></i>
                    </a>

                    <!-- Status Toggle -->
                    @if($member->id !== auth()->id())
                      <form action="{{ route('staff.toggle-status', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to toggle the access status for {{ $member->name }}?');">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $member->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $member->status === 'active' ? 'Suspend Account' : 'Activate Account' }}">
                          <i class="bi {{ $member->status === 'active' ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                        </button>
                      </form>
                    @endif

                    <!-- Reset Password Trigger -->
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#resetModal{{ $member->id }}" title="Reset Password">
                      <i class="bi bi-key"></i>
                    </button>
                  </div>

                  <!-- Reset Password Modal -->
                  <div class="modal fade" id="resetModal{{ $member->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content text-start">
                        <form action="{{ route('staff.reset-password', $member) }}" method="POST">
                          @csrf
                          <div class="modal-header">
                            <h5 class="modal-title fw-bold">Reset Password: {{ $member->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p class="text-muted small">
                              Set a new secure password for <strong>{{ $member->name }}</strong> ({{ $member->email }}). The user will be required to authenticate with this new password immediately.
                            </p>
                            <div class="mb-3">
                              <label class="form-label fw-semibold">New Password</label>
                              <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                            </div>
                            <div class="mb-3">
                              <label class="form-label fw-semibold">Confirm Password</label>
                              <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repeat new password">
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                  No staff members matched your filter criteria.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($staffMembers->hasPages())
      <div class="card-footer bg-transparent border-0 px-4 py-3">
        {{ $staffMembers->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
