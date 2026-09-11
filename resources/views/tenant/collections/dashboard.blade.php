<x-app-layout title="Field Recovery & Collections">
  <!-- Header with Actions & Sub-navigation -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Field Recovery & Collections</h1>
      <p class="text-muted mb-0">Monitor delinquent accounts, field officer daily itineraries, promises-to-pay (PTP), and cash drawer handovers.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('collections.run-sheet') }}" class="btn btn-primary">
        <i class="bi bi-card-checklist me-1"></i>Daily Run Sheet
      </a>
      <a href="{{ route('collections.handovers') }}" class="btn btn-outline-success">
        <i class="bi bi-safe me-1"></i>Drawer Handovers
        @if($kpis['unsettled_count'] > 0)
          <span class="badge bg-danger ms-1">{{ $kpis['unsettled_count'] }}</span>
        @endif
      </a>
      <a href="{{ route('collections.assignments') }}" class="btn btn-outline-secondary">
        <i class="bi bi-person-lines-fill me-1"></i>Assignments Registry
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- KPI Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-danger-subtle text-danger p-3 fs-3">
            <i class="bi bi-exclamation-octagon"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Overdue Accounts</span>
            <h3 class="fw-bold mb-0 text-dark">{{ number_format($kpis['overdue_accounts']) }}</h3>
            <small class="text-danger">Accounts with missed dues</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary-subtle text-primary p-3 fs-3">
            <i class="bi bi-person-badge"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Active Field Assignments</span>
            <h3 class="fw-bold mb-0 text-primary">{{ number_format($kpis['active_assignments']) }}</h3>
            <small class="text-muted">Allocated to recovery officers</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-3 fs-3">
            <i class="bi bi-calendar-event"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Pending Promises-to-Pay</span>
            <h3 class="fw-bold mb-0 text-warning-emphasis">{{ number_format($kpis['pending_ptps']) }}</h3>
            <small class="text-muted">Customer commitments</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-4 d-flex align-items-center gap-3">
          <div class="rounded-circle bg-success-subtle text-success p-3 fs-3">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <span class="text-muted small d-block">Unsettled Field Cash</span>
            <h3 class="fw-bold mb-0 text-success">Rs. {{ number_format($kpis['unsettled_cash']) }}</h3>
            <small class="text-muted">{{ $kpis['unsettled_count'] }} collections in transit</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column: Pending PTPs & Action Queue -->
    <div class="col-lg-7">
      <!-- Pending Promises-to-Pay (PTP) Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-calendar-check text-warning me-2"></i>Upcoming Promises to Pay (PTP)
            </h5>
            <p class="text-muted small mb-0">Committed installment settlement dates requiring follow-up</p>
          </div>
        </div>
        <div class="card-body p-0 mt-3">
          @if($pendingPtps->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Customer & Phone</th>
                    <th>Account #</th>
                    <th>PTP Date</th>
                    <th>Promised Amount</th>
                    <th>Officer</th>
                    <th class="pe-4 text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingPtps as $ptp)
                    <tr>
                      <td class="ps-4">
                        <strong class="text-dark">{{ $ptp->customer->full_name }}</strong>
                        <div class="small text-muted font-monospace">{{ $ptp->customer->mobile_primary }}</div>
                      </td>
                      <td>
                        <a href="{{ route('agreements.show', $ptp->installment_agreement_id) }}" class="font-monospace text-decoration-none">
                          {{ $ptp->agreement->account_number }}
                        </a>
                      </td>
                      <td>
                        @php
                          $isPtpDue = $ptp->promise_to_pay_date->isPast();
                        @endphp
                        <span class="badge {{ $isPtpDue ? 'bg-danger' : 'bg-warning text-dark' }}">
                          {{ $ptp->promise_to_pay_date->format('d M, Y') }}
                        </span>
                      </td>
                      <td class="fw-bold text-success">
                        Rs. {{ number_format($ptp->promised_amount ?? 0) }}
                      </td>
                      <td class="small">{{ $ptp->collectionOfficer->name }}</td>
                      <td class="pe-4 text-end">
                        <a href="{{ route('payments.create', ['agreement_id' => $ptp->installment_agreement_id, 'amount' => $ptp->promised_amount]) }}"
                           class="btn btn-sm btn-outline-success" title="Record Collected Payment">
                          <i class="bi bi-cash"></i>
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4 text-muted">
              <i class="bi bi-calendar-check fs-2 d-block mb-2 text-secondary"></i>
              No pending customer commitments found.
            </div>
          @endif
        </div>
      </div>

      <!-- Delinquent Accounts Queue -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="fw-bold mb-0 text-dark">
              <i class="bi bi-clock-history text-danger me-2"></i>Delinquent Accounts Queue
            </h5>
            <p class="text-muted small mb-0">Active contracts with overdue installments requiring field recovery</p>
          </div>
          <a href="{{ route('collections.assignments') }}" class="btn btn-sm btn-outline-primary">
            Manage Assignments
          </a>
        </div>
        <div class="card-body p-0 mt-3">
          @if($delinquentAgreements->isNotEmpty())
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                  <tr>
                    <th class="ps-4">Purchaser</th>
                    <th>Account #</th>
                    <th>Merchandise</th>
                    <th>Overdue Balance</th>
                    <th>Assignment</th>
                    <th class="pe-4 text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($delinquentAgreements as $dag)
                    @php
                      $overdueBal = $dag->schedules->where('status', 'overdue')->sum('remaining_balance');
                    @endphp
                    <tr>
                      <td class="ps-4">
                        <div class="fw-bold text-dark">{{ $dag->customer->full_name }}</div>
                        <small class="text-muted">{{ $dag->customer->present_address }}</small>
                      </td>
                      <td class="font-monospace small">
                        <a href="{{ route('agreements.show', $dag->id) }}">{{ $dag->account_number }}</a>
                      </td>
                      <td class="small">{{ $dag->product->brand }} {{ $dag->product->model_name }}</td>
                      <td class="fw-bold text-danger">Rs. {{ number_format($overdueBal > 0 ? $overdueBal : $dag->installment_amount) }}</td>
                      <td>
                        @if($dag->activeAssignment)
                          <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                            <i class="bi bi-person me-1"></i>{{ $dag->activeAssignment->collectionOfficer->name }}
                          </span>
                        @else
                          <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Unassigned</span>
                        @endif
                      </td>
                      <td class="pe-4 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#quickAssignModal"
                                data-agreement-id="{{ $dag->id }}"
                                data-account-number="{{ $dag->account_number }}"
                                data-customer-name="{{ $dag->customer->full_name }}">
                          Assign
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="text-center py-4 text-muted">
              <i class="bi bi-shield-check text-success fs-2 d-block mb-2"></i>
              No delinquent accounts detected in current showroom.
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Right Column: Recent Field Activity & Officer Quick Actions -->
    <div class="col-lg-5">
      <!-- Recent Interaction Activity Log -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-activity text-primary me-2"></i>Recent Field Logs & Interactions
          </h5>
          <p class="text-muted small mb-0">Live feed of officer visits, calls, and customer interactions</p>
        </div>
        <div class="card-body p-4">
          @if($recentLogs->isNotEmpty())
            <ul class="list-unstyled mb-0">
              @foreach($recentLogs as $log)
                <li class="border-bottom pb-3 mb-3">
                  <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                      <strong class="text-dark">{{ $log->customer->full_name }}</strong>
                      <span class="text-muted small">&bull; {{ $log->agreement->account_number }}</span>
                    </div>
                    {!! $log->interaction_type_badge !!}
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    {!! $log->interaction_status_badge !!}
                    @if($log->isPtp())
                      {!! $log->ptp_badge !!}
                    @endif
                  </div>
                  @if($log->notes)
                    <div class="text-muted small fst-italic">"{{ Str::limit($log->notes, 90) }}"</div>
                  @endif
                  <div class="text-muted small mt-1 d-flex justify-content-between">
                    <span>Officer: <strong>{{ $log->collectionOfficer->name }}</strong></span>
                    <span>{{ $log->visit_date->diffForHumans() }}</span>
                  </div>
                </li>
              @endforeach
            </ul>
          @else
            <p class="text-muted text-center py-3 mb-0 small">No field logs recorded recently.</p>
          @endif
        </div>
      </div>

      <!-- Quick Links & Field Guidelines Card -->
      <div class="card border-0 shadow-sm bg-light">
        <div class="card-body p-4">
          <h6 class="fw-bold text-dark mb-2">
            <i class="bi bi-info-circle text-primary me-2"></i>Field Recovery Protocols (SOP)
          </h6>
          <ul class="small text-muted ps-3 mb-3">
            <li class="mb-1">Always record GPS check-in / location notes during home or business visits.</li>
            <li class="mb-1">Cash collected in the field immediately generates a <strong>submitted</strong> receipt for customer acknowledgment.</li>
            <li class="mb-1">Physical cash must be deposited with the Branch Cashier before 6:00 PM daily.</li>
            <li>In cases of dispute or refusal to pay, escalate to Showroom Manager for guarantor engagement.</li>
          </ul>
          <div class="d-grid gap-2">
            <a href="{{ route('collections.run-sheet') }}" class="btn btn-primary btn-sm">
              <i class="bi bi-geo me-1"></i>Open Mobile Run Sheet
            </a>
            <a href="{{ route('collections.handovers') }}" class="btn btn-outline-success btn-sm">
              <i class="bi bi-wallet2 me-1"></i>Cash Handover Settlement
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Quick Assign Account -->
  <div class="modal fade" id="quickAssignModal" tabindex="-1">
    <div class="modal-dialog">
      <form action="{{ route('collections.assignments.store') }}" method="POST" class="modal-content">
        @csrf
        <input type="hidden" name="installment_agreement_id" id="assign_agreement_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Assign Account to Field Officer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info small py-2 mb-3">
            Account: <strong id="assign_account_num">-</strong> &bull; Customer: <strong id="assign_customer_name">-</strong>
          </div>
          <div class="mb-3">
            <label for="collection_officer_id" class="form-label fw-semibold">Assign Collection Officer <span class="text-danger">*</span></label>
            <select name="collection_officer_id" id="collection_officer_id" class="form-select" required>
              <option value="">Select Field Recovery Officer</option>
              @foreach($officers as $off)
                <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->email }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="priority" class="form-label fw-semibold">Recovery Priority <span class="text-danger">*</span></label>
            <select name="priority" id="priority" class="form-select" required>
              <option value="normal">Normal Priority</option>
              <option value="high">High Priority</option>
              <option value="urgent">Urgent Escalation</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="notes" class="form-label fw-semibold">Instructions for Recovery Officer</label>
            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="e.g. Verify residence address, call guarantor before visit."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Confirm Assignment</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modal = document.getElementById('quickAssignModal');
      if (modal) {
        modal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          document.getElementById('assign_agreement_id').value = button.getAttribute('data-agreement-id');
          document.getElementById('assign_account_num').textContent = button.getAttribute('data-account-number');
          document.getElementById('assign_customer_name').textContent = button.getAttribute('data-customer-name');
        });
      }
    });
  </script>
</x-app-layout>
