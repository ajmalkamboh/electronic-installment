<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guarantor Undertaking Affidavit - {{ $guarantor?->full_name ?? 'Guarantor' }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background: #f8f9fa;
      font-family: 'Times New Roman', Times, serif, Arial;
      font-size: 13.5px;
      color: #111;
      line-height: 1.65;
    }
    .doc-page {
      background: #fff;
      max-width: 820px;
      margin: 25px auto;
      padding: 45px 50px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .affidavit-title {
      text-align: center;
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 2px solid #000;
      padding-bottom: 5px;
      margin-bottom: 20px;
    }
    .thumb-rect {
      border: 1px dashed #555;
      height: 90px;
      width: 90px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 10px;
      color: #666;
    }
    .sign-box {
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
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Guarantor Affidavit (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="border-bottom pb-2 mb-3 text-center">
      <h3 class="fw-bold text-uppercase mb-0">{{ $company->name }}</h3>
      <div class="small text-muted">{{ $company->legal_name }} &bull; {{ $branch->name }} ({{ $branch->city }})</div>
    </div>

    <div class="affidavit-title">Guarantor Undertaking &amp; Promissory Affidavit</div>
    <div class="text-center text-muted small mb-4">
      (Under Section 128 of the Contract Act, 1872 &amp; Oaths Act, 1873)
    </div>

    <!-- Deponent Bio -->
    <div class="p-3 border rounded bg-light mb-4">
      <p class="mb-0">
        I, <strong>{{ $guarantor?->full_name ?? '____________________________' }}</strong>,
        S/O or D/O or W/O <strong>{{ $guarantor?->father_or_husband_name ?? '____________________________' }}</strong>,<br>
        holding valid CNIC No. <strong>{{ $guarantor?->cnic ?? '_____-_______-_' }}</strong>,
        by occupation <strong>{{ $guarantor?->occupation ?? 'Business / Employment' }}</strong>,<br>
        residing permanently at <strong>{{ $guarantor?->address ?? '________________________________________________' }}</strong>,<br>
        Mobile No. <strong>{{ $guarantor?->mobile ?? '03__-_______' }}</strong>,
        do hereby solemnly affirm, declare, and state on oath as under:
      </p>
    </div>

    <!-- Affirmation Clauses -->
    <ol class="ps-3 mb-4" style="text-align: justify; line-height: 1.8;">
      <li class="mb-2">
        <strong>Relationship &amp; Knowledge:</strong> That I personally know the applicant, <strong>{{ $customer->full_name }}</strong>, holding CNIC No. <strong>{{ $customer->cnic }}</strong>, who has applied for installment financing of <strong>{{ $agreement->product?->name ?? 'Electronic Appliance' }}</strong> under Agreement No. <strong>#{{ $agreement->account_number }}</strong> amounting to a total installment price of <strong>PKR {{ number_format($agreement->total_payable, 2) }}</strong> payable in {{ $agreement->tenure_months }} monthly installments.
      </li>
      <li class="mb-2">
        <strong>Unconditional Guarantee:</strong> That at the special request of the applicant, I hereby stand as his/her unconditional guarantor (Zameen-dar). I undertake and guarantee the punctual, complete, and regular payment of all monthly installments as set forth in the agreement schedule.
      </li>
      <li class="mb-2">
        <strong>Joint &amp; Several Co-Extensive Liability:</strong> That in the event the said applicant commits default, delays payment, absconds, fails to pay any installment, or violates any term of the agreement, <strong>I hereby irrevocably undertake to pay the entire outstanding balance, markup, and accrued late penalties to the First Party immediately upon demand</strong>, without requiring the First Party to first take legal recourse or exhaust legal proceedings against the applicant.
      </li>
      <li class="mb-2">
        <strong>Authorization for Recovery:</strong> That I hereby authorize the management of <strong>{{ $company->name }}</strong> to present and encash any security cheque(s) / promissory note(s) deposited by me, and to initiate recovery proceedings against my moveable and immoveable assets before the competent civil or criminal courts of {{ $branch->city }}.
      </li>
      <li class="mb-2">
        <strong>Truth &amp; Veracity:</strong> That the contents of this affidavit are true, correct, and complete to the best of my personal knowledge, belief, and information, and nothing material has been concealed or misrepresented herein.
      </li>
    </ol>

    <!-- Verification Under Oath -->
    <div class="border p-3 rounded mb-4 bg-light">
      <div class="fw-bold mb-1">VERIFICATION:</div>
      <p class="mb-0 small text-muted">
        Verified on oath at <strong>{{ $branch->city }}</strong> on this <strong>{{ now()->format('d F, Y') }}</strong> that the statements made above are true and correct to the best of my knowledge and no part of it is false.
      </p>
    </div>

    <!-- Signatures & Fingerprints -->
    <div class="row pt-3 text-center align-items-end">
      <div class="col-4">
        <div class="thumb-rect mb-2">Right Thumb</div>
        <div class="sign-box">
          DEPONENT / GUARANTOR<br>
          <span class="small text-muted">{{ $guarantor?->full_name ?? 'Guarantor Signature' }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="thumb-rect mb-2">Left Thumb</div>
        <div class="sign-box">
          WITNESS / VERIFIER<br>
          <span class="small text-muted">CNIC: __________________</span>
        </div>
      </div>
      <div class="col-4">
        <div style="height: 90px;" class="d-flex align-items-center justify-content-center text-muted small border mb-2">
          Notary / Oath Commissioner Seal
        </div>
        <div class="sign-box">
          ATTESTATION OFFICER<br>
          <span class="small text-muted">Oath Commissioner / Notary</span>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
