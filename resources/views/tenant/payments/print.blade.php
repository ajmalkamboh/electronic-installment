<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Receipt - {{ $payment->payment_number }}</title>
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-size: 13px;
      color: #222;
    }
    .receipt-container {
      max-width: 480px;
      margin: 20px auto;
      background: #fff;
      padding: 24px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .divider {
      border-top: 1px dashed #bbb;
      margin: 12px 0;
    }
    @media print {
      body {
        background: #fff;
        margin: 0;
        padding: 0;
      }
      .receipt-container {
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
    Print Receipt (POS / A4)
  </button>
  <button type="button" class="btn btn-secondary btn-sm" onclick="window.close()">
    Close Window
  </button>
</div>

<div class="receipt-container">
  <!-- Header -->
  <div class="text-center mb-3">
    <h4 class="fw-bold text-uppercase mb-0 text-primary">{{ $payment->company->name }}</h4>
    <div class="small text-muted">{{ $payment->branch->name }}</div>
    <div class="small text-muted">{{ $payment->branch->address ?? 'Main Electronic Market' }}</div>
    <div class="small text-muted">Phone: {{ $payment->branch->phone ?? 'N/A' }}</div>
    <div class="divider"></div>
    <h6 class="fw-bold text-uppercase mb-0">Installment Collection Receipt</h6>
    <div class="badge bg-dark font-monospace mt-1">{{ $payment->payment_number }}</div>
  </div>

  <!-- Meta Info -->
  <div class="small mb-2">
    <div class="d-flex justify-content-between">
      <span class="text-muted">Date & Time:</span>
      <strong>{{ $payment->created_at->format('d/m/Y h:i A') }}</strong>
    </div>
    <div class="d-flex justify-content-between">
      <span class="text-muted">Cashier Officer:</span>
      <span>{{ $payment->cashier->name }}</span>
    </div>
    <div class="d-flex justify-content-between">
      <span class="text-muted">Customer Name:</span>
      <strong>{{ $payment->customer->full_name }}</strong>
    </div>
    <div class="d-flex justify-content-between">
      <span class="text-muted">Customer CNIC:</span>
      <span class="font-monospace">{{ $payment->customer->cnic }}</span>
    </div>
    <div class="d-flex justify-content-between">
      <span class="text-muted">Agreement Ref:</span>
      <strong class="font-monospace">{{ $payment->agreement->account_number }}</strong>
    </div>
    <div class="d-flex justify-content-between">
      <span class="text-muted">Merchandise:</span>
      <span>{{ $payment->agreement->product->brand }} {{ $payment->agreement->product->model_name }}</span>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Allocations Breakdown -->
  <div class="mb-3">
    <div class="fw-bold small text-uppercase mb-1">Schedule Allocation Breakdown</div>
    <table class="table table-sm table-borderless small mb-0">
      <thead>
        <tr class="border-bottom text-muted">
          <th>Inst #</th>
          <th>Due Date</th>
          <th class="text-end">Allocated</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payment->allocations as $alloc)
          <tr>
            <td class="font-monospace fw-bold">#{{ $alloc->schedule->installment_number }}</td>
            <td>{{ $alloc->schedule->due_date->format('d/m/Y') }}</td>
            <td class="text-end fw-semibold">Rs. {{ number_format($alloc->amount_allocated) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="divider"></div>

  <!-- Totals -->
  <div class="small mb-3">
    <div class="d-flex justify-content-between fs-6 fw-bold text-dark">
      <span>Total Tendered:</span>
      <span class="text-success">Rs. {{ number_format($payment->amount) }}</span>
    </div>
    <div class="d-flex justify-content-between text-muted">
      <span>Payment Channel:</span>
      <span class="text-uppercase">{{ $payment->payment_method }}</span>
    </div>
    @if($payment->reference_number)
      <div class="d-flex justify-content-between text-muted">
        <span>Transaction Ref:</span>
        <span class="font-monospace">{{ $payment->reference_number }}</span>
      </div>
    @endif
    <div class="d-flex justify-content-between fw-bold text-danger mt-1">
      <span>Remaining Contract Balance:</span>
      <span>Rs. {{ number_format($payment->agreement->remaining_balance) }}</span>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Footer -->
  <div class="text-center small text-muted">
    <div>Thank you for your timely payment!</div>
    <div class="fst-italic mt-1">This is a system generated official receipt.</div>
  </div>
</div>

</body>
</html>
