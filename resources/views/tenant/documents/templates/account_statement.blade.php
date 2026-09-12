<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statement of Account - {{ $agreement->account_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12.5px;
      color: #222;
      line-height: 1.45;
    }
    .doc-page {
      background: #fff;
      max-width: 880px;
      margin: 25px auto;
      padding: 35px 45px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .header-rule {
      border-bottom: 2px solid #000;
      padding-bottom: 12px;
      margin-bottom: 15px;
    }
    .section-title {
      background: #f1f3f5;
      padding: 5px 10px;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 11.5px;
      border-left: 4px solid #0d6efd;
      margin-top: 15px;
      margin-bottom: 8px;
    }
    .stat-card {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 10px;
      text-align: center;
    }
    .stat-title {
      font-size: 11px;
      text-transform: uppercase;
      color: #6c757d;
      font-weight: 600;
    }
    .stat-value {
      font-size: 15px;
      font-weight: 700;
      color: #212529;
    }
    .sign-box {
      border-top: 1px solid #000;
      margin-top: 40px;
      padding-top: 4px;
      font-weight: bold;
      text-align: center;
      font-size: 11px;
    }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .doc-page { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 12mm !important; max-width: 100% !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print text-center py-2 bg-light border-bottom">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Statement (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-primary">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; NTN: {{ $company->ntn_strn ?? 'N/A' }}</div>
          <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Tel: {{ $branch->phone }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-dark fs-6 p-2 text-uppercase mb-1">STATEMENT OF ACCOUNT</span>
          <div class="small fw-bold">Account: <span class="font-monospace">#{{ $agreement->account_number }}</span></div>
          <div class="small text-muted">Statement Date: {{ now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Customer & Merchandise Summary -->
    <div class="row mb-3">
      <div class="col-6">
        <table class="table table-sm table-bordered mb-0">
          <tr>
            <td class="bg-light fw-semibold" style="width: 35%;">Customer Name:</td>
            <td><strong>{{ $customer->full_name }}</strong></td>
          </tr>
          <tr>
            <td class="bg-light fw-semibold">CNIC / Mobile:</td>
            <td>{{ $customer->cnic }} &bull; {{ $customer->mobile_primary }}</td>
          </tr>
          <tr>
            <td class="bg-light fw-semibold">Residential Address:</td>
            <td>{{ $customer->present_address }}</td>
          </tr>
        </table>
      </div>
      <div class="col-6">
        <table class="table table-sm table-bordered mb-0">
          <tr>
            <td class="bg-light fw-semibold" style="width: 35%;">Item Financed:</td>
            <td><strong>{{ $product?->name ?? 'Merchandise' }}</strong></td>
          </tr>
          <tr>
            <td class="bg-light fw-semibold">Agreement Period:</td>
            <td>{{ $agreement->start_date ? \Carbon\Carbon::parse($agreement->start_date)->format('d-M-Y') : 'N/A' }} to {{ $agreement->end_date ? \Carbon\Carbon::parse($agreement->end_date)->format('d-M-Y') : 'N/A' }}</td>
          </tr>
          <tr>
            <td class="bg-light fw-semibold">Account Status:</td>
            <td><span class="badge bg-primary text-uppercase">{{ $agreement->status }}</span></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="row g-2 mb-3">
      <div class="col-3">
        <div class="stat-card">
          <div class="stat-title">Total Financed</div>
          <div class="stat-value text-dark">PKR {{ number_format($agreement->total_payable, 2) }}</div>
        </div>
      </div>
      <div class="col-3">
        <div class="stat-card">
          <div class="stat-title">Total Paid To Date</div>
          <div class="stat-value text-success">PKR {{ number_format($metrics['total_paid'], 2) }}</div>
        </div>
      </div>
      <div class="col-3">
        <div class="stat-card">
          <div class="stat-title">Accrued Late Fees</div>
          <div class="stat-value text-warning">PKR {{ number_format($metrics['accrued_late_fees'], 2) }}</div>
        </div>
      </div>
      <div class="col-3">
        <div class="stat-card bg-light border-danger">
          <div class="stat-title text-danger fw-bold">Net Outstanding</div>
          <div class="stat-value text-danger">PKR {{ number_format($metrics['net_outstanding'], 2) }}</div>
        </div>
      </div>
    </div>

    <!-- Section 1: Detailed Installment Schedule & Ledger -->
    <div class="section-title">1. Scheduled Installments &amp; Payment Ledger</div>
    <table class="table table-sm table-bordered mb-3" style="font-size: 11.5px;">
      <thead class="table-light">
        <tr class="text-center">
          <th>#</th>
          <th>Due Date</th>
          <th>Principal</th>
          <th>Markup</th>
          <th>Inst. Due</th>
          <th>Late Fee</th>
          <th>Paid Amount</th>
          <th>Paid Date</th>
          <th>Remaining</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($schedules as $sch)
        @php
          $statusBadge = match($sch->status) {
            'paid' => 'bg-success',
            'partial' => 'bg-warning text-dark',
            'overdue' => 'bg-danger',
            default => 'bg-secondary'
          };
        @endphp
        <tr class="align-middle">
          <td class="text-center fw-bold">{{ $sch->installment_number }}</td>
          <td class="text-center">{{ $sch->due_date ? \Carbon\Carbon::parse($sch->due_date)->format('d-M-Y') : 'N/A' }}</td>
          <td class="text-end">PKR {{ number_format($sch->principal_amount, 2) }}</td>
          <td class="text-end">PKR {{ number_format($sch->markup_amount, 2) }}</td>
          <td class="text-end fw-bold">PKR {{ number_format($sch->total_amount, 2) }}</td>
          <td class="text-end text-danger">{{ $sch->late_fee_amount > 0 ? 'PKR ' . number_format($sch->late_fee_amount, 2) : '-' }}</td>
          <td class="text-end text-success fw-bold">{{ $sch->paid_amount > 0 ? 'PKR ' . number_format($sch->paid_amount, 2) : '-' }}</td>
          <td class="text-center small">{{ $sch->paid_at ? \Carbon\Carbon::parse($sch->paid_at)->format('d-M-Y') : '-' }}</td>
          <td class="text-end">PKR {{ number_format($sch->remaining_balance, 2) }}</td>
          <td class="text-center"><span class="badge {{ $statusBadge }} text-uppercase" style="font-size: 10px;">{{ $sch->status }}</span></td>
        </tr>
        @empty
        <tr>
          <td colspan="10" class="text-center py-2 text-muted">No schedule records available.</td>
        </tr>
        @endforelse
      </tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td colspan="4" class="text-end">TOTALS:</td>
          <td class="text-end">PKR {{ number_format($schedules->sum('total_amount'), 2) }}</td>
          <td class="text-end text-danger">PKR {{ number_format($schedules->sum('late_fee_amount'), 2) }}</td>
          <td class="text-end text-success">PKR {{ number_format($schedules->sum('paid_amount'), 2) }}</td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
    </table>

    <!-- Section 2: Recent Payment Receipts Audit -->
    @if($payments->isNotEmpty())
    <div class="section-title">2. Receipts History &amp; Breakdown</div>
    <table class="table table-sm table-bordered mb-3" style="font-size: 11.5px;">
      <thead class="table-light">
        <tr>
          <th>Receipt #</th>
          <th>Payment Date</th>
          <th>Mode</th>
          <th>Reference #</th>
          <th class="text-end">Principal</th>
          <th class="text-end">Markup</th>
          <th class="text-end">Late Fee</th>
          <th class="text-end">Total Amount</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payments as $p)
        <tr>
          <td class="font-monospace fw-bold">{{ $p->payment_number }}</td>
          <td>{{ $p->payment_date ? \Carbon\Carbon::parse($p->payment_date)->format('d-M-Y') : 'N/A' }}</td>
          <td class="text-uppercase">{{ $p->payment_method }}</td>
          <td>{{ $p->reference_number ?? '-' }}</td>
          <td class="text-end">PKR {{ number_format($p->principal_paid, 2) }}</td>
          <td class="text-end">PKR {{ number_format($p->markup_paid, 2) }}</td>
          <td class="text-end text-danger">{{ $p->late_fee_paid > 0 ? 'PKR ' . number_format($p->late_fee_paid, 2) : '-' }}</td>
          <td class="text-end fw-bold text-success">PKR {{ number_format($p->amount, 2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <!-- Signatures -->
    <div class="row pt-3 text-center">
      <div class="col-4">
        <div class="sign-box">
          ACCOUNTS OFFICER<br>
          <span class="small text-muted">Prepared By</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          BRANCH MANAGER<br>
          <span class="small text-muted">Verified &amp; Stamped</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          CUSTOMER ACKNOWLEDGMENT<br>
          <span class="small text-muted">{{ $customer->full_name }}</span>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
