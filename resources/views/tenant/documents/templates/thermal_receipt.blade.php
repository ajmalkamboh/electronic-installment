<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Receipt #{{ $payment->payment_number }}</title>
  <style>
    @page {
      margin: 0;
    }
    body {
      background: #f0f0f0;
      font-family: 'Courier New', Courier, monospace, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: {{ $format === '58mm' ? '10px' : ($format === '80mm' ? '12px' : '13px') }};
      line-height: 1.35;
      color: #000;
      margin: 0;
      padding: 20px 0;
    }
    .receipt-container {
      background: #fff;
      margin: 0 auto;
      width: {{ $format === '58mm' ? '54mm' : ($format === '80mm' ? '76mm' : '180mm') }};
      padding: {{ $format === 'a4' ? '15mm' : '4mm 3mm' }};
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      box-sizing: border-box;
    }
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }
    .text-uppercase { text-transform: uppercase; }
    .divider {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }
    .divider-double {
      border-top: 2px dashed #000;
      margin: 8px 0;
    }
    .row-flex {
      display: flex;
      justify-content: space-between;
      margin-bottom: 2px;
    }
    .qr-placeholder {
      display: inline-block;
      border: 1px solid #000;
      padding: 4px 8px;
      font-size: 9px;
      font-family: monospace;
      letter-spacing: 1px;
      margin-top: 6px;
    }
    .no-print-bar {
      background: #343a40;
      color: #fff;
      text-align: center;
      padding: 8px;
      margin-bottom: 15px;
    }
    .btn {
      display: inline-block;
      padding: 4px 10px;
      margin: 0 4px;
      border: 1px solid #fff;
      background: #0d6efd;
      color: #fff;
      text-decoration: none;
      border-radius: 4px;
      font-size: 11px;
      cursor: pointer;
    }
    .btn-secondary { background: #6c757d; }
    @media print {
      body { background: #fff !important; padding: 0 !important; }
      .receipt-container {
        box-shadow: none !important;
        margin: 0 !important;
        width: 100% !important;
        padding: {{ $format === 'a4' ? '10mm' : '2mm' }} !important;
      }
      .no-print-bar { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print-bar">
    <span>Receipt Format: <strong>{{ strtoupper($format) }}</strong></span> &bull;
    <a href="?format=58mm" class="btn {{ $format === '58mm' ? 'btn-secondary' : '' }}">58mm</a>
    <a href="?format=80mm" class="btn {{ $format === '80mm' ? 'btn-secondary' : '' }}">80mm</a>
    <a href="?format=a4" class="btn {{ $format === 'a4' ? 'btn-secondary' : '' }}">A4 Slip</a>
    <button onclick="window.print()" class="btn" style="background: #198754;">Print Receipt</button>
    <button onclick="window.close()" class="btn btn-secondary">Close</button>
  </div>

  <div class="receipt-container">
    <!-- Header -->
    <div class="text-center">
      <div class="fw-bold" style="font-size: {{ $format === '58mm' ? '13px' : '15px' }};">{{ $company->name }}</div>
      <div>{{ $branch->name }}</div>
      <div style="font-size: 0.85em;">{{ $branch->address }}, {{ $branch->city }}</div>
      <div style="font-size: 0.85em;">Tel: {{ $branch->phone }}</div>
      @if(!empty($company->ntn_strn))
      <div style="font-size: 0.85em;">NTN/STRN: {{ $company->ntn_strn }}</div>
      @endif
      <div class="fw-bold mt-1">INSTALLMENT PAYMENT RECEIPT</div>
    </div>

    <div class="divider"></div>

    <!-- Metadata -->
    <div class="row-flex">
      <span>Receipt #:</span>
      <span class="fw-bold">{{ $payment->payment_number }}</span>
    </div>
    <div class="row-flex">
      <span>Date / Time:</span>
      <span>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-M-Y H:i') : now()->format('d-M-Y H:i') }}</span>
    </div>
    <div class="row-flex">
      <span>Cashier:</span>
      <span>{{ $payment->cashier?->name ?? 'System Cashier' }}</span>
    </div>

    <div class="divider"></div>

    <!-- Customer & Agreement -->
    <div class="row-flex">
      <span>Customer:</span>
      <span class="fw-bold">{{ $customer->full_name }}</span>
    </div>
    <div class="row-flex">
      <span>CNIC:</span>
      <span>{{ $customer->cnic }}</span>
    </div>
    <div class="row-flex">
      <span>Mobile:</span>
      <span>{{ $customer->mobile_primary }}</span>
    </div>
    @if($agreement)
    <div class="row-flex">
      <span>Agreement #:</span>
      <span class="fw-bold">#{{ $agreement->account_number }}</span>
    </div>
    <div class="row-flex">
      <span>Item:</span>
      <span>{{ $agreement->product?->name ?? 'Merchandise' }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <!-- Payment Breakdown -->
    <div class="row-flex">
      <span>Payment Mode:</span>
      <span class="fw-bold text-uppercase">{{ $payment->payment_method ?? 'Cash' }}</span>
    </div>
    @if(!empty($payment->reference_number))
    <div class="row-flex">
      <span>Ref / Cheque #:</span>
      <span>{{ $payment->reference_number }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <div class="row-flex">
      <span>Principal Component:</span>
      <span>PKR {{ number_format($payment->principal_paid ?? 0, 2) }}</span>
    </div>
    <div class="row-flex">
      <span>Markup Component:</span>
      <span>PKR {{ number_format($payment->markup_paid ?? 0, 2) }}</span>
    </div>
    @if(($payment->late_fee_paid ?? 0) > 0)
    <div class="row-flex">
      <span>Late Fee Paid:</span>
      <span>PKR {{ number_format($payment->late_fee_paid, 2) }}</span>
    </div>
    @endif

    <div class="divider-double"></div>

    <div class="row-flex fw-bold" style="font-size: 1.15em;">
      <span>TOTAL RECEIVED:</span>
      <span>PKR {{ number_format($payment->amount, 2) }}</span>
    </div>

    <div class="divider-double"></div>

    @if($agreement)
    <!-- Remaining Balances & Next Due -->
    <div class="row-flex">
      <span>Remaining Balance:</span>
      <span class="fw-bold">PKR {{ number_format($agreement->remaining_balance, 2) }}</span>
    </div>
    @php
      $nextSchedule = $agreement->schedules()->where('status', '!=', 'paid')->orderBy('installment_number')->first();
    @endphp
    @if($nextSchedule)
    <div class="row-flex">
      <span>Next Due Date:</span>
      <span class="fw-bold">{{ $nextSchedule->due_date ? \Carbon\Carbon::parse($nextSchedule->due_date)->format('d-M-Y') : 'N/A' }}</span>
    </div>
    <div class="row-flex">
      <span>Next Due Amount:</span>
      <span>PKR {{ number_format($nextSchedule->total_amount, 2) }}</span>
    </div>
    @else
    <div class="text-center fw-bold mt-1" style="color: #198754;">
      *** ALL INSTALLMENTS FULLY PAID ***
    </div>
    @endif
    @endif

    <div class="divider"></div>

    <!-- Footer & Notes -->
    <div class="text-center" style="font-size: 0.9em;">
      <div class="fw-bold">شکریہ برائے بروقت ادائیگی</div>
      <div>Thank you for your prompt payment!</div>
      <div style="font-size: 0.8em; margin-top: 4px;">Computer-generated receipt &bull; No signature required.</div>
    </div>

    <div class="text-center mt-2">
      <div class="qr-placeholder">
        *{{ $payment->payment_number }}*
      </div>
    </div>
  </div>
</body>
</html>
