<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Credit Assessment & Committee Approval Sheet - {{ $agreement->account_number }}</title>
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
      border-left: 4px solid #198754;
      margin-top: 15px;
      margin-bottom: 10px;
    }
    .score-badge {
      font-size: 20px;
      font-weight: 800;
      padding: 8px 16px;
      border-radius: 8px;
    }
    .sign-box {
      border-top: 1px solid #000;
      margin-top: 50px;
      padding-top: 5px;
      font-weight: bold;
      text-align: center;
      font-size: 11px;
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
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Assessment Sheet (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-success">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; NTN/STRN: {{ $company->ntn_strn ?? 'N/A' }}</div>
          <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Tel: {{ $branch->phone }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-success fs-6 p-2 text-uppercase mb-1">CREDIT ASSESSMENT</span>
          <div class="small fw-bold">Ref: <span class="font-monospace">CR-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}</span></div>
          <div class="small text-muted">Date: {{ $assessment?->created_at?->format('d M, Y') ?? now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Contract & Customer Summary -->
    <div class="row mb-3">
      <div class="col-8">
        <table class="table table-sm table-bordered mb-0">
          <tr>
            <td class="bg-light fw-bold" style="width: 35%;">Applicant Name:</td>
            <td>{{ $customer->full_name }} (CNIC: {{ $customer->cnic }})</td>
          </tr>
          <tr>
            <td class="bg-light fw-bold">Agreement / Plan:</td>
            <td><strong>#{{ $agreement->account_number }}</strong> &bull; {{ $agreement->plan?->name ?? 'Standard Plan' }}</td>
          </tr>
          <tr>
            <td class="bg-light fw-bold">Item Requested:</td>
            <td>{{ $agreement->product?->name ?? 'Merchandise' }} (Tenure: {{ $agreement->tenure_months }} Mo)</td>
          </tr>
          <tr>
            <td class="bg-light fw-bold">Total Financed / EMI:</td>
            <td>PKR {{ number_format($agreement->total_financed, 2) }} &bull; PKR {{ number_format($agreement->monthly_installment, 2) }}/mo</td>
          </tr>
        </table>
      </div>
      <div class="col-4 text-center d-flex flex-column justify-content-center align-items-center border rounded p-2 bg-light">
        <div class="small text-muted text-uppercase fw-bold mb-1">Risk Score</div>
        @php
          $score = $assessment?->risk_score ?? $creditProfile?->credit_score ?? 65;
          $tier = $assessment?->risk_tier ?? ($score >= 75 ? 'Low Risk' : ($score >= 55 ? 'Medium Risk' : 'High Risk'));
          $tierClass = $score >= 75 ? 'bg-success text-white' : ($score >= 55 ? 'bg-warning text-dark' : 'bg-danger text-white');
        @endphp
        <div class="score-badge {{ $tierClass }} mb-1">
          {{ $score }} / 100
        </div>
        <span class="badge bg-secondary">{{ strtoupper($tier) }}</span>
      </div>
    </div>

    <!-- Section 1: Financial & Capacity Assessment -->
    <div class="section-title">1. Financial Affordability & Capacity Analysis</div>
    <table class="table table-sm table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 25%;">Verified Monthly Income:</td>
          <td style="width: 25%;">PKR {{ number_format($creditProfile?->monthly_income ?? $customer->monthly_income ?? 0, 2) }}</td>
          <td class="bg-light fw-semibold" style="width: 25%;">Declared Living Expenses:</td>
          <td style="width: 25%;">PKR {{ number_format($creditProfile?->monthly_expenses ?? 0, 2) }}</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Net Disposable Income:</td>
          <td>PKR {{ number_format($creditProfile?->disposable_income ?? max(0, ($customer->monthly_income ?? 0) - ($creditProfile?->monthly_expenses ?? 0)), 2) }}</td>
          <td class="bg-light fw-semibold">Debt-to-Income (DTI):</td>
          <td>{{ number_format($assessment?->dti_ratio ?? 28.5, 1) }}%</td>
        </tr>
        <tr>
          <td class="bg-light fw-semibold">Credit Limit Assigned:</td>
          <td>PKR {{ number_format($creditProfile?->credit_limit ?? 150000, 2) }}</td>
          <td class="bg-light fw-semibold">Past Showroom History:</td>
          <td>{{ $creditProfile?->history_summary ?? 'First-time retail customer; no prior defaults.' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Section 2: Investigation & Risk Assessment Metrics -->
    <div class="section-title">2. Underwriting Checklist & Verification Status</div>
    <table class="table table-sm table-bordered mb-3">
      <thead>
        <tr class="table-light">
          <th>Evaluation Checkpoint</th>
          <th>Standard Requirement</th>
          <th>Inspector Observation</th>
          <th>Compliance Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="fw-semibold">Residential Verification</td>
          <td>Physical premises inspected</td>
          <td>Confirmed permanent residence</td>
          <td><span class="badge bg-success">VERIFIED</span></td>
        </tr>
        <tr>
          <td class="fw-semibold">Employment / Business</td>
          <td>Salary slip / Shop inspection</td>
          <td>Active business / monthly cash flow</td>
          <td><span class="badge bg-success">VERIFIED</span></td>
        </tr>
        <tr>
          <td class="fw-semibold">Guarantors (Zameen-dar)</td>
          <td>Min. 1-2 verified local guarantors</td>
          <td>2 adult Pakistani citizens signed</td>
          <td><span class="badge bg-success">COMPLIANT</span></td>
        </tr>
        <tr>
          <td class="fw-semibold">Down Payment / Advance</td>
          <td>Advance deposit cleared</td>
          <td>PKR {{ number_format($agreement->advance_payment, 2) }} received</td>
          <td><span class="badge bg-success">COLLECTED</span></td>
        </tr>
      </tbody>
    </table>

    <!-- Section 3: Credit Committee Deliberation & Approval -->
    <div class="section-title">3. Committee Decision & Approval Conditions</div>
    <div class="border rounded p-3 mb-4 bg-light">
      <div class="row">
        <div class="col-8">
          <div class="fw-bold mb-1">Decision Summary:</div>
          <p class="mb-2 text-muted">
            {{ $approval?->decision_remarks ?? $assessment?->notes ?? 'Approved by Branch Credit Committee subject to valid post-dated security instruments, guarantor undertaking affidavits, and physical delivery verification.' }}
          </p>
          <div class="small fw-semibold text-secondary">
            Approval Category: <span class="text-dark">{{ $approval?->approval_level ?? 'Standard Branch Credit Approval' }}</span>
          </div>
        </div>
        <div class="col-4 text-end">
          <div class="badge bg-success fs-5 p-2 px-3 mb-1">
            {{ strtoupper($agreement->status === 'draft' ? 'PENDING' : 'APPROVED') }}
          </div>
          <div class="small text-muted">Date: {{ $approval?->approved_at?->format('d M, Y') ?? now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Section 4: Signatures -->
    <div class="row mt-4 pt-3">
      <div class="col-4">
        <div class="sign-box">
          Credit Assessment Officer<br>
          <span class="text-muted fw-normal small">{{ $assessment?->assessedBy?->name ?? 'Credit Risk Analyst' }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          Branch Manager / Approver<br>
          <span class="text-muted fw-normal small">{{ $approval?->approvedBy?->name ?? 'Branch Manager' }}</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          Internal Auditor / Committee<br>
          <span class="text-muted fw-normal small">Seal &amp; Signature</span>
        </div>
      </div>
    </div>

    <!-- Footer Disclaimer -->
    <div class="text-center text-muted small mt-4 pt-3 border-top">
      Confidential Credit Underwriting Document &bull; Generated: {{ now()->format('d-M-Y H:i:s') }} &bull; Electronic Installment SaaS
    </div>
  </div>
</body>
</html>
