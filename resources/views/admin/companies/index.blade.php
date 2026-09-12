<x-app-layout title="Tenant Companies Directory - Super Admin">
  <!-- Header & Actions Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">Tenant Companies Directory</h1>
        <span class="badge bg-secondary-subtle text-dark border">{{ $companies->total() }} Total</span>
      </div>
      <p class="text-muted mb-0">Manage multi-tenant retail client accounts &bull; Subscription status &bull; Plan quotas</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Command Center
      </a>
      <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Onboard New Tenant
      </a>
    </div>
  </div>

  <!-- Filters Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.companies.index') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control form-control-sm border-start-0" placeholder="Search by name, email, or city..." value="{{ request('search') }}">
          </div>
        </div>

        <div class="col-md-3">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Account Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Free Trial</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>

        <div class="col-md-3">
          <select name="plan_id" class="form-select form-select-sm">
            <option value="">All SaaS Plans</option>
            @foreach($plans as $p)
              <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} (PKR {{ number_format($p->price_monthly) }}/mo)</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-sm btn-dark flex-grow-1">Filter</button>
          @if(request()->anyFilled(['search', 'status', 'plan_id']))
            <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Companies Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Company Name</th>
              <th>Location</th>
              <th>Current Plan</th>
              <th>Status</th>
              <th>Showrooms</th>
              <th>Staff</th>
              <th>Active Agreements</th>
              <th>Renewal Date</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($companies as $company)
              @php
                $sub = $company->currentSubscription();
                $targetDate = $sub ? ($sub->isTrial() ? $sub->trial_ends_at : $sub->ends_at) : null;
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold">{{ $company->name }}</div>
                  <small class="text-muted">{{ $company->email }}</small>
                </td>
                <td>{{ $company->city ?? 'N/A' }}</td>
                <td>
                  @if($sub && $sub->plan)
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                      {{ $sub->plan->name }}
                    </span>
                    <small class="d-block text-muted">{{ ucfirst($sub->billing_cycle) }}</small>
                  @else
                    <span class="badge bg-secondary-subtle text-muted">No Plan</span>
                  @endif
                </td>
                <td>
                  @if($company->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                  @elseif($company->status === 'trial')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Trial</span>
                  @elseif($company->status === 'suspended')
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                  @else
                    <span class="badge bg-secondary-subtle text-muted">{{ ucfirst($company->status) }}</span>
                  @endif
                </td>
                <td>
                  <span class="fw-semibold">{{ $company->branches_count }}</span>
                  @if($sub && $sub->plan)
                    <small class="text-muted">/ {{ $sub->plan->max_branches }}</small>
                  @endif
                </td>
                <td>
                  <span class="fw-semibold">{{ $company->users_count }}</span>
                  @if($sub && $sub->plan)
                    <small class="text-muted">/ {{ $sub->plan->max_users }}</small>
                  @endif
                </td>
                <td>
                  <span class="fw-semibold">{{ $company->agreements_count }}</span>
                  @if($sub && $sub->plan)
                    <small class="text-muted">/ {{ $sub->plan->max_active_agreements }}</small>
                  @endif
                </td>
                <td>
                  @if($targetDate)
                    <div>{{ $targetDate->format('d M Y') }}</div>
                    <small class="text-muted">{{ $sub->daysRemaining() }} days left</small>
                  @else
                    <span class="text-muted">&mdash;</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('admin.companies.show', $company) }}" class="btn btn-sm btn-outline-primary py-1 px-2">
                    <i class="bi bi-sliders me-1"></i>Manage
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="bi bi-buildings fs-1 d-block text-secondary opacity-50 mb-2"></i>
                  No tenant companies matching your filter criteria were found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($companies->hasPages())
      <div class="card-footer bg-transparent border-0 pt-3">
        {{ $companies->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
