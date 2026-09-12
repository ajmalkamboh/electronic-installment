<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installment Financing Agreement - {{ $agreement->account_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Times New Roman', Times, serif, Arial;
      font-size: 13px;
      color: #111;
      line-height: 1.6;
    }
    .doc-page {
      background: #fff;
      max-width: 850px;
      margin: 25px auto;
      padding: {{ !empty($stampPaperMargin) ? '90mm 45px 40px 45px' : '40px 45px' }};
      border: 1px solid #ddd;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .contract-title {
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 2px double #000;
      padding-bottom: 6px;
      margin-bottom: 20px;
    }
    .clause-header {
      font-weight: bold;
      font-size: 13px;
      text-decoration: underline;
      margin-top: 14px;
      margin-bottom: 5px;
    }
    .clause-text {
      text-align: justify;
      margin-bottom: 8px;
    }
    .sign-box {
      border-top: 1px solid #000;
      margin-top: 45px;
      padding-top: 4px;
      font-weight: bold;
      text-align: center;
      font-size: 11px;
    }
    .thumb-rect {
      border: 1px dashed #666;
      height: 75px;
      width: 80px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 9px;
      color: #777;
    }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .doc-page {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: {{ !empty($stampPaperMargin) ? '90mm 15mm 15mm 15mm' : '15mm' }} !important;
        max-width: 100% !important;
      }
      .page-break { page-break-before: always; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print text-center py-2 bg-light border-bottom sticky-top shadow-sm">
    <span class="me-3 fw-bold">Stamp Paper Margin: {{ !empty($stampPaperMargin) ? 'ENABLED (85-90mm Top Offset)' : 'Standard A4' }}</span>
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Legal Agreement</button>
    <a href="?stamp_paper_margin={{ !empty($stampPaperMargin) ? '0' : '1' }}" class="btn btn-outline-dark btn-sm me-2">
      Toggle {{ !empty($stampPaperMargin) ? 'Normal A4 Margins' : 'e-Stamp Paper Margins (85mm)' }}
    </a>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    @if(empty($stampPaperMargin))
    <!-- Company Header (Only shown when not printing directly on Pre-Printed e-Stamp Paper) -->
    <div class="border-bottom pb-2 mb-3 text-center">
      <h3 class="fw-bold text-uppercase mb-0">{{ $company->name }}</h3>
      <div class="small text-muted">{{ $company->legal_name }} &bull; NTN/STRN: {{ $company->ntn_strn ?? 'N/A' }}</div>
      <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Ph: {{ $branch->phone }}</div>
    </div>
    @endif

    <div class="contract-title">Hire-Purchase / Electronic Installment Financing Agreement</div>

    <div class="d-flex justify-content-between small text-muted mb-3 border-bottom pb-2">
      <div><strong>Agreement No:</strong> <span class="font-monospace fw-bold text-dark">#{{ $agreement->account_number }}</span></div>
      <div><strong>Date of Execution:</strong> {{ $agreement->created_at->format('d F, Y') }}</div>
      <div><strong>Jurisdiction:</strong> {{ $branch->city }}, Pakistan</div>
    </div>

    <div class="clause-text">
      This Installment Financing Agreement (hereinafter referred to as the <strong>"Agreement"</strong>) is entered into on this <strong>{{ $agreement->created_at->format('jS \d\a\y \o\f F, Y') }}</strong>, by and between:
    </div>

    <div class="clause-text ps-3">
      <strong>1. THE FINANCIER / SELLER:</strong> <strong>{{ $company->name }}</strong> (operating through its branch at <em>{{ $branch->name }}</em>, situated at {{ $branch->address }}, {{ $branch->city }}), acting through its authorized representative, hereinafter referred to as the <strong>"FIRST PARTY"</strong> (which expression shall include its successors, assigns, and legal representatives).
    </div>

    <div class="clause-text ps-3">
      <strong>2. THE PURCHASER / HIRER:</strong> <strong>{{ $customer->full_name }}</strong>, S/O or D/O or W/O {{ $customer->father_or_husband_name ?? 'N/A' }}, holding CNIC No. <strong>{{ $customer->cnic }}</strong>, residing at {{ $customer->present_address }}, Mobile No. {{ $customer->mobile_primary }}, hereinafter referred to as the <strong>"SECOND PARTY"</strong> (which expression shall include his/her heirs, legal representatives, and executors).
    </div>

    <div class="clause-text ps-3">
      <strong>3. THE GUARANTORS (ZAMEEN-DAR):</strong> The persons detailed in the Schedule of Guarantors below, who jointly and severally undertake unconditional liability for the fulfillment of the terms of this Agreement, hereinafter referred to as the <strong>"GUARANTORS / THIRD PARTY"</strong>.
    </div>

    <!-- Article 1: Description of Financed Electronic Equipment -->
    <div class="clause-header">ARTICLE 1: MERCHANDISE &amp; FINANCED GOODS</div>
    <div class="clause-text">
      The First Party agrees to deliver to the Second Party, on hire-purchase installment terms, the following brand new electronic merchandise:
    </div>
    <table class="table table-sm table-bordered mb-3" style="font-size: 12px;">
      <thead class="table-light">
        <tr>
          <th>Item Description / Brand</th>
          <th>Model / Variant</th>
          <th>Serial / IMEI Number</th>
          <th>Cash Retail Price</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><strong>{{ $product?->name ?? 'Electronic Appliance' }}</strong> ({{ $product?->brand ?? 'Brand' }})</td>
          <td>{{ $product?->model_name ?? 'Standard' }}</td>
          <td class="font-monospace fw-bold">{{ $serializedItem?->serial_number ?? $agreement->product_serial_number ?? 'Allocated upon delivery' }}</td>
          <td>PKR {{ number_format($agreement->cash_price ?? ($agreement->total_financed + $agreement->advance_payment), 2) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Article 2: Financial Terms & Installment Structure -->
    <div class="clause-header">ARTICLE 2: FINANCING TERMS &amp; PAYMENT SCHEDULE</div>
    <table class="table table-sm table-bordered mb-3" style="font-size: 12px;">
      <tbody>
        <tr>
          <td class="bg-light fw-bold" style="width: 25%;">Cash Price:</td>
          <td style="width: 25%;">PKR {{ number_format($agreement->cash_price ?? ($agreement->total_financed + $agreement->advance_payment), 2) }}</td>
          <td class="bg-light fw-bold" style="width: 25%;">Down Payment (Advance):</td>
          <td style="width: 25%;">PKR {{ number_format($agreement->advance_payment, 2) }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-bold">Principal Financed:</td>
          <td>PKR {{ number_format($agreement->total_financed, 2) }}</td>
          <td class="bg-light fw-bold">Markup Rate / Total Profit:</td>
          <td>{{ $agreement->markup_rate_pct }}% (PKR {{ number_format($agreement->total_markup, 2) }})</td>
        </tr>
        <tr>
          <td class="bg-light fw-bold">Total Installment Price:</td>
          <td>PKR {{ number_format($agreement->total_payable, 2) }}</td>
          <td class="bg-light fw-bold">Tenure &amp; Installments:</td>
          <td>{{ $agreement->tenure_months }} Months ({{ $agreement->total_installments }} Installments)</td>
        </tr>
        <tr>
          <td class="bg-light fw-bold">Monthly Installment (EMI):</td>
          <td colspan="3" class="fw-bold fs-6 text-primary">
            PKR {{ number_format($agreement->monthly_installment, 2) }} / month (Due on 1st-5th of each calendar month)
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Article 3: Covenants & Default Conditions -->
    <div class="clause-header">ARTICLE 3: COVENANTS, DEFAULT &amp; REPOSSESSION</div>
    <div class="clause-text">
      <strong>3.1 Ownership Retention:</strong> Absolute legal ownership and title of the financed merchandise shall remain exclusively with the First Party until the entire contractual amount, including all installments and accrued late charges, has been paid in full and a formal Certificate of Clearance (NOC) has been issued.
    </div>
    <div class="clause-text">
      <strong>3.2 Restriction on Alienation:</strong> The Second Party covenants that he/she shall NOT sell, pledge, mortgage, pawn, rent, transfer possession of, or take out of the city the said merchandise without prior written authorization of the First Party. Any unauthorized transfer shall constitute criminal breach of trust under Section 406/420 of Pakistan Penal Code (PPC).
    </div>
    <div class="clause-text">
      <strong>3.3 Default &amp; Right to Repossess:</strong> In the event that the Second Party fails to pay any monthly installment by its due date, or falls into arrears for 30 consecutive days, the First Party shall have the absolute right to:
      <ol type="a" class="mb-1 ps-3">
        <li>Terminate this Agreement and demand immediate settlement of the entire outstanding balance.</li>
        <li>Enter upon any premises where the merchandise is situated and peaceably repossess, seize, and take custody of the equipment without prior notice or court order.</li>
        <li>Enforce and encash all security post-dated cheques and legal promissory notes provided by the Second Party and Guarantors.</li>
      </ol>
    </div>
    <div class="clause-text">
      <strong>3.4 Late Payment Surcharge:</strong> Late payments beyond the contractual grace period shall attract late fee surcharges as prescribed by the showroom credit policy (PKR 50-100 per day or fixed per installment).
    </div>

    <!-- Article 4: Joint & Several Guarantor Undertaking -->
    <div class="clause-header">ARTICLE 4: GUARANTORS UNDERTAKING</div>
    <div class="clause-text">
      The Guarantors hereby unconditionally and irrevocably guarantee the punctual payment of all installments. The Guarantors agree that their liability is joint, several, and co-extensive with that of the Second Party under Section 128 of the Contract Act, 1872. The First Party may proceed against the Guarantors directly without exhausting remedies against the Second Party.
    </div>

    <!-- Guarantor Details Table -->
    @if($guarantors->isNotEmpty())
    <table class="table table-sm table-bordered mb-3" style="font-size: 11px;">
      <thead class="table-light">
        <tr>
          <th>Guarantor Name</th>
          <th>CNIC Number</th>
          <th>Relation</th>
          <th>Mobile</th>
          <th>Residential Address</th>
        </tr>
      </thead>
      <tbody>
        @foreach($guarantors as $g)
        <tr>
          <td><strong>{{ $g->full_name }}</strong></td>
          <td>{{ $g->cnic }}</td>
          <td>{{ $g->pivot->relationship ?? 'Guarantor' }}</td>
          <td>{{ $g->mobile }}</td>
          <td>{{ $g->address }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endif

    <!-- Article 5: Dispute Resolution & Jurisdiction -->
    <div class="clause-header">ARTICLE 5: GOVERNING LAW &amp; JURISDICTION</div>
    <div class="clause-text">
      This Agreement shall be governed by and construed in accordance with the laws of the Islamic Republic of Pakistan. Any dispute arising under or in connection with this Agreement shall be subject to the exclusive jurisdiction of the competent civil and criminal courts situated at <strong>{{ $branch->city }}</strong>.
    </div>

    <!-- Execution Signatures -->
    <div class="clause-header mt-4">EXECUTION &amp; SOLEMN ATTESTATION</div>
    <div class="clause-text mb-4">
      IN WITNESS WHEREOF, the parties hereto have signed and executed this Agreement in the presence of witnesses on the date and year first above written.
    </div>

    <div class="row pt-2 text-center">
      <div class="col-3">
        <div class="thumb-rect mb-2">Right Thumb</div>
        <div class="sign-box">
          SECOND PARTY / HIRER<br>
          <span class="small text-muted">{{ $customer->full_name }}</span>
        </div>
      </div>
      @if($guarantors->isNotEmpty())
        @foreach($guarantors->take(2) as $idx => $g)
        <div class="col-3">
          <div class="thumb-rect mb-2">Thumb</div>
          <div class="sign-box">
            GUARANTOR {{ $idx + 1 }}<br>
            <span class="small text-muted">{{ $g->full_name }}</span>
          </div>
        </div>
        @endforeach
      @else
        <div class="col-3">
          <div class="thumb-rect mb-2">Thumb</div>
          <div class="sign-box">
            GUARANTOR 1<br>
            <span class="small text-muted">Signature &amp; Thumb</span>
          </div>
        </div>
        <div class="col-3">
          <div class="thumb-rect mb-2">Thumb</div>
          <div class="sign-box">
            GUARANTOR 2<br>
            <span class="small text-muted">Signature &amp; Thumb</span>
          </div>
        </div>
      @endif
      <div class="col-3">
        <div style="height: 75px;" class="d-flex align-items-center justify-content-center text-muted small border mb-2">Showroom Stamp</div>
        <div class="sign-box">
          FIRST PARTY / SELLER<br>
          <span class="small text-muted">Branch Manager &amp; Stamp</span>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
