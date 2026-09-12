<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Early Settlement Letter - {{ $agreement->account_number }}</title>
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
      border-left: 4px solid #fd7e14;
      margin-top: 15px;
      margin-bottom: 10px;
    }
    .settlement-card {
      background: #fff8f0;
      border: 2px solid #fd7e14;
      border-radius: 8px;
      padding: 15px 20px;
    }
    .sign-box {
      border-top: 1px solid #000;
      margin-top: 45px;
      padding-top: 4px;
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
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">Print Settlement Letter (A4)</button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">Close</button>
  </div>

  <div class="doc-page">
    <!-- Header -->
    <div class="header-rule">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-0 text-dark">{{ $company->name }}</h3>
          <div class="small text-muted">{{ $company->legal_name }} &bull; {{ $branch->name }}</div>
          <div class="small text-muted">{{ $branch->address }}, {{ $branch->city }} &bull; Tel: {{ $branch->phone }}</div>
        </div>
        <div class="col-4 text-end">
          <span class="badge bg-warning text-dark fs-6 p-2 text-uppercase mb-1">EARLY SETTLEMENT QUOTE</span>
          <div class="small fw-bold">Ref: <span class="font-monospace">STL-{{ str_pad($agreement->id, 5, '0', STR_PAD_LEFT) }}</span></div>
          <div class="small text-muted">Issue Date: {{ now()->format('d M, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Addressee -->
    <div class="mb-4">
      <div>To:</div>
      <div class="fw-bold fs-6">{{ $customer->full_name }}</div>
      <div>CNIC: {{ $customer->cnic }} &bull; Mobile: {{ $customer->mobile_primary }}</div>
      <div>Address: {{ $customer->present_address }}</div>
    </div>

    <div class="fw-bold mb-3 text-uppercase border-bottom pb-2">
      Subject: Early Settlement Calculation &amp; Full Payoff Offer &bull; Agreement #{{ $agreement->account_number }}
    </div>

    <p style="text-align: justify;">
      Dear Customer,<br>
      In response to your request for early pre-closure of your installment financing facility for <strong>{{ $product?->name ?? 'Electronic Merchandise' }}</strong>, we are pleased to present the official early payoff settlement calculation. In accordance with showroom credit policies, an unearned markup rebate of <strong>{{ number_format($settlement['rebate_pct'], 1) }}%</strong> has been applied to reduce your financial obligation.
    </p>

    <!-- Section 1: Settlement Computation Breakdown -->
    <div class="section-title">1. Early Settlement &amp; Rebate Computation</div>
    <table class="table table-bordered mb-3">
      <tbody>
        <tr>
          <td class="bg-light fw-semibold" style="width: 60%;">Remaining Contract Balance (Principal + Remaining Markup):</td>
          <td class="text-end fw-semibold">PKR {{ number_format($settlement['contract_balance_before'], 2) }}</td>
        </tr>
        <tr>
          <td class="ps-4">a. Remaining Outstanding Principal:</td>
          <td class="text-end">PKR {{ number_format($settlement['remaining_principal'], 2) }}</td>
        </tr>
        <tr>
          <td class="ps-4">b. Unearned Future Financing Markup:</td>
          <td class="text-end">PKR {{ number_format($settlement['unearned_markup'], 2) }}</td>
        </tr>
        <tr class="table-success">
          <td class="ps-4 fw-bold text-success">
            Less: Unearned Markup Rebate Discount ({{ number_format($settlement['rebate_pct'], 1) }}%):
          </td>
          <td class="text-end fw-bold text-success">- PKR {{ number_format($settlement['markup_rebate'], 2) }}</td>
        </tr>
        <tr>
          <td class="ps-4">c. Retained Markup by Showroom:</td>
          <td class="text-end">PKR {{ number_format($settlement['retained_markup'], 2) }}</td>
        </tr>
        @if(($settlement['accrued_late_fees'] ?? 0) > 0)
        <tr class="table-warning">
          <td class="ps-4 fw-semibold text-danger">Add: Accrued Overdue Late Surcharges:</td>
          <td class="text-end fw-semibold text-danger">+ PKR {{ number_format($settlement['accrued_late_fees'], 2) }}</td>
        </tr>
        @endif
        <tr class="table-dark fs-6 fw-bold">
          <td>TOTAL NET SETTLEMENT PAYABLE AMOUNT:</td>
          <td class="text-end text-warning">PKR {{ number_format($settlement['net_settlement_amount'], 2) }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Callout Offer Box -->
    <div class="settlement-card mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small fw-bold text-uppercase text-secondary">FINAL EARLY PAYOFF AMOUNT</div>
          <div class="fs-4 fw-bold text-dark">PKR {{ number_format($settlement['net_settlement_amount'], 2) }}</div>
          <div class="small text-muted">You save <strong>PKR {{ number_format($settlement['markup_rebate'], 2) }}</strong> via early closure!</div>
        </div>
        <div class="text-end">
          <span class="badge bg-danger fs-6 p-2 mb-1">OFFER VALIDITY</span>
          <div class="small fw-bold text-danger">Valid until: {{ \Carbon\Carbon::parse($settlement['valid_until'])->format('d F, Y') }}</div>
          <div class="small text-muted">(Subject to payment on or before this date)</div>
        </div>
      </div>
    </div>

    <!-- Section 2: Terms & Pre-Closure Protocol -->
    <div class="section-title">2. Settlement Conditions &amp; Release Protocol</div>
    <ol class="small text-muted ps-3 mb-4" style="line-height: 1.7;">
      <li>This settlement quotation is valid strictly up to <strong>{{ \Carbon\Carbon::parse($settlement['valid_until'])->format('d M, Y') }}</strong>. If payment is delayed beyond this date, the rebate calculation shall be void and recomputed.</li>
      <li>Payment must be remitted in Cash or verified Direct Bank Transfer at our showroom cashier counter.</li>
      <li>Upon full clearance of the settlement amount, an official <strong>Clearance Certificate &amp; No Objection Certificate (NOC)</strong> will be issued immediately, transferring full title to the purchaser.</li>
      <li>All post-dated security cheques and promissory notes will be officially stamped 'CANCELLED' and handed back to the customer.</li>
    </ol>

    <!-- Signatures -->
    <div class="row pt-2 text-center">
      <div class="col-4">
        <div class="sign-box">
          ACCOUNTS MANAGER<br>
          <span class="small text-muted">Rebate Computed By</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          BRANCH MANAGER<br>
          <span class="small text-muted">Authorized Signatory &amp; Stamp</span>
        </div>
      </div>
      <div class="col-4">
        <div class="sign-box">
          CUSTOMER ACCEPTANCE<br>
          <span class="small text-muted">{{ $customer->full_name }}</span>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
