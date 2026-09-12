<x-app-layout title="Chart of Accounts">
  <!-- Header with Actions -->
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">Chart of Accounts (COA)</h1>
      <p class="text-muted mb-0">Standardized double-entry chart of accounts for retail installment showrooms</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAccountModal">
        <i class="bi bi-plus-circle me-1"></i>New Sub-Account
      </button>
      <a href="{{ route('accounting.journal') }}" class="btn btn-outline-secondary">
        <i class="bi bi-journal-bookmark me-1"></i>Journal Entries
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

  <!-- KPI Metrics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Total Accounts</span>
          <span class="fs-4 fw-bold text-dark">{{ $accounts->count() }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Assets</span>
          <span class="fs-4 fw-bold text-primary">{{ $grouped->get('asset', collect())->count() }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Liabilities</span>
          <span class="fs-4 fw-bold text-warning">{{ $grouped->get('liability', collect())->count() }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-info">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Equity</span>
          <span class="fs-4 fw-bold text-info">{{ $grouped->get('equity', collect())->count() }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Revenue</span>
          <span class="fs-4 fw-bold text-success">{{ $grouped->get('revenue', collect())->count() }}</span>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
        <div class="card-body p-3">
          <span class="text-muted small d-block">Expenses</span>
          <span class="fs-4 fw-bold text-danger">{{ $grouped->get('expense', collect())->count() }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ route('accounting.coa') }}" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Search by account code, name, category..." value="{{ $search }}">
          </div>
        </div>
        <div class="col-md-4">
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Account Classes</option>
            <option value="asset" {{ $typeFilter === 'asset' ? 'selected' : '' }}>1000 - Assets</option>
            <option value="liability" {{ $typeFilter === 'liability' ? 'selected' : '' }}>2000 - Liabilities</option>
            <option value="equity" {{ $typeFilter === 'equity' ? 'selected' : '' }}>3000 - Equity</option>
            <option value="revenue" {{ $typeFilter === 'revenue' ? 'selected' : '' }}>4000 - Revenue</option>
            <option value="expense" {{ $typeFilter === 'expense' ? 'selected' : '' }}>5000 - Expenses</option>
          </select>
        </div>
        <div class="col-md-3 text-md-end">
          <button type="submit" class="btn btn-sm btn-primary me-1">Filter</button>
          @if($search || $typeFilter)
            <a href="{{ route('accounting.coa') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <!-- Accounts Table -->
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold mb-0">General Ledger Accounts</h5>
      <span class="badge bg-light text-dark border">{{ $accounts->count() }} Accounts Active</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 120px;">Code</th>
            <th>Account Title</th>
            <th>Class</th>
            <th>Category</th>
            <th>Normal Balance</th>
            <th class="text-end">Current Balance</th>
            <th class="text-center" style="width: 120px;">Statement</th>
          </tr>
        </thead>
        <tbody>
          @forelse($accounts as $acc)
          <tr>
            <td>
              <span class="font-monospace fw-bold text-primary">{{ $acc->code }}</span>
              @if($acc->is_system)
                <span class="badge bg-light text-secondary border ms-1" title="System Protected Account" style="font-size: 10px;">SYS</span>
              @endif
            </td>
            <td>
              <div class="fw-semibold text-dark">{{ $acc->name }}</div>
              @if($acc->description)
                <small class="text-muted d-block text-truncate" style="max-width: 320px;">{{ $acc->description }}</small>
              @endif
            </td>
            <td>{!! $acc->type_badge !!}</td>
            <td>
              <span class="badge bg-light text-dark border text-uppercase" style="font-size: 10px;">{{ str_replace('_', ' ', $acc->category) }}</span>
            </td>
            <td>
              <span class="badge {{ $acc->normal_balance === 'debit' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-dark' }} border text-uppercase" style="font-size: 10px;">
                {{ $acc->normal_balance }}
              </span>
            </td>
            <td class="text-end fw-bold {{ $acc->current_balance < 0 ? 'text-danger' : 'text-dark' }}">
              PKR {{ number_format($acc->current_balance, 2) }}
            </td>
            <td class="text-center">
              <a href="{{ route('accounting.account-ledger', $acc) }}" class="btn btn-sm btn-outline-primary" title="View Account Ledger">
                <i class="bi bi-journal-text me-1"></i>Ledger
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No accounts found matching search criteria.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal: Create New Account -->
  <div class="modal fade" id="newAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form action="{{ route('accounting.accounts.store') }}" method="POST">
          @csrf
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title fs-6 fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Sub-Account</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small fw-bold">Account Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control" placeholder="e.g. 1025" required>
                <small class="text-muted">Unique numeric code</small>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Class / Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                  <option value="asset">1000 - Asset</option>
                  <option value="liability">2000 - Liability</option>
                  <option value="equity">3000 - Equity</option>
                  <option value="revenue">4000 - Revenue</option>
                  <option value="expense">5000 - Expense</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Account Name / Title <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Bank Alfalah Islamic Current Account" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
                <input type="text" name="category" class="form-control" placeholder="e.g. cash_and_bank" value="cash_and_bank" required>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold">Normal Balance <span class="text-danger">*</span></label>
                <select name="normal_balance" class="form-select" required>
                  <option value="debit">Debit</option>
                  <option value="credit">Credit</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Showroom Branch (Optional)</label>
                <select name="branch_id" class="form-select">
                  <option value="">Company Wide / Shared</option>
                  @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-bold">Description / Purpose</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief note on account usage..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Create Account</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</x-app-layout>
