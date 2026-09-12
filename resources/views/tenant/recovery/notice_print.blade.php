<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Legal Notice - {{ $notice->notice_number }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Times New Roman', Times, serif;
      color: #111;
      font-size: 14px;
      line-height: 1.6;
    }
    .notice-paper {
      background: #fff;
      max-width: 800px;
      margin: 30px auto;
      padding: 50px 60px;
      border: 1px solid #ddd;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .header-border {
      border-bottom: 2px solid #000;
      padding-bottom: 15px;
      margin-bottom: 25px;
    }
    .stamp-box {
      border: 2px dashed #999;
      padding: 15px;
      text-align: center;
      min-height: 80px;
    }
    @media print {
      body {
        background: #fff !important;
        margin: 0;
        padding: 0;
      }
      .notice-paper {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 20mm 20mm !important;
        max-width: 100% !important;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>
  <!-- Print Control Bar -->
  <div class="container text-center my-3 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 me-2">
      <i class="bi bi-printer"></i> Print Legal Notice (A4)
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
      Close Window
    </button>
  </div>

  <div class="notice-paper">
    <!-- Letterhead Header -->
    <div class="header-border">
      <div class="row align-items-center">
        <div class="col-8">
          <h3 class="fw-bold text-uppercase mb-1" style="letter-spacing: 1px;">{{ $notice->company->name }}</h3>
          <p class="mb-0 text-muted small">{{ $notice->company->legal_name }}</p>
          <p class="mb-0 text-muted small">NTN / STRN: {{ $notice->company->ntn_strn ?? 'REGISTERED ENTITY' }}</p>
          <p class="mb-0 text-muted small">{{ $notice->recoveryCase->branch->address }}, {{ $notice->recoveryCase->branch->city }}</p>
          <p class="mb-0 text-muted small">Showroom Helpline: {{ $notice->recoveryCase->branch->phone }} | Email: {{ $notice->company->email }}</p>
        </div>
        <div class="col-4 text-end">
          <div class="badge bg-danger text-white fs-6 p-2 text-uppercase mb-2">
            LEGAL NOTICE
          </div>
          <div class="small fw-bold">Ref: <span class="font-monospace">{{ $notice->notice_number }}</span></div>
          <div class="small text-muted">Date: {{ $notice->issued_at->format('d F, Y') }}</div>
        </div>
      </div>
    </div>

    <!-- Mode of Delivery -->
    <div class="mb-3 text-uppercase fw-bold small text-muted">
      <strong>BY:</strong> {{ strtoupper(str_replace('_', ' ', $notice->delivery_channel)) }} / RECOVERY SQUAD HAND DELIVERY
    </div>

    <!-- Recipient Dossier -->
    <div class="row mb-4">
      <div class="col-7">
        <strong class="d-block text-uppercase">TO:</strong>
        <div class="fw-bold fs-6">{{ $notice->recipient_name }}</div>
        <div>CNIC No: <span class="font-monospace">{{ $notice->recoveryCase->customer->cnic }}</span></div>
        <div>Contact: {{ $notice->recipient_contact }}</div>
        <div>Address: {{ $notice->recipient_address ?? $notice->recoveryCase->customer->present_address }}</div>
      </div>
      <div class="col-5">
        <div class="p-2 border rounded bg-light small">
          <strong>CONTRACT PARTICULARS:</strong><br>
          Account No: <span class="font-monospace fw-bold">{{ $notice->agreement->account_number }}</span><br>
          Merchandise: {{ $notice->agreement->product?->name }}<br>
          IMEI / Serial: <span class="font-monospace fw-bold">{{ $notice->agreement->serializedItem?->serial_number ?? 'N/A' }}</span><br>
          Showroom Branch: {{ $notice->recoveryCase->branch->name }}
        </div>
      </div>
    </div>

    <!-- Subject Header -->
    <div class="bg-light p-2 border-top border-bottom mb-4 text-uppercase fw-bold text-center">
      SUBJECT: FORMAL DEMAND NOTICE & NOTICE OF DEFAULT FOR IMMEDIATE LIQUIDATION OF OVERDUE INSTALLMENT DUES UNDER APPLICABLE CONTRACT LAW
    </div>

    <!-- Legal Notice Body -->
    <p>Dear Sir / Madam,</p>

    <p>
      Under instructions from and on behalf of our client, <strong>{{ $notice->company->name }}</strong>, we hereby issue you this formal Legal Notice of Default regarding your Retail Installment Financing Contract (Account Reference: <strong>{{ $notice->agreement->account_number }}</strong>) entered into for the acquisition of serialized product <strong>{{ $notice->agreement->product?->name }}</strong> (IMEI/Serial: <strong>{{ $notice->agreement->serializedItem?->serial_number ?? 'N/A' }}</strong>).
    </p>

    <p>
      As per the agreed contractual repayment schedule, you undertook to remit regular installment payments on their stipulated calendar due dates. Regrettably, our financial ledger records reveal that you have wilfully failed, neglected, and defaulted on your financial obligations, causing the account to accumulate substantial overdue arrears:
    </p>

    <!-- Financial Breakdown Table -->
    <table class="table table-bordered table-sm my-3">
      <thead class="table-light">
        <tr>
          <th>Description of Liability</th>
          <th class="text-end">Amount (PKR)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Accrued Overdue Installments (Principal + Financed Markup)</td>
          <td class="text-end fw-bold">{{ number_format($notice->overdue_amount, 2) }}</td>
        </tr>
        <tr>
          <td>Contractual Late Payment Penalties / Administrative Charges</td>
          <td class="text-end fw-bold">{{ number_format($notice->late_fees_amount, 2) }}</td>
        </tr>
        <tr class="table-danger fw-bold">
          <td>TOTAL IMMEDIATE RECOVERY DEMAND</td>
          <td class="text-end fs-6">PKR {{ number_format($notice->total_demand_amount, 2) }}</td>
        </tr>
      </tbody>
    </table>

    <p>
      <strong>TAKE NOTICE</strong> that you are hereby called upon to pay the aforesaid outstanding sum of <strong>PKR {{ number_format($notice->total_demand_amount, 2) }}</strong> at the showroom cash counter of <strong>{{ $notice->recoveryCase->branch->name }}</strong> on or before <strong>{{ $notice->demand_deadline->format('d F, Y') }}</strong> (within stipulated deadline).
    </p>

    <p>
      <strong>PLEASE TAKE FURTHER NOTICE</strong> that upon your failure to liquidate the said demand within the stipulated period, our client reserves the unequivocal legal right, without further reference to you, to initiate the following remedies:
    </p>

    <ol>
      <li><strong>Immediate Physical Repossession:</strong> Executing immediate repossession and seizure of the financed merchandise ({{ $notice->agreement->product?->name }} / {{ $notice->agreement->serializedItem?->serial_number }}) in terms of the specific repossession covenants executed in the Master Agreement.</li>
      <li><strong>Joint & Several Guarantor Enforcement:</strong> Enforcing recovery against the personal assets, bank accounts, and promissory undertakings of all co-signers and guarantors.</li>
      <li><strong>Civil & Criminal Legal Proceedings:</strong> Instituting legal proceedings for recovery of outstanding dues, damages, and litigation expenses under the Contract Act 1872 and related commercial statutes.</li>
      <li><strong>Credit Blacklisting:</strong> Reporting your default to retail merchant risk registers and credit information databases.</li>
    </ol>

    <p>
      To avert severe legal consequences, repossession of property, and irreparable damage to your credit standing, you are advised to treat this communication as extremely urgent.
    </p>

    <p class="mb-5">Yours faithfully,</p>

    <!-- Signatures -->
    <div class="row pt-4 text-center">
      <div class="col-4">
        <div style="border-top: 1px solid #000; padding-top: 5px;">
          <strong>{{ $notice->issuedBy?->name }}</strong><br>
          <small class="text-muted">Legal & Recovery Officer<br>{{ $notice->company->name }}</small>
        </div>
      </div>
      <div class="col-4">
        <div class="stamp-box">
          <small class="text-muted d-block">SHOWROOM OFFICIAL STAMP</small>
        </div>
      </div>
      <div class="col-4">
        <div style="border-top: 1px solid #000; padding-top: 5px;">
          <strong>Branch Manager</strong><br>
          <small class="text-muted">{{ $notice->recoveryCase->branch->name }}<br>{{ $notice->recoveryCase->branch->city }}</small>
        </div>
      </div>
    </div>

    <!-- Copy to Guarantors footer -->
    <div class="mt-5 pt-3 border-top small text-muted">
      <strong>COPY TO:</strong><br>
      1. Primary Guarantor: {{ $notice->agreement->guarantors()->wherePivot('is_primary', true)->first()?->full_name ?? 'On Record' }}<br>
      2. Recovery Squad / Field Enforcement File Ref: {{ $notice->recoveryCase->case_number }}
    </div>
  </div>
</body>
</html>
