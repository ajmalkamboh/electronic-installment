<x-app-layout title="General Journal Entries">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">General Journal</h1>
      <p class="text-muted mb-0">Double-entry audit log of all financial transactions across showroom operations</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('accounting.journal.create') }}" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i>New Manual Journal
      </a>
      <a href="{{ route('accounting.coa') }}" class="btn btn-outline-secondary">
        <i class="bi bi-diagram-3 me-1"></i>Chart of Accounts
      </a>
      <a href="{{ route('accounting.trial-balance') }}" class="btn btn-outline-secondary">
        <i class="bi bi-calculator me-1"></i>Trial Balance
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Filters -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.journal') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by entry #, description..." value="{{ $search }}">
          </div>
        </div>
        <div class="col-md-3">
          <select name="reference_type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Transaction Types</option>
            <option value="down_payment" {{ $refType === 'down_payment' ? 'selected' : '' }}>Down Payment</option>
            <option value="disbursement" {{ $refType === 'disbursement' ? 'selected' : '' }}>Disbursement / Sale</option>
            <option value="installment_payment" {{ $refType === 'installment_payment' ? 'selected' : '' }}>Installment Collection</option>
            <option value="late_fee_accrual" {{ $refType === 'late_fee_accrual' ? 'selected' : '' }}>Late Fee Accrual</option>
            <option value="late_fee_waiver" {{ $refType === 'late_fee_waiver' ? 'selected' : '' }}>Late Fee Waiver</option>
            <option value="early_settlement" {{ $refType === 'early_settlement' ? 'selected' : '' }}>Early Payoff Settlement</option>
            <option value="repossession" {{ $refType === 'repossession' ? 'selected' : '' }}>Asset Repossession</option>
            <option value="write_off" {{ $refType === 'write_off' ? 'selected' : '' }}>Bad Debt Write-off</option>
            <option value="manual_journal" {{ $refType === 'manual_journal' ? 'selected' : '' }}>Manual Journal Entry</option>
          </select>
        </div>
        <div class="col-md-3">
          <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-2 text-md-end">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($search || $refType || $date)
            <a href="{{ route('accounting.journal') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Journal Entries Table -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">Posted Journal Entries</h5>
      <span class="badge bg-light text-dark border">{{ $entries->total() }} Total Entries</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 150px;">Entry Number</th>
            <th style="width: 110px;">Date</th>
            <th>Description &amp; Reference</th>
            <th>Showroom Branch</th>
            <th class="text-end" style="width: 140px;">Debit (PKR)</th>
            <th class="text-end" style="width: 140px;">Credit (PKR)</th>
            <th class="text-center" style="width: 90px;">Status</th>
            <th class="text-center" style="width: 80px;">Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($entries as $entry)
          <tr>
            <td>
              <span class="font-monospace fw-bold text-primary">{{ $entry->entry_number }}</span>
            </td>
            <td>
              <div class="small fw-semibold">{{ $entry->entry_date->format('d M, Y') }}</div>
              <small class="text-muted">{{ $entry->created_at->format('H:i') }}</small>
            </td>
            <td>
              <div class="fw-semibold text-dark">{{ $entry->description }}</div>
              <span class="badge bg-light text-secondary border text-uppercase" style="font-size: 10px;">
                {{ str_replace('_', ' ', $entry->reference_type) }}
              </span>
              @if($entry->postedBy)
                <small class="text-muted ms-2">&bull; by {{ $entry->postedBy->name }}</small>
              @endif
            </td>
            <td>
              <span class="badge bg-light text-dark border">{{ $entry->branch?->name ?? 'Company Wide' }}</span>
            </td>
            <td class="text-end font-monospace fw-bold text-dark">
              {{ number_format($entry->total_debit, 2) }}
            </td>
            <td class="text-end font-monospace fw-bold text-dark">
              {{ number_format($entry->total_credit, 2) }}
            </td>
            <td class="text-center">{!! $entry->status_badge !!}</td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#entryModal{{ $entry->id }}" title="View Journal Items">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>

          <!-- Entry Detail Modal -->
          <div class="modal fade" id="entryModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                  <div>
                    <h5 class="modal-title fs-6 fw-bold mb-0">Journal Entry #{{ $entry->entry_number }}</h5>
                    <small class="text-light">{{ $entry->entry_date->format('d F, Y') }} &bull; {{ $entry->description }}</small>
                  </div>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                  <table class="table table-bordered mb-0" style="font-size: 12.5px;">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 100px;">Code</th>
                        <th>Account Name</th>
                        <th>Memo</th>
                        <th class="text-end" style="width: 130px;">Debit (PKR)</th>
                        <th class="text-end" style="width: 130px;">Credit (PKR)</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($entry->items as $item)
                      <tr>
                        <td class="font-monospace fw-bold">{{ $item->account->code }}</td>
                        <td>
                          <strong>{{ $item->account->name }}</strong>
                          <span class="badge bg-light text-muted border ms-1" style="font-size: 9px;">{{ strtoupper($item->account->type) }}</span>
                        </td>
                        <td class="small text-muted">{{ $item->memo ?? '-' }}</td>
                        <td class="text-end font-monospace {{ $item->debit > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                          {{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}
                        </td>
                        <td class="text-end font-monospace {{ $item->credit > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                          {{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                      <tr>
                        <td colspan="3" class="text-end">TOTAL:</td>
                        <td class="text-end font-monospace">PKR {{ number_format($entry->total_debit, 2) }}</td>
                        <td class="text-end font-monospace">PKR {{ number_format($entry->total_credit, 2) }}</td>
                      </tr>
                      @if($entry->isBalanced())
                      <tr class="table-success text-center">
                        <td colspan="5" class="small text-success fw-bold py-1">
                          <i class="bi bi-check-circle me-1"></i>Double-Entry Invariant Verified (Balanced: Debits == Credits)
                        </td>
                      </tr>
                      @else
                      <tr class="table-danger text-center">
                        <td colspan="5" class="small text-danger fw-bold py-1">
                          <i class="bi bi-exclamation-triangle me-1"></i>Unbalanced Entry Detected!
                        </td>
                      </tr>
                      @endif
                    </tfoot>
                  </table>
                </div>
                <div class="modal-footer bg-light py-2">
                  <span class="small text-muted me-auto">Reference: {{ strtoupper(str_replace('_', ' ', $entry->reference_type)) }}</span>
                  <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
          @empty
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">No journal entries found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-white border-top py-3">
      {{ $entries->links() }}
    </div>
    @endif
  </div>
</x-app-layout>
