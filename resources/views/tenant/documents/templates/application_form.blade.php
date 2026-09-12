<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Application Form - {{ $customer->full_name }}</title>
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
      padding: 40px 50px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .header-rule {
      border-bottom: 2px solid #000;
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    .section-title {
      background: #f1f3f5;
      padding: 6px 12px;
      font-weight: bold;
      text-transform: uppercase;
      font-size: 12px;
      border-left: 4px solid #0d6efd;
      margin-top: 15px;
      margin-bottom: 10px;
    }
    .thumb-box {
      border: 1px dashed #777;
      height: 90px;
      width: 90px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 10px;
      color: #777;
    }
    .sign-line {
      border-top: 1px solid #000;
      margin-top: 50px;
      padding-top: 5px;
      font-weight: bold;
      text-align: center;
      font-size: 12px;
    }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .doc-page { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 15mm !important; max-width: 100% !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print text-center py-2 bg-light border-bottom">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Application Form (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="header-border header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-primary">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; NTN/STRN: {{ $company->ntn_strn ?? 'N/A' }}</div>
          <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Tel: {{ $branch->phone }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-primary fs-6 p-2 text-uppercase mb-1">APPLICATION FORM</span>
          <div class="small fw-bold">App Ref: <span class="font-monospace">APP-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}</span></div>
          <div class="small text-muted">Date: {{ $agreement->created_at->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Section 1: Customer Personal Profile -->
    <div class="section-title">1. Applicant Biographical Profile</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Full Name:</td>
          <td style="width: 25%;">{{ $customer->full_name }}</td>
          <td class="bg-light fw-semibold" style="width: 25%;">Father / Husband:</td>
          <td style="width: 25%;">{{ $customer->father_or_husband_name ?? 'N/A' }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">CNIC Number:</td>
          <td class="font-monospace fw-bold">{{ $customer->cnic }}</td>
          <td class="bg-light fw-semibold">Gender:</td>
          <td>{{ ucfirst($customer->gender ?? 'Male') }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Primary Mobile:</td>
          <td class="font-monospace">{{ $customer->mobile_primary }}</td>
          <td class="bg-light fw-semibold">Secondary / WhatsApp:</td>
          <td class="font-monospace">{{ $customer->mobile_secondary ?? $customer->whatsapp_number ?? 'N/A' }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Email Address:</td>
          <td colspan="3">{{ $customer->email ?? 'N/A' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 2: Residential Tenure -->
    <div class="section-title">2. Residential Details & Utility Profile</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Present Residence:</td>
          <td colspan="3">{{ $customer->present_address }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Permanent Address:</td>
          <td colspan="3">{{ $customer->permanent_address ?? $customer->present_address }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Residence Status:</td>
          <td>{{ ucfirst($customer->residence_type ?? 'Owned') }}</td>
          <td class="bg-light fw-semibold">Tenure at Residence:</td>
          <td>{{ $customer->residence_tenure_years ?? 5 }} Years</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Electricity Bill Ref #:</td>
          <td colspan="3" class="font-monospace">{{ $customer->utility_bill_ref_number ?? 'VERIFIED AT SHOWROOM' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 3: Financing & Merchandise Request -->
    <div class="section-title">3. Merchandise Requested & Proposed Financing Terms</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Product Description:</td>
          <td style="width: 25%;" class="fw-bold">{{ $product->name }}</td>
          <td class="bg-light fw-semibold" style="width: 25%;">Cash Retail Price:</td>
          <td style="width: 25%;">PKR {{ number_format($agreement->cash_price, 2) }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Down Payment Paid:</td>
          <td class="text-success fw-bold">PKR {{ number_format($agreement->down_payment_paid, 2) }}</td>
          <td class="bg-light fw-semibold">Financed Principal:</td>
          <td>PKR {{ number_format($agreement->financed_principal, 2) }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Tenure & Plan:</td>
          <td>{{ $agreement->tenure_months }} Months ({{ $plan->name }})</td>
          <td class="bg-light fw-semibold">Monthly Installment:</td>
          <td class="text-primary fw-bold">PKR {{ number_format($agreement->installment_amount, 2) }} / mo</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Total Repayable:</td>
          <td colspan="3" class="fw-bold fs-6 text-danger">PKR {{ number_format($agreement->total_payable, 2) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 4: Guarantor Summary -->
    <div class="section-title">4. Guarantors on Record</div>
    <table class="table table-sm table-bordered mb-3">
      <thead class="table-light">
        <tr>
          <th>Guarantor Name</th>
          <th>CNIC Number</th>
          <th>Mobile Number</th>
          <th>Relationship</th>
        </tr>
      </thead>
      <tbody>
        @forelse($guarantors as $g)
          <tr>
            <td class="fw-semibold">{{ $g->full_name }}</td>
            <td class="font-monospace">{{ $g->cnic }}</td>
            <td>{{ $g->mobile }}</td>
            <td>{{ ucfirst($g->pivot->relationship ?? 'Associate') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center text-muted">Guarantor details submitted separately.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <!-- Applicant Undertaking & Signatures -->
    <div class="mt-4 p-3 bg-light rounded border small">
      <strong>APPLICANT SOLEMN DECLARATION:</strong><br>
      I hereby solemnly affirm and declare that all particulars, biographical records, residential addresses, and employment data supplied above are true, accurate, and complete. I understand that any false statement will result in immediate disqualification, forfeiture of down payment, and legal recovery proceedings under the laws of Pakistan.
    </div>

    <div class="row mt-4 pt-3 text-center align-items-end">
      <div class="col-4">
        <div class="thumb-box">LEFT THUMB<br>IMPRESSION</div>
        <small class="text-muted d-block mt-1">Applicant Thumb Print</small>
      </div>
      <div class="col-4">
        <div class="sign-line">{{ $customer->full_name }}</div>
        <small class="text-muted d-block">Signature of Applicant</small>
      </div>
      <div class="col-4">
        <div class="sign-line">Showroom Branch Manager</div>
        <small class="text-muted d-block">{{ $branch->name }}</small>
      </div>
    </div>
  </div>
</body>
</html>
