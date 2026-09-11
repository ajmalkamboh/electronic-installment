<x-app-layout title="Collection Assignments Registry">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Collection Account Assignments</h1>
      <p class="text-muted mb-0">Manage allocation of installment contracts to recovery and field collection officers.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
        <i class="bi bi-person-plus me-1"></i>Assign New Account
      </button>
      <a href="{{ route('collections.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Command Center
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

  <!-- Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
      <form action="{{ route('collections.assignments') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">All Assignment Statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Assignments</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed / Settled</option>
            <option value="reassigned" {{ request('status') === 'reassigned' ? 'selected' : '' }}>Reassigned</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="officer_id" class="form-select">
            <option value="">All Collection Officers</option>
            @foreach($officers as $off)
              <option value="{{ $off->id }}" {{ request('officer_id') == $off->id ? 'selected' : '' }}>
                {{ $off->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <select name="priority" class="form-select">
            <option value="">All Priorities</option>
            <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
            <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
          <a href="{{ route('collections.assignments') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Assignments Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted small text-uppercase">
            <tr>
              <th class="ps-4">Account #</th>
              <th>Customer</th>
              <th>Merchandise</th>
              <th>Assigned Officer</th>
              <th>Assigned By</th>
              <th>Assigned Date</th>
              <th>Priority</th>
              <th>Status</th>
              <th class="pe-4 text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($assignments as $asgn)
              <tr>
                <td class="ps-4 font-monospace fw-bold">
                  <a href="{{ route('agreements.show', $asgn->installment_agreement_id) }}" class="text-decoration-none">
                    {{ $asgn->agreement->account_number }}
                  </a>
                </td>
                <td>
                  <div class="fw-bold text-dark">{{ $asgn->agreement->customer->full_name }}</div>
                  <small class="text-muted font-monospace">{{ $asgn->agreement->customer->mobile_primary }}</small>
                </td>
                <td class="small">
                  {{ $asgn->agreement->product->brand }} {{ $asgn->agreement->product->model_name }}
                </td>
                <td>
                  <span class="fw-semibold text-dark">{{ $asgn->collectionOfficer->name }}</span>
                </td>
                <td class="small text-muted">{{ $asgn->assignedBy->name }}</td>
                <td class="small">{{ $asgn->assigned_date->format('d M, Y') }}</td>
                <td>{!! $asgn->priority_badge !!}</td>
                <td>{!! $asgn->status_badge !!}</td>
                <td class="pe-4 text-end">
                  <a href="{{ route('agreements.show', $asgn->installment_agreement_id) }}" class="btn btn-sm btn-outline-primary" title="View Agreement">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="bi bi-person-lines-fill fs-1 text-secondary d-block mb-3"></i>
                  <h5 class="fw-bold">No Collection Assignments Found</h5>
                  <p class="mb-3">Assign overdue or active installment accounts to field recovery officers.</p>
                  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                    <i class="bi bi-plus-circle me-1"></i>Create First Assignment
                  </button>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($assignments->hasPages())
      <div class="card-footer bg-transparent border-0 p-3">
        {{ $assignments->links() }}
      </div>
    @endif
  </div>

  <!-- Modal: New Assignment -->
  <div class="modal fade" id="newAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <form action="{{ route('collections.assignments.store') }}" method="POST" class="modal-content">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Allocate Installment Account to Recovery Officer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="installment_agreement_id" class="form-label fw-semibold">Select Installment Account <span class="text-danger">*</span></label>
            <select name="installment_agreement_id" id="installment_agreement_id" class="form-select" required>
              <option value="">Select Account</option>
              @foreach($availableAgreements as $avail)
                <option value="{{ $avail->id }}">
                  {{ $avail->account_number }} &bull; {{ $avail->customer->full_name }} &bull; {{ $avail->product->brand }} {{ $avail->product->model_name }} (Bal: Rs. {{ number_format($avail->remaining_balance) }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label for="modal_collection_officer_id" class="form-label fw-semibold">Assign Collection Officer <span class="text-danger">*</span></label>
              <select name="collection_officer_id" id="modal_collection_officer_id" class="form-select" required>
                <option value="">Select Officer</option>
                @foreach($officers as $off)
                  <option value="{{ $off->id }}">{{ $off->name }} ({{ $off->role }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label for="modal_priority" class="form-label fw-semibold">Assignment Priority <span class="text-danger">*</span></label>
              <select name="priority" id="modal_priority" class="form-select" required>
                <option value="normal">Normal</option>
                <option value="high">High Priority</option>
                <option value="urgent">Urgent Escalation</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="modal_notes" class="form-label fw-semibold">Instructions for Recovery Officer</label>
            <textarea name="notes" id="modal_notes" rows="3" class="form-control"
                      placeholder="e.g. Verify current residence address, collect 1 overdue installment, obtain updated utility bill."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Allocate Account</button>
        </div>
      </form>
    </div>
  </div>
</x-app-layout>
