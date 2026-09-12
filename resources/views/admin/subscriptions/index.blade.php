<x-app-layout title="Platform Subscriptions Ledger - Super Admin">
  <!-- Header Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">Platform Subscriptions &amp; Billing Ledger</h1>
        <span class="badge bg-secondary-subtle text-dark border">{{ $subscriptions->total() }} Records</span>
      </div>
      <p class="text-muted mb-0">Master multi-tenant licensing &bull; Payment tracking &bull; Renewal extensions</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Command Center
      </a>
      <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-buildings me-1"></i>Tenants Directory
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tenant company name..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select form-select-sm">
            <option value="">All Subscription Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Free Trial</option>
            <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>Past Due (Grace)</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="plan_id" class="form-select form-select-sm">
            <option value="">All SaaS Tiers</option>
            @foreach($plans as $p)
              <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-sm btn-dark flex-grow-1">Filter</button>
          @if(request()->anyFilled(['search', 'status', 'plan_id']))
            <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Subscriptions Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light small">
            <tr>
              <th>Tenant Company</th>
              <th>Plan Tier</th>
              <th>Status</th>
              <th>Billing Cadence</th>
              <th>Amount (PKR)</th>
              <th>Effective Period</th>
              <th>Payment Channel</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subscriptions as $sub)
              @php
                $targetDate = $sub->isTrial() ? $sub->trial_ends_at : $sub->ends_at;
              @endphp
              <tr>
                <td>
                  <a href="{{ route('admin.companies.show', $sub->company) }}" class="fw-semibold text-decoration-none">
                    {{ $sub->company->name }}
                  </a>
                  <small class="d-block text-muted">{{ $sub->company->city }}</small>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    {{ $sub->plan->name }}
                  </span>
                </td>
                <td>
                  @if($sub->status === 'active')
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                  @elseif($sub->status === 'trial')
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Trial</span>
                  @elseif($sub->status === 'past_due')
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Past Due</span>
                  @elseif($sub->status === 'suspended')
                    <span class="badge bg-danger text-white">Suspended</span>
                  @else
                    <span class="badge bg-secondary-subtle text-muted">{{ ucfirst($sub->status) }}</span>
                  @endif
                </td>
                <td>{{ ucfirst($sub->billing_cycle) }}</td>
                <td class="fw-semibold">PKR {{ number_format($sub->amount_paid, 2) }}</td>
                <td>
                  <small class="text-muted">
                    {{ $sub->starts_at->format('d M Y') }} &rarr;
                    <strong class="{{ $sub->daysRemaining() <= 7 ? 'text-danger' : 'text-dark' }}">
                      {{ $targetDate ? $targetDate->format('d M Y') : 'Indefinite' }}
                    </strong>
                    <br>
                    <span>({{ $sub->daysRemaining() }} days left)</span>
                  </small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    {{ $sub->payment_method ? ucwords(str_replace('_', ' ', $sub->payment_method)) : 'Unrecorded' }}
                  </span>
                </td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $sub->id }}" title="Record Payment / Renew">
                    <i class="bi bi-cash-stack me-1"></i>Renew
                  </button>

                  <!-- Payment Record Modal -->
                  <div class="modal fade" id="paymentModal{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog text-start">
                      <div class="modal-content border-0 shadow">
                        <form method="POST" action="{{ route('admin.subscriptions.record-payment', $sub) }}">
                          @csrf
                          <div class="modal-header">
                            <h5 class="modal-title fw-bold">Record Subscription Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="p-3 bg-light rounded mb-3 border">
                              <strong>Tenant:</strong> {{ $sub->company->name }}<br>
                              <strong>Current Plan:</strong> {{ $sub->plan->name }} (PKR {{ number_format($sub->plan->price_monthly) }}/mo)
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-semibold">Amount Received (PKR)</label>
                              <input type="number" step="0.01" name="amount_paid" class="form-control" value="{{ $sub->plan->price_monthly }}" required>
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-semibold">Renewal Duration (Months)</label>
                              <select name="months" class="form-select" required>
                                <option value="1">1 Month</option>
                                <option value="3">3 Months (Quarterly)</option>
                                <option value="6">6 Months (Half-Yearly)</option>
                                <option value="12">12 Months (Annual)</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-semibold">Payment Channel</label>
                              <select name="payment_method" class="form-select" required>
                                <option value="bank_transfer">Direct Bank Transfer / Wire</option>
                                <option value="cheque">Company Cheque / Pay Order</option>
                                <option value="cash">Cash Collection</option>
                                <option value="online_gateway">Online Payment Gateway</option>
                              </select>
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-semibold">Notes / Transaction Reference</label>
                              <input type="text" name="notes" class="form-control" placeholder="e.g. Meezan Bank Ref #9812903">
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success btn-sm">Confirm Payment &amp; Extend</button>
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
                  No subscription records found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($subscriptions->hasPages())
      <div class="card-footer bg-transparent border-0 pt-3">
        {{ $subscriptions->links() }}
      </div>
    @endif
  </div>
</x-app-layout>
