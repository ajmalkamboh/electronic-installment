<x-app-layout title="New Manual Journal Entry">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1">New Manual Journal Entry</h1>
      <p class="text-muted mb-0">Record adjustments, capital contributions, expenses, or bank transfers</p>
    </div>
    <div>
      <a href="{{ route('accounting.journal') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Journal
      </a>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('accounting.journal.store') }}" method="POST" id="journalForm">
    @csrf
    <!-- Header Details Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3">
        <h5 class="fw-bold mb-0">Entry Metadata</h5>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label small fw-bold">Posting Date <span class="text-danger">*</span></label>
            <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', date('Y-m-d')) }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-bold">Showroom Branch</label>
            <select name="branch_id" class="form-select">
              <option value="">Company Wide / HQ</option>
              @foreach($branches as $b)
                <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Transaction Description / Narrative <span class="text-danger">*</span></label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Owner capital injection via Meezan Bank" value="{{ old('description') }}" required>
          </div>
        </div>
      </div>
    </div>

    <!-- Line Items Table Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Journal Line Items (Debits &amp; Credits)</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
          <i class="bi bi-plus-circle me-1"></i>Add Row
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="itemsTable">
          <thead class="table-light">
            <tr>
              <th style="width: 35%;">Account <span class="text-danger">*</span></th>
              <th style="width: 30%;">Memo / Note</th>
              <th style="width: 15%;" class="text-end">Debit (PKR)</th>
              <th style="width: 15%;" class="text-end">Credit (PKR)</th>
              <th style="width: 5%;" class="text-center">Action</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            <!-- Row 1 -->
            <tr>
              <td>
                <select name="items[0][account_id]" class="form-select form-select-sm" required>
                  <option value="">Select Account...</option>
                  @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text" name="items[0][memo]" class="form-control form-select-sm" placeholder="Line note...">
              </td>
              <td>
                <input type="number" step="0.01" name="items[0][debit]" class="form-control form-control-sm text-end debit-input" value="0.00" oninput="calculateTotals()">
              </td>
              <td>
                <input type="number" step="0.01" name="items[0][credit]" class="form-control form-control-sm text-end credit-input" value="0.00" oninput="calculateTotals()">
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" disabled title="Minimum 2 rows required">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <!-- Row 2 -->
            <tr>
              <td>
                <select name="items[1][account_id]" class="form-select form-select-sm" required>
                  <option value="">Select Account...</option>
                  @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="text" name="items[1][memo]" class="form-control form-select-sm" placeholder="Line note...">
              </td>
              <td>
                <input type="number" step="0.01" name="items[1][debit]" class="form-control form-control-sm text-end debit-input" value="0.00" oninput="calculateTotals()">
              </td>
              <td>
                <input type="number" step="0.01" name="items[1][credit]" class="form-control form-control-sm text-end credit-input" value="0.00" oninput="calculateTotals()">
              </td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" disabled title="Minimum 2 rows required">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="2" class="text-end">TOTALS:</td>
              <td class="text-end font-monospace fs-6" id="totalDebitText">PKR 0.00</td>
              <td class="text-end font-monospace fs-6" id="totalCreditText">PKR 0.00</td>
              <td></td>
            </tr>
            <tr id="balanceStatusRow" class="table-warning text-center">
              <td colspan="5" class="py-2 small">
                <span id="balanceStatusText">Entry is not balanced. Discrepancy: PKR 0.00</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
        <span class="small text-muted">A valid journal transaction requires balanced debits and credits.</span>
        <button type="submit" id="submitBtn" class="btn btn-primary px-4" disabled>
          <i class="bi bi-check-circle me-1"></i>Post Journal Entry
        </button>
      </div>
    </div>
  </form>

  <script>
    let rowIndex = 2;

    function addRow() {
      const tbody = document.getElementById('itemsBody');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <select name="items[${rowIndex}][account_id]" class="form-select form-select-sm" required>
            <option value="">Select Account...</option>
            @foreach($accounts as $acc)
              <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
            @endforeach
          </select>
        </td>
        <td>
          <input type="text" name="items[${rowIndex}][memo]" class="form-control form-select-sm" placeholder="Line note...">
        </td>
        <td>
          <input type="number" step="0.01" name="items[${rowIndex}][debit]" class="form-control form-control-sm text-end debit-input" value="0.00" oninput="calculateTotals()">
        </td>
        <td>
          <input type="number" step="0.01" name="items[${rowIndex}][credit]" class="form-control form-control-sm text-end credit-input" value="0.00" oninput="calculateTotals()">
        </td>
        <td class="text-center">
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
      rowIndex++;
      updateRemoveButtons();
      calculateTotals();
    }

    function removeRow(button) {
      const row = button.closest('tr');
      row.remove();
      updateRemoveButtons();
      calculateTotals();
    }

    function updateRemoveButtons() {
      const rows = document.querySelectorAll('#itemsBody tr');
      rows.forEach(r => {
        const btn = r.querySelector('.btn-outline-danger');
        if (rows.length <= 2) {
          btn.disabled = true;
          btn.title = 'Minimum 2 rows required';
        } else {
          btn.disabled = false;
          btn.title = 'Delete row';
        }
      });
    }

    function calculateTotals() {
      let debits = 0;
      let credits = 0;

      document.querySelectorAll('.debit-input').forEach(input => {
        debits += parseFloat(input.value) || 0;
      });

      document.querySelectorAll('.credit-input').forEach(input => {
        credits += parseFloat(input.value) || 0;
      });

      debits = Math.round(debits * 100) / 100;
      credits = Math.round(credits * 100) / 100;

      document.getElementById('totalDebitText').textContent = 'PKR ' + debits.toLocaleString('en-US', { minimumFractionDigits: 2 });
      document.getElementById('totalCreditText').textContent = 'PKR ' + credits.toLocaleString('en-US', { minimumFractionDigits: 2 });

      const diff = Math.abs(debits - credits);
      const row = document.getElementById('balanceStatusRow');
      const text = document.getElementById('balanceStatusText');
      const submitBtn = document.getElementById('submitBtn');

      if (debits > 0 && diff < 0.01) {
        row.className = 'table-success text-center';
        text.innerHTML = '<i class="bi bi-check-circle me-1"></i><strong>Balanced:</strong> Total Debits equal Total Credits (PKR ' + debits.toFixed(2) + ')';
        submitBtn.disabled = false;
      } else {
        row.className = 'table-danger text-center';
        text.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i><strong>Unbalanced:</strong> Discrepancy of PKR ' + diff.toFixed(2) + ' between debits and credits';
        submitBtn.disabled = true;
      }
    }
  </script>
</x-app-layout>
