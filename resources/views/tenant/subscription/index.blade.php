<x-app-layout title="Subscription & Plan Limits">
  <!-- Header Bar -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h1 class="h3 fw-bold mb-0">SaaS Subscription &amp; Plan Limits</h1>
        @if($company->isSuspended())
          <span class="badge bg-danger text-white px-2 py-1">Account Suspended</span>
        @elseif($company->status === 'trial')
          <span class="badge bg-warning text-dark px-2 py-1">Free Trial</span>
        @else
          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active License</span>
        @endif
      </div>
      <p class="text-muted mb-0">Tenant license details &bull; Real-time quota capacity monitoring &bull; Tier upgrades</p>
    </div>
  </div>

  @if($company->isSuspended())
    <div class="alert alert-danger d-flex align-items-start p-3 mb-4 shadow-sm" role="alert">
      <i class="bi bi-shield-x fs-3 me-3 text-danger"></i>
      <div>
        <h5 class="alert-heading fw-bold mb-1">Your Subscription is Currently Suspended</h5>
        <p class="mb-2">
          Your account is past its subscription renewal date by more than 7 days. Standard operational features (creating installment agreements, receiving payments, managing stock) are locked across all showroom branches until your license is renewed.
        </p>
        <div class="d-flex gap-2">
          <a href="#plans-comparison" class="btn btn-danger btn-sm">
            <i class="bi bi-credit-card me-1"></i>Upgrade / Renew Plan Now
          </a>
          <a href="mailto:support@installment.test" class="btn btn-outline-danger btn-sm">
            Contact Enterprise Support
          </a>
        </div>
      </div>
    </div>
  @endif

  <!-- Top Row: Current Plan & Quota Meters -->
  <div class="row g-4 mb-4">
    <!-- Current Plan Card -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Active Plan Details</h5>
          @if($subscription)
            <span class="badge {{ $subscription->isActive() ? 'bg-success' : 'bg-warning text-dark' }}">
              {{ ucfirst($subscription->status) }}
            </span>
          @endif
        </div>
        <div class="card-body">
          @if($currentPlan)
            <div class="p-3 bg-light rounded mb-3 border">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Subscription Plan</span>
                <span class="h4 fw-bold text-primary mb-0">{{ $currentPlan->name }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Pricing Rate</span>
                <span class="fw-semibold">PKR {{ number_format($currentPlan->price_monthly) }} <small class="text-muted">/ month</small></span>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Billing Interval</span>
                <span class="fw-semibold">{{ ucfirst($subscription->billing_cycle ?? 'monthly') }}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted small">Valid Until</span>
                @php
                  $target = $subscription ? ($subscription->isTrial() ? $subscription->trial_ends_at : $subscription->ends_at) : null;
                @endphp
                <span class="fw-bold {{ $subscription && $subscription->daysRemaining() <= 7 ? 'text-danger' : 'text-dark' }}">
                  {{ $target ? $target->format('d M Y') : 'Active' }}
                  @if($subscription)
                    <small class="d-block text-muted text-end">({{ $subscription->daysRemaining() }} days left)</small>
                  @endif
                </span>
              </div>
            </div>

            <h6 class="fw-bold small text-uppercase text-muted mb-2">Feature Entitlements</h6>
            <div class="row g-2 mb-3">
              @foreach($featuresCatalog as $key => $name)
                @php
                  $hasFeat = $currentPlan->hasFeature($key);
                @endphp
                <div class="col-sm-6">
                  <div class="d-flex align-items-center small {{ $hasFeat ? 'text-dark fw-medium' : 'text-muted opacity-50' }}">
                    <i class="bi {{ $hasFeat ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-secondary' }} me-2"></i>
                    <span>{{ $name }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div class="alert alert-warning mb-0">No active plan assigned to this account.</div>
          @endif
        </div>
      </div>
    </div>

    <!-- Quota Consumption Meters -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Resource Quota Utilization</h5>
          <span class="badge bg-light text-muted border small">Enforced by Plan Engine</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <!-- User Seats -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100 {{ $quotaUsage['users']['is_maxed'] ? 'border-danger bg-danger-subtle bg-opacity-10' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-people me-1 text-primary"></i>Staff User Seats</span>
                  <span class="badge {{ $quotaUsage['users']['is_maxed'] ? 'bg-danger' : 'bg-primary-subtle text-primary' }}">
                    {{ $quotaUsage['users']['used'] }} / {{ $quotaUsage['users']['limit'] }} Seats
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['users']['is_maxed'] ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $quotaUsage['users']['percentage'] }}%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                  <span>{{ $quotaUsage['users']['percentage'] }}% capacity</span>
                  @if($quotaUsage['users']['is_maxed'])
                    <span class="text-danger fw-semibold">Quota Exceeded</span>
                  @else
                    <span>{{ $quotaUsage['users']['limit'] - $quotaUsage['users']['used'] }} seats remaining</span>
                  @endif
                </div>
              </div>
            </div>

            <!-- Showroom Outlets -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100 {{ $quotaUsage['branches']['is_maxed'] ? 'border-danger bg-danger-subtle bg-opacity-10' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-shop me-1 text-info"></i>Showroom Outlets</span>
                  <span class="badge {{ $quotaUsage['branches']['is_maxed'] ? 'bg-danger' : 'bg-info-subtle text-info' }}">
                    {{ $quotaUsage['branches']['used'] }} / {{ $quotaUsage['branches']['limit'] }} Outlets
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['branches']['is_maxed'] ? 'bg-danger' : 'bg-info' }}" style="width: {{ $quotaUsage['branches']['percentage'] }}%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                  <span>{{ $quotaUsage['branches']['percentage'] }}% capacity</span>
                  @if($quotaUsage['branches']['is_maxed'])
                    <span class="text-danger fw-semibold">Limit Reached</span>
                  @else
                    <span>{{ $quotaUsage['branches']['limit'] - $quotaUsage['branches']['used'] }} branches remaining</span>
                  @endif
                </div>
              </div>
            </div>

            <!-- Active Agreements -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100 {{ $quotaUsage['agreements']['is_maxed'] ? 'border-danger bg-danger-subtle bg-opacity-10' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-file-earmark-text me-1 text-success"></i>Active Agreements</span>
                  <span class="badge {{ $quotaUsage['agreements']['is_maxed'] ? 'bg-danger' : 'bg-success-subtle text-success' }}">
                    {{ $quotaUsage['agreements']['used'] }} / {{ $quotaUsage['agreements']['limit'] }} Contracts
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['agreements']['is_maxed'] ? 'bg-danger' : 'bg-success' }}" style="width: {{ $quotaUsage['agreements']['percentage'] }}%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                  <span>{{ $quotaUsage['agreements']['percentage'] }}% capacity</span>
                  @if($quotaUsage['agreements']['is_maxed'])
                    <span class="text-danger fw-semibold">Cap Reached</span>
                  @else
                    <span>{{ $quotaUsage['agreements']['limit'] - $quotaUsage['agreements']['used'] }} active slots</span>
                  @endif
                </div>
              </div>
            </div>

            <!-- Monthly Transactions -->
            <div class="col-md-6">
              <div class="p-3 border rounded h-100 {{ $quotaUsage['transactions']['is_maxed'] ? 'border-danger bg-danger-subtle bg-opacity-10' : '' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold small"><i class="bi bi-receipt me-1 text-warning"></i>Monthly Receipts</span>
                  <span class="badge {{ $quotaUsage['transactions']['is_maxed'] ? 'bg-danger' : 'bg-warning-subtle text-dark' }}">
                    {{ $quotaUsage['transactions']['used'] }} / {{ $quotaUsage['transactions']['limit'] }}
                  </span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                  <div class="progress-bar {{ $quotaUsage['transactions']['is_maxed'] ? 'bg-danger' : 'bg-warning' }}" style="width: {{ $quotaUsage['transactions']['percentage'] }}%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                  <span>{{ $quotaUsage['transactions']['percentage'] }}% used this month</span>
                  @if($quotaUsage['transactions']['is_maxed'])
                    <span class="text-danger fw-semibold">Monthly Cap Reached</span>
                  @else
                    <span>Resets on 1st of month</span>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Plan Comparison Grid -->
  <div class="card border-0 shadow-sm" id="plans-comparison">
    <div class="card-header bg-transparent border-0 pt-3 pb-0">
      <h4 class="fw-bold mb-1">Available SaaS Subscription Plans</h4>
      <p class="text-muted mb-0">Upgrade your organization capacity seamlessly as your retail business expands</p>
    </div>
    <div class="card-body">
      <div class="row g-4 pt-2">
        @foreach($plans as $plan)
          @php
            $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
          @endphp
          <div class="col-xl-3 col-md-6">
            <div class="card h-100 {{ $isCurrent ? 'border-primary border-2 shadow' : 'border shadow-sm' }}">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="fw-bold mb-0 text-dark">{{ $plan->name }}</h5>
                  @if($isCurrent)
                    <span class="badge bg-primary">Current Plan</span>
                  @endif
                </div>
                <p class="text-muted small mb-3">{{ $plan->description }}</p>

                <div class="mb-4">
                  <span class="h3 fw-bold text-dark">PKR {{ number_format($plan->price_monthly) }}</span>
                  <span class="text-muted small">/ mo</span>
                  <div class="text-muted small">PKR {{ number_format($plan->price_yearly) }} / yr billed annually</div>
                </div>

                <div class="p-3 bg-light rounded small mb-4">
                  <div class="d-flex justify-content-between mb-1">
                    <span>Staff Seats:</span>
                    <strong>{{ $plan->max_users }} users</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-1">
                    <span>Showrooms:</span>
                    <strong>{{ $plan->max_branches }} branch{{ $plan->max_branches > 1 ? 'es' : '' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between mb-1">
                    <span>Active Contracts:</span>
                    <strong>{{ number_format($plan->max_active_agreements) }} max</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>Monthly Receipts:</span>
                    <strong>{{ number_format($plan->max_monthly_transactions) }}/mo</strong>
                  </div>
                </div>

                <ul class="list-unstyled small mb-4 flex-grow-1">
                  @php $feats = $plan->features ?? []; @endphp
                  <li class="mb-2 text-muted">
                    <i class="bi bi-check2 text-success me-2 fs-6"></i>Standard POS &amp; Legal Docs
                  </li>
                  <li class="mb-2 text-muted">
                    <i class="bi {{ in_array('sms_notifications', $feats) || in_array('*', $feats) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-2 fs-6"></i>SMS Gateway Alerts
                  </li>
                  <li class="mb-2 text-muted">
                    <i class="bi {{ in_array('whatsapp_notifications', $feats) || in_array('*', $feats) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-2 fs-6"></i>WhatsApp Cloud Dispatch
                  </li>
                  <li class="mb-2 text-muted">
                    <i class="bi {{ in_array('inventory_transfers', $feats) || in_array('*', $feats) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-2 fs-6"></i>Multi-Branch Stock Gate Passes
                  </li>
                  <li class="mb-2 text-muted">
                    <i class="bi {{ in_array('general_ledger', $feats) || in_array('*', $feats) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-2 fs-6"></i>Double-Entry General Ledger
                  </li>
                  <li class="mb-2 text-muted">
                    <i class="bi {{ in_array('advanced_analytics', $feats) || in_array('*', $feats) ? 'bi-check2 text-success' : 'bi-x text-secondary opacity-50' }} me-2 fs-6"></i>Executive Analytics PAR 30/60/90
                  </li>
                </ul>

                @if($isCurrent)
                  <button class="btn btn-outline-secondary w-100" disabled>Active Subscription</button>
                @else
                  <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#upgradeModal{{ $plan->id }}">
                    Upgrade to {{ $plan->name }}
                  </button>

                  <!-- Upgrade Modal -->
                  <div class="modal fade" id="upgradeModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                      <div class="modal-content border-0 shadow">
                        <form method="POST" action="{{ route('subscription.upgrade') }}">
                          @csrf
                          <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                          <div class="modal-header">
                            <h5 class="modal-title fw-bold">Upgrade to {{ $plan->name }} Plan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <div class="p-3 bg-light rounded mb-3 border">
                              <h6 class="fw-bold text-primary mb-1">{{ $plan->name }} Plan</h6>
                              <p class="text-muted small mb-0">{{ $plan->description }}</p>
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-semibold">Choose Billing Frequency</label>
                              <div class="form-check p-2 border rounded mb-2">
                                <input class="form-check-input ms-0 me-2" type="radio" name="billing_cycle" id="cycle_monthly_{{ $plan->id }}" value="monthly" checked>
                                <label class="form-check-label fw-semibold" for="cycle_monthly_{{ $plan->id }}">
                                  Monthly Billing &mdash; PKR {{ number_format($plan->price_monthly) }} / month
                                </label>
                              </div>
                              <div class="form-check p-2 border rounded">
                                <input class="form-check-input ms-0 me-2" type="radio" name="billing_cycle" id="cycle_yearly_{{ $plan->id }}" value="yearly">
                                <label class="form-check-label fw-semibold" for="cycle_yearly_{{ $plan->id }}">
                                  Yearly Billing (Save 17%) &mdash; PKR {{ number_format($plan->price_yearly) }} / year
                                </label>
                              </div>
                            </div>

                            <div class="alert alert-info small mb-0">
                              <i class="bi bi-info-circle me-1"></i>
                              Your new capacity limits ({{ $plan->max_users }} seats, {{ $plan->max_branches }} branches, {{ $plan->max_active_agreements }} contracts) will activate immediately.
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Confirm &amp; Activate Plan</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</x-app-layout>
