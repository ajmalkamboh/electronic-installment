<x-app-layout title="Legal Documents & Print Center">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Legal Documents & Print Center</h1>
      <p class="text-muted mb-0">Unified repository for generating, customizing, and printing official Pakistani installment contracts, thermal receipts, guarantor affidavits, and NOCs.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('agreements.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark-text me-1"></i>All Contracts
      </a>
      <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-cash-stack me-1"></i>Payment Receipts
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Search & Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('documents.hub') }}" class="row g-3 align-items-center">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Search Contract / Customer</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Account #, Name, CNIC, Phone" value="{{ $search }}">
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label small text-muted mb-1">Showroom Branch</label>
          <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Showrooms</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ (string)$branchId === (string)$b->id ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-5 text-md-end pt-md-3">
          <button type="submit" class="btn btn-sm btn-primary me-1">
            <i class="bi bi-filter me-1"></i>Search
          </button>
          @if($search || $branchId)
            <a href="{{ route('documents.hub') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Agreements Directory with Document Center Links -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <h5 class="fw-bold mb-0">Installment Agreement Dossiers</h5>
          <small class="text-muted">Select an agreement to access its complete 10-document legal suite</small>
        </div>
        <span class="badge bg-light text-dark border p-2">
          {{ $agreements->total() }} Contracts Available
        </span>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Contract #</th>
            <th>Customer Name</th>
            <th>Merchandise</th>
            <th>Showroom</th>
            <th>Status</th>
            <th class="text-end">Payable Balance</th>
            <th class="text-center">Document Center</th>
          </tr>
        </thead>
        <tbody>
          @forelse($agreements as $ag)
            <tr>
              <td>
                <span class="fw-bold text-primary font-monospace">{{ $ag->account_number }}</span>
                <small class="text-muted d-block">{{ $ag->created_at->format('d M, Y') }}</small>
              </td>
              <td>
                <div class="fw-semibold">{{ $ag->customer?->full_name }}</div>
                <small class="text-muted font-monospace"><i class="bi bi-card-heading me-1"></i>{{ $ag->customer?->cnic }}</small>
              </td>
              <td>
                <div class="fw-semibold">{{ $ag->product?->name }}</div>
                <small class="text-muted font-monospace">{{ $ag->serializedItem?->serial_number ?? 'NON-SERIALIZED' }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">{{ $ag->branch?->name }}</span>
              </td>
              <td>
                {!! $ag->status_badge !!}
              </td>
              <td class="text-end fw-bold text-dark">
                PKR {{ number_format($ag->remaining_balance, 2) }}
              </td>
              <td class="text-center">
                <a href="{{ route('documents.agreement', $ag) }}" class="btn btn-sm btn-primary">
                  <i class="bi bi-folder2-open me-1"></i>Open Documents Suite
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-folder-x fs-1 text-muted d-block mb-2"></i>
                No installment agreements found matching search criteria.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($agreements->hasPages())
      <div class="card-footer bg-white py-3">
        {{ $agreements->links() }}
      </div>
    @endif
  </div>

  <!-- Recent Documents Print Audit Log -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
      <h5 class="fw-bold mb-0">Recent Document Generation & Print Audit Trail</h5>
      <small class="text-muted">Permanent chronological log of all documents rendered, printed, or exported across showrooms</small>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Document #</th>
            <th>Type</th>
            <th>Title</th>
            <th>Customer & Contract</th>
            <th>Printed By</th>
            <th>Showroom</th>
            <th>Timestamp</th>
            <th class="text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentDocs as $doc)
            <tr>
              <td class="fw-bold font-monospace text-primary">{{ $doc->document_number }}</td>
              <td>{!! $doc->type_badge !!}</td>
              <td class="small fw-semibold text-dark">{{ $doc->title }}</td>
              <td>
                <div class="fw-semibold">{{ $doc->customer?->full_name }}</div>
                <small class="text-muted font-monospace">{{ $doc->agreement?->account_number }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-person me-1"></i>{{ $doc->generatedBy?->name }}
                </span>
              </td>
              <td>{{ $doc->branch?->name }}</td>
              <td>
                <div>{{ $doc->created_at->format('d M, Y') }}</div>
                <small class="text-muted">{{ $doc->created_at->format('h:i A') }}</small>
              </td>
              <td class="text-center">
                <a href="{{ route('documents.agreement', $doc->installment_agreement_id) }}" class="btn btn-xs btn-outline-secondary">
                  <i class="bi bi-eye me-1"></i>View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                No documents generated or printed yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</x-app-layout>
