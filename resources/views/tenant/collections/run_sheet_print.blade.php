<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Collection Run Sheet - {{ $selectedOfficer->name }} ({{ $date }})</title>
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-size: 11px;
      color: #222;
    }
    .sheet-container {
      max-width: 960px;
      margin: 20px auto;
      background: #fff;
      padding: 30px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .table-sm th, .table-sm td {
      padding: 6px 8px;
    }
    @media print {
      body {
        background: #fff;
        margin: 0;
        padding: 0;
      }
      .sheet-container {
        border: none;
        box-shadow: none;
        max-width: 100%;
        margin: 0;
        padding: 10px;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<div class="no-print text-center py-3 bg-light border-bottom mb-3">
  <button type="button" class="btn btn-primary btn-sm me-2" onclick="window.print()">
    Print Run Sheet (A4)
  </button>
  <button type="button" class="btn btn-secondary btn-sm" onclick="window.close()">
    Close Window
  </button>
</div>

<div class="sheet-container">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
    <div>
      <h4 class="fw-bold text-uppercase mb-0 text-primary">{{ $company->name }}</h4>
      <div class="text-muted small">Showroom: {{ $currentBranch?->name ?? 'All Showrooms' }} ({{ $currentBranch?->code ?? 'HQ' }})</div>
      <div class="text-muted small">Daily Field Collection Itinerary & Recovery Run Sheet</div>
    </div>
    <div class="text-end">
      <div class="badge bg-dark fs-6 font-monospace mb-1">{{ Carbon\Carbon::parse($date)->format('d-M-Y') }}</div>
      <div class="small">Officer: <strong>{{ $selectedOfficer->name }}</strong></div>
      <div class="text-muted small">Allocated Accounts: <strong>{{ $runSheet->count() }}</strong></div>
    </div>
  </div>

  <!-- Run Sheet Table -->
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-sm align-middle">
      <thead class="table-light text-uppercase font-monospace small">
        <tr>
          <th style="width: 30px;">#</th>
          <th>Account & Customer</th>
          <th>Contact & Address</th>
          <th>Merchandise</th>
          <th class="text-end">Target Due</th>
          <th style="width: 120px;">Outcome / PTP</th>
          <th style="width: 100px;">Cash Collected</th>
          <th style="width: 90px;">Customer Sign</th>
        </tr>
      </thead>
      <tbody>
        @forelse($runSheet as $idx => $item)
          @php
            $agr = $item->agreement;
            $cust = $agr->customer;
            $overdueAmount = $agr->schedules->where('status', 'overdue')->sum('remaining_balance');
            $target = $overdueAmount > 0 ? $overdueAmount : $agr->installment_amount;
          @endphp
          <tr>
            <td class="text-center font-monospace">{{ $idx + 1 }}</td>
            <td>
              <strong class="text-dark">{{ $cust->full_name }}</strong>
              <div class="font-monospace text-muted small">{{ $agr->account_number }}</div>
              <div class="small text-muted">CNIC: {{ $cust->cnic }}</div>
            </td>
            <td>
              <div class="fw-bold font-monospace">{{ $cust->mobile_primary }}</div>
              <div class="text-muted small">{{ $cust->present_address }}</div>
            </td>
            <td>
              <div>{{ $agr->product->brand }} {{ $agr->product->model_name }}</div>
              <small class="text-muted font-monospace">Bal: Rs. {{ number_format($agr->remaining_balance) }}</small>
            </td>
            <td class="text-end fw-bold text-danger">
              Rs. {{ number_format($target) }}
            </td>
            <td>
              <div class="border-bottom pb-2 mb-1" style="height: 18px;"></div>
              <div class="text-muted small" style="font-size: 9px;">PTP Date: ____________</div>
            </td>
            <td>
              <div class="border-bottom pb-2 mb-1" style="height: 18px;"></div>
              <div class="text-muted small" style="font-size: 9px;">Rcpt #: ____________</div>
            </td>
            <td>
              <div style="height: 32px;"></div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center py-4 text-muted">No accounts assigned for this run sheet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Signatures & Verification Block -->
  <div class="row pt-4 mt-4 border-top">
    <div class="col-4 text-center">
      <div class="border-top border-dark mx-auto pt-2" style="width: 70%;">
        <strong>{{ $selectedOfficer->name }}</strong>
        <div class="text-muted small">Field Recovery Officer</div>
      </div>
    </div>
    <div class="col-4 text-center">
      <div class="border-top border-dark mx-auto pt-2" style="width: 70%;">
        <strong>Branch Cashier</strong>
        <div class="text-muted small">Cash Verification & Handover</div>
      </div>
    </div>
    <div class="col-4 text-center">
      <div class="border-top border-dark mx-auto pt-2" style="width: 70%;">
        <strong>Branch Manager</strong>
        <div class="text-muted small">Audit & Review Approval</div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
