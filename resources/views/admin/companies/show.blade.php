<x-app-layout title="{{ $company->name }} - Tenant Dossier">
  <!-- Header & Control Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
          <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">{{ $company->name }}</h1>
        @if($company->status === 'active')
          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
        @elseif($company->status === 'trial')
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Trial</span>
        @elseif($company->status === 'suspended')
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Suspended</span>
        @else
          <span class="badge bg-secondary-subtle text-muted px-2 py-1">{{ ucfirst($company->status) }}</span>
        @endif
      </div>
      <p class="text-muted mb-0">
        {{ $company->legal_name ?? $company->name }} &bull; NTN/STRN: {{ $company->ntn_strn ?? 'Not registered' }} &bull; {{ $company->city }}, Pakistan
      </p>
    </div>

    <!-- Administrative Quick Actions -->
    <div class="d-flex gap-2 flex-wrap">
      <!-- Change Plan Button (Triggers Modal) -->
      <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#changePlanModal">
        <i class="bi bi-arrow-repeat me-1"></i>Change Plan
      </button>

      <!-- Extend Subscription Button (Triggers Modal) -->
      <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#extendSubModal">
        <i class="bi bi-calendar-plus me-1"></i>Extend Period
      </button>

      <!-- Suspend / Reactivate Form -->
      <form method="POST" action="{{ route('admin.companies.toggle-status', $company) }}" onsubmit="return confirm('Are you sure you want to {{ $company->status === 'suspended' ? 'reactivate' : 'suspend' }} this tenant?');">
        @csrf
        @if($company->status === 'suspended')
          <button type="submit" class="btn btn-success btn-sm">
            <i class="bi bi-unlock me-1"></i>Reactivate Tenant
          </button>
        @else
          <button type="submit" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-slash-circle me-1"></i>Suspend Tenant
          </button>
        @endif
      </form>
    </div>
  </div>

  <!-- Subscription Overview & Quota Meters -->
  <div class="row g-4 mb-4">
    <!-- Subscription Card -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Subscription &amp; License Status</h5>
          @if($currentSubscription)
            <span class="badge {{ $currentSubscription->isActive() ? 'bg-success' : 'bg-warning text-dark' }}">
              {{ ucfirst($currentSubscription->status) }}
            </span>
          @endif
        </div>
        <div class="card-body">
          @if($currentSubscription)
            <div class="p-3 bg-light rounded mb-3 border">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Current Tier</span>
                <span class="fw-bold text-primary fs-5">{{ $currentSubscription->plan->name }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Billing Cadence</span>
                <span class="fw-semibold">{{ ucfirst($currentSubscription->billing_cycle) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">License Fee</span>
                <span class="fw-semibold">PKR {{ number_format($currentSubscription->amount_paid, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Renewal / Expiry</span>
                @php
                  $target = $currentSubscription->isTrial() ? $currentSubscription->trial_ends_at : $currentSubscription->ends_at;
                @endphp
                <span class="fw-bold {{ $currentSubscription->daysRemaining() <= 7 ? 'text-danger' : 'text-dark' }}">
                  {{ $target ? $target->format('d M Y') : 'N/A' }} ({{ $currentSubscription->daysRemaining() }} days left)
                </span>
              </div>
            </div>

            <h6 class="fw-bold small text-uppercase text-muted mb-2">Included Features</h6>
            <div class="d-flex flex-wrap gap-1 mb-3">
              @forelse($currentSubscription->plan->features ?? [] as $feat)
                <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-check2 text-success me-1"></i>{{ $feat }}</span>
              @empty
                <span class="text-muted small">Standard baseline features</span>
              @endforelse
            </div>

            @if($currentSubscription->notes)
              <div class="small text-muted bg-white p-2 border rounded">
                <strong>Audit Notes:</strong><br>
                {{ nl2br(e($currentSubscription->notes)) }}
              </div>
            @endif
          @else
            <div class="alert alert-warning mb-0">No subscription record found for this company.</div>
          @endif
        </div>
      </div>
    </div>

    <!-- Quota Telemetry Gauges -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0">
          <h5 class="fw-bold mb-0">Plan Quota Consumption Telemetry</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <!-- User Seats -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-people me-1 text-primary"></i>User Seats</span>
                  <span class="badge {{ $quotaUsage['users']['is_maxed'] ? 'bg-danger' : 'bg-primary-subtle text-primary' }}">
                    {{ $quotaUsage['users']['used'] }} / {{ $quotaUsage['users']['limit'] }}
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['users']['is_maxed'] ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $quotaUsage['users']['percentage'] }}%"></div>
                </div>
                <small class="text-muted">{{ $quotaUsage['users']['percentage'] }}% quota utilized</small>
              </div>
            </div>

            <!-- Showroom Branches -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-shop me-1 text-info"></i>Showroom Outlets</span>
                  <span class="badge {{ $quotaUsage['branches']['is_maxed'] ? 'bg-danger' : 'bg-info-subtle text-info' }}">
                    {{ $quotaUsage['branches']['used'] }} / {{ $quotaUsage['branches']['limit'] }}
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['branches']['is_maxed'] ? 'bg-danger' : 'bg-info' }}" style="width: {{ $quotaUsage['branches']['percentage'] }}%"></div>
                </div>
                <small class="text-muted">{{ $quotaUsage['branches']['percentage'] }}% quota utilized</small>
              </div>
            </div>

            <!-- Active Agreements -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-file-earmark-text me-1 text-success"></i>Active Contracts</span>
                  <span class="badge {{ $quotaUsage['agreements']['is_maxed'] ? 'bg-danger' : 'bg-success-subtle text-success' }}">
                    {{ $quotaUsage['agreements']['used'] }} / {{ $quotaUsage['agreements']['limit'] }}
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['agreements']['is_maxed'] ? 'bg-danger' : 'bg-success' }}" style="width: {{ $quotaUsage['agreements']['percentage'] }}%"></div>
                </div>
                <small class="text-muted">{{ $quotaUsage['agreements']['percentage'] }}% quota utilized</small>
              </div>
            </div>

            <!-- Monthly Transactions -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-cash-coin me-1 text-warning"></i>Monthly Receipts</span>
                  <span class="badge {{ $quotaUsage['transactions']['is_maxed'] ? 'bg-danger' : 'bg-warning-subtle text-dark' }}">
                    {{ $quotaUsage['transactions']['used'] }} / {{ $quotaUsage['transactions']['limit'] }}
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['transactions']['is_maxed'] ? 'bg-danger' : 'bg-warning' }}" style="width: {{ $quotaUsage['transactions']['percentage'] }}%"></div>
                </div>
                <small class="text-muted">{{ $quotaUsage['transactions']['percentage'] }}% used this calendar month</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Detailed Tabs: Branches, Staff, Subscription History -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-3">
      <ul class="nav nav-tabs card-header-tabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#branches-tab" type="button">
            <i class="bi bi-shop me-1"></i>Showroom Branches ({{ $company->branches->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#staff-tab" type="button">
            <i class="bi bi-people me-1"></i>Staff Directory ({{ $company->users->count() }})
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#history-tab" type="button">
            <i class="bi bi-clock-history me-1"></i>Subscription History ({{ $company->subscriptions->count() }})
          </button>
        </li>
      </ul>
    </div>
    <div class="card-body p-0">
      <div class="tab-content">
        <!-- Branches Tab -->
        <div class="tab-pane fade show active" id="branches-tab">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Branch Code</th>
                  <th>Branch Name</th>
                  <th>City</th>
                  <th>Address</th>
                  <th>Main HQ</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($company->branches as $b)
                  <tr>
                    <td><span class="badge bg-secondary-subtle text-dark">{{ $b->code }}</span></td>
                    <td class="fw-semibold">{{ $b->name }}</td>
                    <td>{{ $b->city }}</td>
                    <td><small class="text-muted">{{ $b->address }}</small></td>
                    <td>{!! $b->is_main ? '<span class="badge bg-primary">HQ</span>' : '<span class="text-muted">&mdash;</span>' !!}</td>
                    <td>
                      <span class="badge {{ $b->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary' }}">
                        {{ ucfirst($b->status) }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center py-4 text-muted">No branches recorded.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- Staff Tab -->
        <div class="tab-pane fade" id="staff-tab">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Staff Name</th>
                  <th>Email</th>
                  <th>System Role</th>
                  <th>Status</th>
                  <th>Last Login</th>
                </tr>
              </thead>
              <tbody>
                @forelse($company->users as $u)
                  <tr>
                    <td class="fw-semibold">{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td><span class="badge bg-info-subtle text-info">{{ $u->role }}</span></td>
                    <td>
                      <span class="badge {{ $u->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                        {{ ucfirst($u->status) }}
                      </span>
                    </td>
                    <td><small class="text-muted">{{ $u->last_login_at ? $u->last_login_at->format('d M Y, h:i A') : 'Never' }}</small></td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center py-4 text-muted">No staff users recorded.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <!-- Subscription History Tab -->
        <div class="tab-pane fade" id="history-tab">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Plan Tier</th>
                  <th>Status</th>
                  <th>Billing Cycle</th>
                  <th>Amount Paid</th>
                  <th>Period</th>
                  <th>Payment Method</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @forelse($company->subscriptions as $sub)
                  <tr>
                    <td class="fw-semibold">{{ $sub->plan->name }}</td>
                    <td>
                      <span class="badge {{ $sub->status === 'active' ? 'bg-success' : ($sub->status === 'trial' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                        {{ ucfirst($sub->status) }}
                      </span>
                    </td>
                    <td>{{ ucfirst($sub->billing_cycle) }}</td>
                    <td>PKR {{ number_format($sub->amount_paid, 2) }}</td>
                    <td>
                      <small class="text-muted">
                        {{ $sub->starts_at->format('d M Y') }} &rarr; 
                        {{ $sub->ends_at ? $sub->ends_at->format('d M Y') : ($sub->trial_ends_at ? $sub->trial_ends_at->format('d M Y') . ' (Trial)' : 'Indefinite') }}
                      </small>
                    </td>
                    <td><small class="text-muted">{{ $sub->payment_method ?? 'None' }}</small></td>
                    <td><small class="text-muted">{{ Str::limit($sub->notes, 40) }}</small></td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center py-4 text-muted">No historical subscription records found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Change Plan -->
  <div class="modal fade" id="changePlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
        <form method="POST" action="{{ route('admin.companies.change-plan', $company) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Upgrade / Change SaaS Plan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Select New SaaS Plan Tier</label>
              <select name="plan_id" class="form-select" required>
                @foreach($plans as $p)
                  <option value="{{ $p->id }}" {{ $currentSubscription && $currentSubscription->saas_plan_id === $p->id ? 'selected' : '' }}>
                    {{ $p->name }} &mdash; Monthly: PKR {{ number_format($p->price_monthly) }} / Yearly: PKR {{ number_format($p->price_yearly) }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Billing Interval</label>
              <select name="billing_cycle" class="form-select" required>
                <option value="monthly" {{ $currentSubscription && $currentSubscription->billing_cycle === 'monthly' ? 'selected' : '' }}>Monthly Billing</option>
                <option value="yearly" {{ $currentSubscription && $currentSubscription->billing_cycle === 'yearly' ? 'selected' : '' }}>Yearly Billing (Discounted)</option>
              </select>
            </div>
            <div class="alert alert-info small mb-0">
              <i class="bi bi-info-circle me-1"></i>
              Changing the plan will immediately adjust the tenant's user, branch, and active contract limits.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Confirm Plan Change</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Extend Period -->
  <div class="modal fade" id="extendSubModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
        <form method="POST" action="{{ route('admin.companies.extend-subscription', $company) }}">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Extend Subscription / Trial Period</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Number of Days to Extend</label>
              <input type="number" name="days" class="form-control" value="30" min="1" max="365" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Administrative Reason / Note</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Courtesy trial extension, wire transfer verified..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success btn-sm">Extend License</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
