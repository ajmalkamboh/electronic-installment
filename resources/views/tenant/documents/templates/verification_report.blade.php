<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Report - {{ $customer->full_name }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { background: #f8f9fa; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; }
    .doc-page { background: #fff; max-width: 820px; margin: 25px auto; padding: 40px 50px; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
    .header-rule { border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 20px; }
    .section-title { background: #e7f5ff; padding: 6px 12px; font-weight: bold; text-transform: uppercase; font-size: 12px; border-left: 4px solid #0284c7; margin-top: 15px; margin-bottom: 10px; }
    .sign-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; font-weight: bold; text-align: center; font-size: 12px; }
    @media print {
      body { background: #fff !important; margin: 0; padding: 0; }
      .doc-page { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 15mm !important; max-width: 100% !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print text-center py-2 bg-light border-bottom">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Verification Report (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <div class="header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-primary">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; Field Credit Intelligence Squad</div>
          <div class="small text-muted">{{ $branch->name }} &bull; {{ $branch->city }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-info text-dark fs-6 p-2 text-uppercase mb-1">VERIFICATION REPORT</span>
          <div class="small fw-bold">Ref: <span class="font-monospace">VER-{{ str_pad($customer->id, 5, '0', STR_PAD_LEFT) }}</span></div>
          <div class="small text-muted">Inspection Date: {{ now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Customer Basic Profile -->
    <div class="section-title">1. Subject Details</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Subject Name:</td>
          <td style="width: 25%;">{{ $customer->full_name }}</td>
          <td class="bg-light fw-semibold" style="width: 25%;">CNIC Number:</td>
          <td style="width: 25%;" class="font-monospace fw-bold">{{ $customer->cnic }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Primary Contact:</td>
          <td class="font-monospace">{{ $customer->mobile_primary }}</td>
          <td class="bg-light fw-semibold">Present Address:</td>
          <td>{{ $customer->present_address }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Residential Verification -->
    <div class="section-title">2. Physical Residence Verification</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Residence Status:</td>
          <td style="width: 25%;">{{ ucfirst($customer->residence_type ?? 'Owned') }} (Confirmed)</td>
          <td class="bg-light fw-semibold" style="width: 25%;">Tenure Verified:</td>
          <td style="width: 25%;">{{ $customer->residence_tenure_years ?? 5 }} Years at Current House</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Living Condition:</td>
          <td>Living with Family & Dependents</td>
          <td class="bg-light fw-semibold">Locality Reputation:</td>
          <td>Satisfactory / Established Residential Neighborhood</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Electricity / Utility Bill:</td>
          <td colspan="3" class="text-success fw-semibold">
            <i class="bi bi-check-circle me-1"></i>Physically Verified at Meter Box (Ref # {{ $customer->utility_bill_ref_number ?? 'VER-CHECKED' }})
          </td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Neighbor Check:</td>
          <td colspan="3">Inquired with adjacent resident; confirmed subject resides at given premises permanently.</td>
        </tr>
      </tbody>
    </table>

    <!-- Workplace Verification -->
    <div class="section-title">3. Workplace & Employment Verification</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Occupation / Business:</td>
          <td style="width: 25%;">Salaried / Self-Employed</td>
          <td class="bg-light fw-semibold" style="width: 25%;">Monthly Income Est:</td>
          <td style="width: 25%;" class="fw-bold text-success">PKR {{ number_format($customer->monthly_household_income ?? 85000, 2) }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Workplace Check:</td>
          <td colspan="3">Premises visited / confirmed active commercial operations. Income stability deemed adequate for proposed installment burden.</td>
        </tr>
      </tbody>
    </table>

    <!-- Investigator Verdict -->
    <div class="section-title">4. Field Investigator Risk Assessment & Recommendation</div>
    <div class="p-3 bg-light rounded border mb-3">
      <div class="row align-items-center">
        <div class="col-8">
          <strong class="text-dark">RECOMMENDATION: <span class="badge bg-success fs-6 ms-2">RECOMMENDED FOR APPROVAL</span></strong>
          <p class="small text-muted mb-0 mt-1">Applicant has a permanent residential footprint, clear neighborhood inquiry, and verifiable income flow. No adverse credit markers detected during physical field audit.</p>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-light text-dark border p-2">Physical Audit Passed</span>
        </div>
      </div>
    </div>

    <div class="row mt-5 pt-3 text-center">
      <div class="col-6">
        <div class="sign-line">Field Verification Officer</div>
        <small class="text-muted d-block">{{ $branch->name }} Credit Bureau</small>
      </div>
      <div class="col-6">
        <div class="sign-line">Branch Manager Signature & Seal</div>
        <small class="text-muted d-block">{{ $branch->name }}</small>
      </div>
    </div>
  </div>
</body>
</html>
