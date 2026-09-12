<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Gate Pass &bull; {{ $transfer->gate_pass_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #212529;
      font-size: 13px;
    }
    .gate-pass-container {
      max-width: 800px;
      margin: 20px auto;
      background: #ffffff;
      padding: 30px;
      border: 1px solid #dee2e6;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }
    .header-border {
      border-bottom: 2px solid #000;
      padding-bottom: 12px;
      margin-bottom: 15px;
    }
    .meta-box {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      padding: 10px 15px;
      border-radius: 4px;
    }
    .table-manifest th {
      background-color: #f1f3f5 !important;
      border-color: #ced4da;
      font-size: 12px;
    }
    .table-manifest td {
      border-color: #dee2e6;
      vertical-align: middle;
    }
    .signature-box {
      border-top: 1px dashed #495057;
      margin-top: 50px;
      padding-top: 8px;
      text-align: center;
      font-size: 11px;
    }
    .security-notice {
      border: 1px solid #ffc107;
      background-color: #fffdf5;
      padding: 10px;
      border-radius: 4px;
      font-size: 11px;
    }
    @media print {
      body {
        background: #fff;
        margin: 0;
        padding: 0;
      }
      .gate-pass-container {
        border: none;
        box-shadow: none;
        padding: 0;
        max-width: 100%;
        margin: 0;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Actions -->
  <div class="container text-center my-3 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
      <i class="bi bi-printer"></i> Print Gate Pass (Ctrl+P)
    </button>
    <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-outline-secondary btn-sm">
      Return to Transfer Order
    </a>
  </div>

  <div class="gate-pass-container">
    <!-- Showroom Header -->
    <div class="header-border">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-1 tracking-wide">{{ $transfer->company->name }}</h3>
          <div class="text-muted small">Retail Electronics Installment Network &bull; Pakistan</div>
          <div class="fw-bold mt-1 text-primary text-uppercase" style="letter-spacing: 1px;">
            OUTWARD SECURITY GATE PASS / INTER-BRANCH DELIVERY CHALLAN
          </div>
        </div>
        <div class="col-4 text-end">
          <div class="fs-5 fw-bold font-monospace text-dark">{{ $transfer->gate_pass_number }}</div>
          <div class="text-muted small">Transfer Ref: {{ $transfer->transfer_number }}</div>
          <div class="text-muted small">Date: {{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d-M-Y h:i A') : date('d-M-Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Routing Info -->
    <div class="row g-2 mb-3">
      <div class="col-6">
        <div class="meta-box h-100">
          <div class="text-muted small fw-bold text-uppercase mb-1">Dispatched From (Origin Showroom)</div>
          <div class="fw-bold text-dark">{{ $transfer->sourceBranch?->name }} ({{ $transfer->sourceBranch?->code }})</div>
          <div class="small text-muted">{{ $transfer->sourceBranch?->address }}, {{ $transfer->sourceBranch?->city }}</div>
          <div class="small text-muted">Phone: {{ $transfer->sourceBranch?->phone ?? 'N/A' }}</div>
          <div class="small text-muted mt-1">Dispatched By: <strong>{{ $transfer->dispatcher?->name }}</strong></div>
        </div>
      </div>
      <div class="col-6">
        <div class="meta-box h-100">
          <div class="text-muted small fw-bold text-uppercase mb-1">Delivering To (Destination Showroom)</div>
          <div class="fw-bold text-dark">{{ $transfer->destinationBranch?->name }} ({{ $transfer->destinationBranch?->code }})</div>
          <div class="small text-muted">{{ $transfer->destinationBranch?->address }}, {{ $transfer->destinationBranch?->city }}</div>
          <div class="small text-muted">Phone: {{ $transfer->destinationBranch?->phone ?? 'N/A' }}</div>
          <div class="small text-muted mt-1">Expected Intake: Store In-charge</div>
        </div>
      </div>
    </div>

    <!-- Driver & Vehicle Credentials -->
    <div class="meta-box mb-3">
      <div class="row g-2">
        <div class="col-3">
          <div class="text-muted small">Driver Full Name:</div>
          <div class="fw-bold">{{ $transfer->driver_name ?? 'N/A' }}</div>
        </div>
        <div class="col-3">
          <div class="text-muted small">Driver CNIC:</div>
          <div class="fw-bold font-monospace">{{ $transfer->driver_cnic ?? 'N/A' }}</div>
        </div>
        <div class="col-3">
          <div class="text-muted small">Driver Phone:</div>
          <div class="fw-bold">{{ $transfer->driver_phone ?? 'N/A' }}</div>
        </div>
        <div class="col-3">
          <div class="text-muted small">Vehicle Registration #:</div>
          <div class="fw-bold font-monospace text-uppercase text-primary">{{ $transfer->vehicle_number ?? 'N/A' }}</div>
        </div>
      </div>
      @if($transfer->transport_company)
        <div class="mt-2 pt-2 border-top small text-muted">
          Transport Service / Carrier: <strong>{{ $transfer->transport_company }}</strong>
        </div>
      @endif
    </div>

    <!-- Hardware Item Manifest -->
    <table class="table table-bordered table-sm table-manifest mb-3">
      <thead>
        <tr>
          <th style="width: 5%;" class="text-center">#</th>
          <th>Appliance Description</th>
          <th>Brand &amp; Model</th>
          <th>Serial / IMEI Identifier</th>
          <th style="width: 10%;" class="text-center">Qty</th>
          <th style="width: 15%;">Package Condition</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transfer->items as $idx => $item)
          <tr>
            <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
            <td>
              <div class="fw-bold">{{ $item->product?->category?->name ?? 'Home Appliance' }}</div>
              <small class="text-muted">SKU: {{ $item->product?->sku ?? 'N/A' }}</small>
            </td>
            <td>
              {{ $item->product?->brand }} {{ $item->product?->model_name }}
            </td>
            <td>
              @if($item->serializedItem)
                <span class="font-monospace fw-bold">{{ $item->serializedItem->identifier_label }}</span>
                @if($item->serializedItem->color)
                  <small class="text-muted d-block">Color: {{ $item->serializedItem->color }}</small>
                @endif
              @else
                <span class="text-muted">Bulk Goods</span>
              @endif
            </td>
            <td class="text-center fw-bold">{{ $item->quantity }}</td>
            <td class="small text-muted">Sealed / Intact</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr class="table-light">
          <td colspan="4" class="text-end fw-bold">Total Physical Units:</td>
          <td class="text-center fw-bold">{{ $transfer->total_items_count }}</td>
          <td></td>
        </tr>
      </tfoot>
    </table>

    <!-- Gate Security Instructions -->
    <div class="security-notice mb-4">
      <div class="fw-bold text-uppercase mb-1"><i class="bi bi-shield-lock me-1"></i>Security Guard Outward Check Notice:</div>
      <ul class="mb-0 ps-3">
        <li>Verify Driver Original CNIC and Vehicle Registration Plate against this Gate Pass before authorizing gate exit.</li>
        <li>Ensure physical carton count exactly equals <strong>{{ $transfer->total_items_count }} units</strong>.</li>
        <li>This Security Gate Pass is strictly valid only on the date of issuance for the authorized transit route.</li>
      </ul>
    </div>

    @if($transfer->gate_pass_notes)
      <div class="p-2 mb-4 bg-light border small">
        <strong>Gate Security Notes:</strong> {{ $transfer->gate_pass_notes }}
      </div>
    @endif

    <!-- 3-Part Signature Blocks -->
    <div class="row g-4 mt-2">
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold text-dark">{{ $transfer->dispatcher?->name ?? 'Store In-charge' }}</div>
          <div class="text-muted">Dispatched By (Store In-charge)</div>
        </div>
      </div>
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold text-dark">&nbsp;</div>
          <div class="text-muted">Security Guard (Showroom Exit Check)</div>
        </div>
      </div>
      <div class="col-4">
        <div class="signature-box">
          <div class="fw-bold text-dark">{{ $transfer->driver_name ?? 'Transport Driver' }}</div>
          <div class="text-muted">Received for Transit (Driver)</div>
        </div>
      </div>
    </div>

    <!-- Footer Verification -->
    <div class="text-center text-muted small mt-4 pt-3 border-top" style="font-size: 10px;">
      System Generated Security Gate Pass &bull; {{ $transfer->company->name }} SaaS &bull; Document ID: {{ $transfer->ulid }} &bull; Print Date: {{ date('d-M-Y H:i:s') }}
    </div>
  </div>

</body>
</html>
