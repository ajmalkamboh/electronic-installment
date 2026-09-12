<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Merchandise Delivery & Handover Note - {{ $agreement->account_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: Arial, Helvetica, sans-serif;
      font-size: 13px;
      color: #222;
      line-height: 1.5;
    }
    .doc-page {
      background: #fff;
      max-width: 820px;
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
      padding: 5px 12px;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 12px;
      border-left: 4px solid #0d6efd;
      margin-top: 15px;
      margin-bottom: 8px;
    }
    .gate-pass-stub {
      border-top: 2px dashed #666;
      margin-top: 30px;
      padding-top: 20px;
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
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Delivery Note & Gate Pass (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-primary">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; {{ $branch->name }}</div>
          <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Tel: {{ $branch->phone }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-primary fs-6 p-2 text-uppercase mb-1">DELIVERY &amp; HANDOVER NOTE</span>
          <div class="small fw-bold">Note Ref: <span class="font-monospace">DN-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}</span></div>
          <div class="small text-muted">Date: {{ $agreement->disbursed_at?->format('d M, Y') ?? now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Applicant / Consignee Info -->
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 20%;">Customer Name:</td>
          <td style="width: 30%;"><strong>{{ $customer->full_name }}</strong></td>
          <td class="bg-light fw-semibold" style="width: 20%;">CNIC Number:</td>
          <td style="width: 30%;">{{ $customer->cnic }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Agreement No:</td>
          <td><strong>#{{ $agreement->account_number }}</strong></td>
          <td class="bg-light fw-semibold">Contact Mobile:</td>
          <td>{{ $customer->mobile_primary }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Delivery Address:</td>
          <td colspan="3">{{ $customer->present_address }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 1: Item & Serial Inventory Specifications -->
    <div class="section-title">1. Disbursed Merchandise &amp; Serial Identification</div>
    <table class="table table-sm table-bordered mb-3">
      <thead class="table-light">
        <tr>
          <th>Item / Category</th>
          <th>Brand &amp; Model</th>
          <th>Serial / IMEI Number</th>
          <th>Color / Condition</th>
          <th>Warranty Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>{{ $product?->name ?? 'Electronic Appliance' }}</strong></td>
          <td>{{ $product?->brand ?? 'Brand' }} &bull; {{ $product?->model_name ?? 'Model' }}</td>
          <td class="font-monospace fw-bold text-danger">
            {{ $serializedItem?->serial_number ?? $agreement->product_serial_number ?? 'Allocated at showroom' }}
          </td>
          <td>{{ $serializedItem?->color ?? 'Standard' }} &bull; Brand New (A+)</td>
          <td>12 Months Official Manufacturer</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 2: Physical Inspection & Condition Checklist -->
    <div class="section-title">2. Pre-Delivery Physical Inspection Checklist</div>
    <table class="table table-sm table-bordered mb-3">
      <thead>
        <tr class="table-light">
          <th style="width: 35%;">Inspection Point</th>
          <th style="width: 30%;">Technician Verification</th>
          <th style="width: 35%;">Customer Initial</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Original Factory Box / Packaging</td>
          <td><span class="badge bg-success">&check; Intact &amp; Sealed / Inspected</span></td>
          <td>Customer Verified &bull; [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
        </tr>
        <tr>
          <td>Physical Outer Body &amp; Screen Condition</td>
          <td><span class="badge bg-success">&check; Zero Scratches, Dents or Defects</span></td>
          <td>Customer Verified &bull; [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
        </tr>
        <tr>
          <td>Power-on Test &amp; Operating Demonstration</td>
          <td><span class="badge bg-success">&check; Demonstrated in showroom</span></td>
          <td>Customer Verified &bull; [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
        </tr>
        <tr>
          <td>Standard Accessories Included (Cable, Remote, etc.)</td>
          <td><span class="badge bg-success">&check; Complete OEM Accessories</span></td>
          <td>Customer Verified &bull; [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
        </tr>
        <tr>
          <td>Official Warranty Card &amp; User Manual</td>
          <td><span class="badge bg-success">&check; Stamped Warranty Card Handed</span></td>
          <td>Customer Verified &bull; [ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 3: Customer Receipt & Handover Declaration -->
    <div class="section-title">3. Customer Receipt &amp; Acknowledgment Declaration</div>
    <div class="p-3 border rounded bg-light mb-3">
      <p class="small mb-0" style="text-align: justify;">
        I, <strong>{{ $customer->full_name }}</strong>, hereby confirm that I have physically received the electronic merchandise described above in 100% brand new, pristine condition with all original accessories, cables, and stamped manufacturer warranty documents. I acknowledge that the title remains with <strong>{{ $company->name }}</strong> until completion of all monthly installment payments. In case of any technical defect, I will avail warranty services through authorized brand service centers as per standard manufacturer policies.
      </p>
    </div>

    <!-- Handover Signatures -->
    <div class="row pt-2 text-center">
      <div class="col-4">
        <div class="sign-box">
          SHOWROOM STORE INCHARGE<br>
          <span class="small text-muted">{{ $disbursedBy?->name ?? 'Inventory Dispatcher' }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          BRANCH MANAGER<br>
          <span class="small text-muted">Signature &amp; Showroom Stamp</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          CUSTOMER / RECIPIENT<br>
          <span class="small text-muted">Signature &amp; Thumb Impression</span>
        </div>
      </div>
    </div>

    <!-- Section 4: Gate Pass Stub -->
    <div class="gate-pass-stub">
      <div class="row align-items-center">
        <div class="col-8">
          <div class="fw-bold text-uppercase fs-6 text-danger">GATE PASS / SECURITY CLEARANCE STUB</div>
          <div class="small text-muted">Valid for exit on: <strong>{{ now()->format('d M, Y') }}</strong> &bull; Gate Pass Ref: <strong>#GP-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
          <div class="small">
            Item: <strong>{{ $product?->name ?? 'Merchandise' }}</strong> &bull; S/N: <span class="font-monospace fw-bold">{{ $serializedItem?->serial_number ?? $agreement->product_serial_number ?? 'N/A' }}</span><br>
            Bearer / Consignee: {{ $customer->full_name }} (CNIC: {{ $customer->cnic }})
          </div>
        </div>
        <div class="col-4 text-center">
          <div style="height: 50px;" class="d-flex align-items-center justify-content-center border border-dark small fw-bold">
            SECURITY CHECK &amp; STAMP
          </div>
          <div class="small text-muted mt-1">Exit Authorized By Security</div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
