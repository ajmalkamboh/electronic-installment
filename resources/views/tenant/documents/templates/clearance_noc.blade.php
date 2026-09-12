<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clearance Certificate & NOC - {{ $agreement->account_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Times New Roman', Times, serif, Georgia, Arial;
      font-size: 13.5px;
      color: #111;
      line-height: 1.6;
    }
    .doc-page {
      background: #fff;
      max-width: 840px;
      margin: 25px auto;
      padding: 45px 55px;
      border: 6px double #b8860b;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      position: relative;
    }
    .gold-seal {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: radial-gradient(circle, #ffd700 0%, #daa520 60%, #b8860b 100%);
      color: #4a2c00;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: bold;
      text-transform: uppercase;
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
      border: 3px dashed #fff;
      margin: 0 auto;
      letter-spacing: 0.5px;
      text-align: center;
      line-height: 1.2;
    }
    .cert-title {
      font-size: 22px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #1a4314;
      text-align: center;
      margin-top: 10px;
      margin-bottom: 5px;
    }
    .cert-subtitle {
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      text-align: center;
      color: #555;
      margin-bottom: 25px;
    }
    .sign-box {
      border-top: 1px solid #333;
      margin-top: 45px;
      padding-top: 5px;
      font-weight: bold;
      text-align: center;
      font-size: 11.5px;
    }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .doc-page {
        box-shadow: none !important;
        margin: 0 !important;
        padding: 15mm !important;
        max-width: 100% !important;
        border: 4px double #b8860b !important;
      }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print text-center py-2 bg-light border-bottom">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Clearance Certificate (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="text-center pb-2 mb-2 border-bottom">
      <h2 class="fw-bold text-uppercase mb-0" style="color: #1a4314; letter-spacing: 1px;">{{ $company->name }}</h2>
      <div class="small text-muted">{{ $company->legal_name }} &bull; {{ $branch->name }}</div>
      <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Ph: {{ $branch->phone }}</div>
    </div>

    <div class="cert-title">CERTIFICATE OF FULL CONTRACT CLEARANCE</div>
    <div class="cert-subtitle">&amp; NO OBJECTION CERTIFICATE (N.O.C)</div>

    <div class="d-flex justify-content-between small text-muted mb-4 border-bottom pb-2">
      <div><strong>Certificate Ref:</strong> <span class="font-monospace fw-bold text-dark">NOC-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}-{{ now()->format('Y') }}</span></div>
      <div><strong>Account No:</strong> <span class="font-monospace fw-bold text-dark">#{{ $agreement->account_number }}</span></div>
      <div><strong>Date of Discharge:</strong> {{ \Carbon\Carbon::parse($clearedAt)->format('d F, Y') }}</div>
    </div>

    <div class="text-center mb-4">
      <span class="fs-5 fst-italic">TO WHOM IT MAY CONCERN</span>
    </div>

    <p style="text-align: justify; line-height: 1.8;">
      This is to certify and officially attest that <strong>Mr./Mrs./Ms. {{ $customer->full_name }}</strong>,
      S/O or D/O or W/O <strong>{{ $customer->father_or_husband_name ?? 'N/A' }}</strong>,
      holding valid CNIC No. <strong>{{ $customer->cnic }}</strong>, residing at {{ $customer->present_address }},
      had purchased the electronic equipment detailed below under Hire-Purchase Installment Financing Agreement
      No. <strong>#{{ $agreement->account_number }}</strong> dated <strong>{{ $agreement->created_at->format('d-M-Y') }}</strong>:
    </p>

    <!-- Merchandise Specification Table -->
    <table class="table table-bordered mb-4" style="font-size: 12.5px;">
      <thead class="table-light">
        <tr>
          <th>Merchandise Item</th>
          <th>Brand &amp; Model</th>
          <th>Serial / IMEI Number</th>
          <th>Total Financed Paid</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>{{ $product?->name ?? 'Electronic Merchandise' }}</strong></td>
          <td>{{ $product?->brand ?? 'Brand' }} &bull; {{ $product?->model_name ?? 'Model' }}</td>
          <td class="font-monospace fw-bold">{{ $serializedItem?->serial_number ?? $agreement->product_serial_number ?? 'Verified' }}</td>
          <td class="fw-bold text-success">PKR {{ number_format($agreement->total_payable, 2) }} (Cleared)</td>
        </tr>
      </tbody>
    </table>

    <div class="p-3 border rounded bg-light mb-4" style="text-align: justify; line-height: 1.8;">
      <p class="mb-2">
        <strong>1. SATISFACTION &amp; DISCHARGE OF DUES:</strong> We hereby confirm and declare that the customer has punctually and satisfactorily repaid <strong>all installments, principal sums, agreed markup, and late surcharges</strong>. There is <strong>ZERO (0.00) OUTSTANDING BALANCE</strong> or liability remaining against the said account on the books of our company.
      </p>
      <p class="mb-2">
        <strong>2. UNCONDITIONAL TRANSFER OF TITLE:</strong> The management of <strong>{{ $company->name }}</strong> hereby unconditionally relinquishes all hire-purchase liens, hypothecations, claims, security rights, and ownership reservations over the aforementioned merchandise. Absolute, free, and unencumbered ownership and title is hereby transferred to the customer.
      </p>
      <p class="mb-0">
        <strong>3. DISCHARGE OF GUARANTORS &amp; SECURITY INSTRUMENTS:</strong> The Guarantors are hereby completely discharged from all obligations under their undertaking affidavits. All deposited post-dated security cheques and promissory instruments have been rendered null and void.
      </p>
    </div>

    <!-- Seal and Signatures -->
    <div class="row pt-2 align-items-center text-center">
      <div class="col-4">
        <div class="sign-box">
          ACCOUNTS CONTROLLER<br>
          <span class="small text-muted">Ledger Reconciled</span>
        </div>
      </div>
      <div class="col-4">
        <div class="gold-seal">
          <div>OFFICIAL</div>
          <div style="font-size: 13px; font-weight: 900;">CLEARANCE</div>
          <div>SEAL</div>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          MANAGING DIRECTOR / BM<br>
          <span class="small text-muted">Authorized Signatory &amp; Stamp</span>
        </div>
      </div>
    </div>

    <div class="text-center text-muted small mt-4 pt-3 border-top">
      This is a certified digital document generated by Electronic Installment SaaS &bull; Verified under Company Seal &bull; {{ $branch->city }}, Pakistan
    </div>
  </div>
</body>
</html>
