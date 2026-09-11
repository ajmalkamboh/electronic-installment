<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Installment Contract - {{ $agreement->account_number }}</title>
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <style>
    body {
      background-color: #fff;
      font-size: 13px;
      color: #222;
      line-height: 1.5;
    }
    .contract-box {
      max-width: 850px;
      margin: 20px auto;
      padding: 30px;
      border: 1px solid #ddd;
    }
    .watermark {
      position: absolute;
      top: 40%;
      left: 20%;
      font-size: 80px;
      color: rgba(0,0,0,0.04);
      transform: rotate(-30deg);
      pointer-events: none;
      font-weight: bold;
      text-transform: uppercase;
    }
    .table-sm th, .table-sm td {
      padding: 5px 8px;
    }
    @media print {
      body {
        margin: 0;
        padding: 0;
      }
      .contract-box {
        border: none;
        padding: 0;
        margin: 0;
        max-width: 100%;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

<div class="no-print bg-light py-2 border-bottom text-center mb-4">
  <button type="button" class="btn btn-primary btn-sm me-2" onclick="window.print()">
    Print Legal Agreement
  </button>
  <button type="button" class="btn btn-secondary btn-sm" onclick="window.close()">
    Close Window
  </button>
</div>

<div class="contract-box position-relative">
  <div class="watermark">{{ $agreement->status }}</div>

  <!-- Header & Letterhead -->
  <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
    <div>
      <h3 class="fw-bold text-uppercase mb-1 text-primary">{{ $agreement->company->name }}</h3>
      <div class="text-muted small">Showroom: {{ $agreement->branch->name }} (Code: {{ $agreement->branch->code }})</div>
      <div class="text-muted small">{{ $agreement->branch->address ?? 'Main Electronic Market' }} &bull; Ph: {{ $agreement->branch->phone ?? 'N/A' }}</div>
    </div>
    <div class="text-end">
      <div class="badge bg-dark fs-6 font-monospace mb-1">{{ $agreement->account_number }}</div>
      <div class="text-muted small">Date: <strong>{{ $agreement->start_date->format('d F, Y') }}</strong></div>
      <div class="text-muted small">Status: <strong>{{ strtoupper($agreement->status) }}</strong></div>
    </div>
  </div>

  <div class="text-center my-3">
    <h5 class="fw-bold text-uppercase text-decoration-underline mb-1">Electronic Installment Sales & Financing Agreement</h5>
    <small class="text-muted fst-italic">Legally binding contract under the Sale of Goods Act & Contract Act of Pakistan</small>
  </div>

  <!-- 1. Parties Particulars -->
  <div class="mb-3">
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 bg-light p-1">1. Contracting Parties</h6>
    <div class="row g-2">
      <div class="col-6">
        <div class="p-2 border rounded">
          <strong class="d-block text-primary small text-uppercase">First Party (Seller / Creditor):</strong>
          <div><strong>{{ $agreement->company->name }}</strong></div>
          <div class="small text-muted">Branch: {{ $agreement->branch->name }}</div>
          <div class="small text-muted">Showroom Officer: {{ $agreement->creator->name }}</div>
        </div>
      </div>
      <div class="col-6">
        <div class="p-2 border rounded">
          <strong class="d-block text-primary small text-uppercase">Second Party (Purchaser / Debtor):</strong>
          <div><strong>{{ $agreement->customer->full_name }}</strong> (S/O: {{ $agreement->customer->father_or_husband_name }})</div>
          <div class="small">CNIC: <strong class="font-monospace">{{ $agreement->customer->cnic }}</strong> &bull; Cell: {{ $agreement->customer->mobile_primary }}</div>
          <div class="small text-muted text-truncate">{{ $agreement->customer->present_address }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Financed Merchandise Details -->
  <div class="mb-3">
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 bg-light p-1">2. Financed Merchandise Specifications</h6>
    <table class="table table-bordered table-sm mb-0">
      <tbody>
        <tr>
          <th class="bg-light" style="width: 25%;">Brand & Model:</th>
          <td><strong>{{ $agreement->product->brand }} {{ $agreement->product->model_name }}</strong></td>
          <th class="bg-light" style="width: 25%;">Product SKU:</th>
          <td class="font-monospace">{{ $agreement->product->sku }}</td>
        </tr>
        @if($agreement->serializedItem)
          <tr>
            <th class="bg-light">Primary IMEI (SIM 1):</th>
            <td class="font-monospace fw-bold">{{ $agreement->serializedItem->imei_1 ?? 'N/A' }}</td>
            <th class="bg-light">Secondary IMEI (SIM 2):</th>
            <td class="font-monospace">{{ $agreement->serializedItem->imei_2 ?? 'N/A' }}</td>
          </tr>
          <tr>
            <th class="bg-light">Serial Number:</th>
            <td class="font-monospace">{{ $agreement->serializedItem->serial_number ?? 'N/A' }}</td>
            <th class="bg-light">Physical Inspection:</th>
            <td>Inspected and verified original by Purchaser</td>
          </tr>
        @endif
      </tbody>
    </table>
  </div>

  <!-- 3. Financial Schedule & Repayment Terms -->
  <div class="mb-3">
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 bg-light p-1">3. Financial Terms & Schedule of Repayment</h6>
    <table class="table table-bordered table-sm text-center mb-2">
      <thead class="bg-light small">
        <tr>
          <th>Cash Price</th>
          <th>Down Payment</th>
          <th>Financed Principal</th>
          <th>Markup Rate</th>
          <th>Markup Amount</th>
          <th>Total Payable</th>
        </tr>
      </thead>
      <tbody>
        <tr class="fw-bold">
          <td>Rs. {{ number_format($agreement->cash_price) }}</td>
          <td class="text-primary">Rs. {{ number_format($agreement->down_payment_amount) }}</td>
          <td>Rs. {{ number_format($agreement->financed_principal) }}</td>
          <td>{{ number_format($agreement->markup_rate_pct, 2) }}%</td>
          <td class="text-success">Rs. {{ number_format($agreement->markup_amount) }}</td>
          <td class="text-primary fs-6">Rs. {{ number_format($agreement->total_payable) }}</td>
        </tr>
      </tbody>
    </table>

    <div class="row g-2 small">
      <div class="col-4">
        <div class="p-2 border rounded">
          <span class="text-muted d-block">Monthly Installment:</span>
          <strong class="fs-6 text-primary">Rs. {{ number_format($agreement->installment_amount) }}</strong>
        </div>
      </div>
      <div class="col-4">
        <div class="p-2 border rounded">
          <span class="text-muted d-block">Duration & Frequency:</span>
          <strong>{{ $agreement->tenure_months }} Months ({{ ucfirst($agreement->installment_frequency) }})</strong>
        </div>
      </div>
      <div class="col-4">
        <div class="p-2 border rounded">
          <span class="text-muted d-block">Schedule Dates:</span>
          <strong>{{ $agreement->first_due_date->format('d/m/Y') }} to {{ $agreement->maturity_date->format('d/m/Y') }}</strong>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. Guarantors Undertaking -->
  @if($agreement->guarantors->isNotEmpty())
    <div class="mb-3">
      <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 bg-light p-1">4. Legal Guarantors & Joint Undertaking</h6>
      <div class="row g-2">
        @foreach($agreement->guarantors as $g)
          <div class="col-6">
            <div class="p-2 border rounded small">
              <div><strong>{{ $g->full_name }}</strong> ({{ $g->relationship }})</div>
              <div>CNIC: <strong class="font-monospace">{{ $g->cnic }}</strong> &bull; Ph: {{ $g->mobile }}</div>
              <div class="text-muted">{{ $g->address }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <!-- 5. Legal Terms & Conditions -->
  <div class="mb-4">
    <h6 class="fw-bold text-uppercase border-bottom pb-1 mb-2 bg-light p-1">5. Mandatory Contract Undertakings</h6>
    <ol class="small text-muted mb-0 ps-3">
      <li><strong>Retention of Title:</strong> Absolute ownership of the merchandise remains vested with the Seller until all monthly installments, markup, and applicable charges are paid in full.</li>
      <li><strong>Default & Acceleration:</strong> Failure to pay any monthly installment by the due date shall constitute default, empowering the Seller to accelerate the total outstanding balance and/or repossess the merchandise without court intervention.</li>
      <li><strong>Guarantor Liability:</strong> The undersigned Guarantors undertake joint and several liability with the Purchaser for all unpaid sums, damages, and recovery costs.</li>
      <li><strong>Jurisdiction:</strong> Any dispute arising hereunder shall be subject to the exclusive jurisdiction of the competent courts of the branch district.</li>
    </ol>
  </div>

  <!-- Signatures -->
  <div class="pt-4 border-top">
    <div class="row text-center">
      <div class="col-3">
        <div class="border-top pt-2 mt-4">
          <strong class="d-block small">Purchaser Signature</strong>
          <small class="text-muted">Thumb Impression</small>
        </div>
      </div>
      <div class="col-3">
        <div class="border-top pt-2 mt-4">
          <strong class="d-block small">Guarantor Signature</strong>
          <small class="text-muted">Primary Guarantor</small>
        </div>
      </div>
      <div class="col-3">
        <div class="border-top pt-2 mt-4">
          <strong class="d-block small">Branch Manager</strong>
          <small class="text-muted">Authorized Officer</small>
        </div>
      </div>
      <div class="col-3">
        <div class="border-top pt-2 mt-4">
          <strong class="d-block small">Witness Signature</strong>
          <small class="text-muted">Showroom Witness</small>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
