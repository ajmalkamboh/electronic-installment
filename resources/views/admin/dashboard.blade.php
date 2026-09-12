<x-app-layout title="Platform Command Center - Super Admin">
  <!-- Header Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">Platform Super Admin Command Center</h1>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Super Admin</span>
      </div>
      <p class="text-muted mb-0">Multi-tenant portfolio health &bull; SaaS subscription tiers &bull; Platform quota telemetry</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-building-add me-1"></i>Onboard New Tenant
      </a>
      <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-tags me-1"></i>SaaS Plans
      </a>
      <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-receipt me-1"></i>Subscriptions Ledger
      </a>
    </div>
  </div>

  <!-- Primary Platform Metric Cards -->
  <div class="row g-3 mb-4">
    <!-- Total Tenants -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Total Onboarded Tenants</p>
              <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalCompanies) }}</h3>
              <small class="text-muted">
                <span class="text-success fw-semibold">{{ $activeCompanies }} Active</span> &bull; 
                <span class="text-warning fw-semibold">{{ $trialCompanies }} Trial</span>
              </small>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
              <i class="bi bi-buildings fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Monthly Recurring Revenue (MRR) -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Monthly Recurring Revenue</p>
              <h3 class="fw-bold mb-0 text-success">PKR {{ number_format($mrr, 0) }}</h3>
              <small class="text-muted">From {{ $activeCompanies }} paying tenants</small>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
              <i class="bi bi-cash-coin fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Total Platform Staff / Seats -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Platform User Seats</p>
              <h3 class="fw-bold mb-0 text-info">{{ number_format($totalUsers) }}</h3>
              <small class="text-muted">Active employees across all tenants</small>
            </div>
            <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
              <i class="bi bi-people fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Suspended Tenants -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <p class="text-muted small fw-semibold text-uppercase mb-1">Suspended Tenants</p>
              <h3 class="fw-bold mb-0 {{ $suspendedCompanies > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($suspendedCompanies) }}</h3>
              <small class="text-muted">Due to expiration / non-payment</small>
            </div>
            <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
              <i class="bi bi-shield-x fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Plan Breakdown & Expiring Subscriptions -->
  <div class="row g-4 mb-4">
    <!-- Plan Breakdown -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Subscription Tiers Breakdown</h5>
          <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">Manage Plans &rarr;</a>
        </div>
        <div class="card-body">
          @forelse($plans as $plan)
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                  <span class="fw-semibold">{{ $plan->name }}</span>
                  <small class="text-muted ms-1">(PKR {{ number_format($plan->price_monthly) }}/mo)</small>
                </div>
                <span class="badge bg-primary-subtle text-primary">{{ $plan->subscriptions_count }} Tenants</span>
              </div>
              <div class="progress" style="height: 8px;">
                @php
                  $pct = $totalCompanies > 0 ? ($plan->subscriptions_count / $totalCompanies) * 100 : 0;
                @endphp
                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <div class="d-flex justify-content-between text-muted small mt-1">
                <span>Max {{ $plan->max_users }} seats &bull; {{ $plan->max_branches }} branches</span>
                <span>{{ round($pct, 1) }}% of base</span>
              </div>
            </div>
          @empty
            <p class="text-muted text-center my-4">No active plans configured.</p>
          @endforelse
        </div>
      </div>
    </div>

    <!-- Upcoming Expirations -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Subscriptions Due for Renewal (14 Days)</h5>
          <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All &rarr;</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>Tenant Company</th>
                  <th>Current Plan</th>
                  <th>Status</th>
                  <th>Expires / Renews</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse($expiringSubscriptions as $sub)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $sub->company->name }}</div>
                      <small class="text-muted">{{ $sub->company->city }}</small>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle text-dark">{{ $sub->plan->name }}</span>
                    </td>
                    <td>
                      @if($sub->status === 'trial')
                        <span class="badge bg-warning text-dark">Trial</span>
                      @elseif($sub->status === 'active')
                        <span class="badge bg-success">Active</span>
                      @elseif($sub->status === 'past_due')
                        <span class="badge bg-danger">Past Due</span>
                      @else
                        <span class="badge bg-secondary">{{ ucfirst($sub->status) }}</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $targetDate = $sub->isTrial() ? $sub->trial_ends_at : $sub->ends_at;
                      @endphp
                      <div class="fw-semibold">{{ $targetDate ? $targetDate->format('d M Y') : 'N/A' }}</div>
                      <small class="text-muted">{{ $sub->daysRemaining() }} days remaining</small>
                    </td>
                    <td class="text-end">
                      <a href="{{ route('admin.companies.show', $sub->company) }}" class="btn btn-sm btn-outline-primary py-1 px-2">
                        Manage
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                      All tenant subscriptions are in good standing. No urgent renewals within 14 days.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Tenants Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Recent Tenant Companies</h5>
      <a href="{{ route('admin.companies.index') }}" class="btn btn-sm btn-primary">View All Tenants Directory &rarr;</a>
    </div>
    <div class="card-body p-0 mt-2">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Company Name</th>
              <th>City</th>
              <th>Contact Email</th>
              <th>Subscribed Plan</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentCompanies as $comp)
              @php
                $sub = $comp->currentSubscription();
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold">{{ $comp->name }}</div>
                  <small class="text-muted">{{ $comp->legal_name }}</small>
                </td>
                <td>{{ $comp->city ?? 'N/A' }}</td>
                <td>{{ $comp->email }}</td>
                <td>
                  @if($sub && $sub->plan)
                    <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $sub->plan->name }}</span>
                  @else
                    <span class="badge bg-secondary-subtle text-muted">No Plan</span>
                  @endif
                </td>
                <td>
                  @if($comp->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                  @elseif($comp->status === 'trial')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Trial</span>
                  @elseif($comp->status === 'suspended')
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                  @else
                    <span class="badge bg-secondary-subtle text-muted">{{ ucfirst($comp->status) }}</span>
                  @endif
                </td>
                <td>
                  <small class="text-muted">{{ $comp->created_at->format('d M Y') }}</small>
                </td>
                <td class="text-end">
                  <a href="{{ route('admin.companies.show', $comp) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                    <i class="bi bi-eye me-1"></i>Dossier
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No tenant companies registered yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>
