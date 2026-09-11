# Business Domain Specification & Workflow

## 1. Comprehensive Domain Model & Entity Responsibilities

The **Electronic Installment SaaS** encompasses 34 interconnected business domain entities. Below is the architectural responsibility definition for each:

| # | Entity | Domain Scope | Primary Operational Responsibilities |
| :--- | :--- | :--- | :--- |
| 1 | **Platform** | Global | Root platform operator managing multiple company tenants, global settings, and platform health. |
| 2 | **Company (Tenant)** | Tenant | Legal commercial entity selling electronics and appliances through installment financing contracts. |
| 3 | **Branch** | Operational | Physical showroom, retail store, or warehouse outlet belonging to a company. |
| 4 | **User** | Security | System user (Admin, Manager, Credit Officer, Cashier, Collector, Accountant). |
| 5 | **Role & Permission** | Security | Granular access control defining functional capabilities per tenant and branch. |
| 6 | **Customer** | Shared | Individual buying electronics on installment terms. Identified by CNIC / National ID. |
| 7 | **CustomerCreditProfile**| Shared | Dynamic risk rating, cumulative repayment score, active debt burden, and blacklist status. |
| 8 | **Guarantor** | Shared | Third-party individual formally guaranteeing contract fulfillment and legal liability. |
| 9 | **Reference** | Shared | Personal or professional acquaintance verifying customer character and residence. |
| 10 | **CustomerVerification** | Shared | Physical and documentary verification of customer residence, utility bills, and employment. |
| 11 | **CreditAssessment** | Risk | Analytical evaluation assessing repayment capacity, disposable income, and debt-to-income ratio. |
| 12 | **CreditApproval** | Governance | Multi-tier approval milestone authorized by Branch Manager or Company Owner before contract signing. |
| 13 | **ProductCategory** | Company | Categorization hierarchy (Smartphones, LED TVs, Refrigerators, ACs, Solar Systems). |
| 14 | **Product** | Company | Specific commercial model definition, technical specifications, and baseline cash price. |
| 15 | **Supplier** | Company | Commercial vendor or distributor supplying electronics to the company. |
| 16 | **Inventory** | Branch | Aggregated stock counts and physical inventory on hand at an individual branch showroom. |
| 17 | **SerializedItem** | Branch | Distinct physical unit tracked by IMEI (1 & 2), Serial Number, or Asset Tag. |
| 18 | **StockMovement** | Branch | Audit log of inventory receipts, branch transfers, returns, or adjustments. |
| 19 | **InstallmentPlan** | Company | Standard financing template defining tenure (3 to 24 months), markup %, and down payment %. |
| 20 | **InstallmentAgreement** | Branch | Core binding legal contract between Company and Customer specifying financial terms. |
| 21 | **PaymentSchedule** | Financial | Chronological breakdown of monthly or weekly installments with exact due dates. |
| 22 | **InstallmentPayment** | Financial | Recorded cash, bank, or mobile transaction submitted towards installment fulfillment. |
| 23 | **PaymentAllocation** | Financial | Mathematical ledger allocation of payment towards fees, overdue, current, or advance installments. |
| 24 | **PaymentMethod** | Financial | Transaction channel used (`cash`, `bank_transfer`, `cheque`, `raast`, `easypaisa`, `jazzcash`). |
| 25 | **Receipt** | Financial | Sequentially numbered audit token issued to customer for physical or digital verification. |
| 26 | **LateFee** | Financial | Surcharge automatically applied when payment exceeds grace period without waiver. |
| 27 | **LateFeeWaiver** | Governance | Auditable managerial override reducing or waiving late fees with mandatory reason. |
| 28 | **RecoveryPolicy** | Governance | Company-configurable timeline for automated reminders, escalation, field visits, and legal recovery. |
| 29 | **CollectionOfficer** | Operational | Assigned staff member tasked with visiting customers, collecting field cash, and recovery. |
| 30 | **CustomerVisit** | Operational | Physical field visit record capturing collector GPS, customer interaction, and promise-to-pay. |
| 31 | **Notification** | Communication| Multi-channel alert (SMS, WhatsApp, Email, In-App) for due dates, receipts, and defaults. |
| 32 | **SaaSPlan** | Platform | Subscription tier (Free, Trial, Basic, Professional, Enterprise) defining tenant quotas. |
| 33 | **Subscription** | Platform | Active SaaS billing and license status binding a Company to a SaaSPlan. |
| 34 | **AuditLog** | Security | Tamper-evident ledger of critical mutations, status transitions, approvals, and overrides. |

---

## 2. Customer Lifecycle & Customer Credit Profile

### 2.1 Multi-Stage Lifecycle
```text
  [ New Customer Lead ]
            │
            ▼
  [ Registration & Profile ] ───► CNIC (13 digits), Phone, Address, Residence Type, Employment
            │
            ▼
  [ References & Guarantors ] ──► Minimum 1-2 verified guarantors with CNIC & Contact
            │
            ▼
  [ Physical Verification ] ────► Field inquiry, Utility bill verification, Neighbor check
            │
            ▼
  [ Credit Risk Assessment ] ───► Income evaluation, debt capacity, risk scoring
            │
            ▼
  [ Credit Approval / Denial ] ─► Authorized by Branch Manager / Credit Committee
            │
            ▼
  [ Customer Eligible ] ────────► Credit limit unlocked for installment purchase
            │
            ▼
  [ Active Agreements ] ────────► Ongoing monthly repayments
            │
            ▼
  [ Agreement Completion ] ─────► Credit Profile score upgraded; eligible for repeat purchases
```

### 2.2 Customer Credit Profile & Multiple Agreements
A customer must **NEVER** be restricted to a single agreement. The platform supports multiple concurrent and sequential agreements:
```text
Customer: Muhammad Usman (CNIC: 35201-1234567-1)
  │
  ├── Credit Profile: Score 85/100 (Tier: Low Risk, Maximum Financed Limit: PKR 400,000)
  │
  ├── Agreement #AGR-2025-001 (Samsung 55" LED TV) ──► Status: Completed (12/12 Paid on Time)
  ├── Agreement #AGR-2026-015 (Haier Inverter AC) ────► Status: Active (5/12 Paid, 0 Overdue)
  └── Agreement #AGR-2026-042 (iPhone 16 Pro) ────────► Status: Active (1/6 Paid, 0 Overdue)
```
A customer's past repayment behavior directly influences future credit assessments. If a customer has a history of severe delinquency or late-fee defaults, their Customer Credit Profile automatically updates their status to `restricted` or `blacklisted`.

---

## 3. Installment Agreement Anatomy & Pricing Engine

### 3.1 Core Agreement Entity Breakdown
The `InstallmentAgreement` binds:
- Customer ID & Guarantor ID(s)
- Company ID & Originating Branch ID
- Assigned Salesperson / Officer ID
- Product ID & Specific Serialized Item (IMEI / Serial Number)
- Financing terms: Original Cash Price, Negotiated Price, Down Payment, Financed Amount, Markup %, Tenure (months), Installment Frequency (Monthly / Weekly).
- Status lifecycle: `draft` &rarr; `under_review` &rarr; `approved` &rarr; `disbursed` &rarr; `active` &rarr; `completed` &rarr; `defaulted` &rarr; `cancelled`.

### 3.2 Pricing Calculation Audit Trail
To protect company margins and prevent unauthorized discounts, the pricing engine enforces a three-stage audit trail:
1. **Original Standard Calculation**: System-computed based on standard product catalog markup rules.
2. **Negotiated Calculation**: Adjustments proposed by sales officer (discounted markup, increased down payment).
3. **Final Approved Agreement**: The formal agreement authorized by the Branch Manager.

```text
========================================================================
PRICING CALCULATION AUDIT TRAIL (AGREEMENT #AGR-2026-089)
========================================================================
Product: Samsung Galaxy S25 Ultra 512GB (IMEI: 354892019284710)
Base Cash Price:            PKR 350,000

Stage 1: Standard Calculation (12-Month Standard Plan @ 25% Markup)
- Total Standard Markup:    PKR  87,500
- Standard Down Payment:    PKR  70,000 (20%)
- Standard Financed Price:  PKR 437,500
- Standard Monthly Pmt:     PKR  30,625 / month

Stage 2: Negotiated Calculation (Sales Officer: Tariq Manager)
- Negotiated Down Payment:  PKR 100,000 (+PKR 30,000 cash upfront)
- Negotiated Markup Rate:   20% (-5% discount granted)
- Negotiated Total Markup:  PKR  50,000
- Justification Note:       "Customer is repeat VIP with 100% on-time record."

Stage 3: Managerial Approval (Approved by: Ajmal Admin at 2026-09-11 14:30)
- Final Down Payment:       PKR 100,000 (Receipt #DP-8921)
- Final Financed Principal: PKR 250,000
- Total Markup Financed:    PKR  50,000
- Total Contract Amount:    PKR 300,000
- Final Monthly Pmt:        PKR  25,000 / month (12 Installments)
========================================================================
```

---

## 4. Payment Engine & Acknowledgement Lifecycle

### 4.1 Payment Allocation Hierarchy
Payments received are applied mathematically in strict order:
1. **Unpaid Late Fees & Penalties** (unless formally waived).
2. **Earliest Overdue Installments** (Principal & Markup).
3. **Current Due Installment**.
4. **Advance Installments** (applied towards future scheduled months).

### 4.2 Partial & Advance Payment Handling
- If scheduled installment is PKR 10,000 and customer pays PKR 4,000:
  - Installment status remains `partially_paid`.
  - Schedule line reflects: `paid_amount = 4000`, `remaining_amount = 6000`.
- If customer pays PKR 25,000 against a PKR 10,000 installment:
  - Current installment is marked `paid` (PKR 10,000).
  - Next month's installment is marked `paid` (PKR 10,000).
  - Subsequent month receives PKR 5,000 advance allocation (`partially_paid`).

### 4.3 Payment Acknowledgement (Method vs Status)
To maintain dual custody and prevent theft by field collectors:
```text
  [ Collection Officer Collects PKR 10,000 Cash from Customer ]
                            │
                            ▼
  [ Officer Submits Payment Record ] ───► Status: submitted (Pending Verification)
                            │              Customer receives provisional SMS receipt
                            ▼
  [ Cash Deposited at Branch Office ]
                            │
                            ▼
  [ Branch Cashier / Accountant Acknowledges Receipt ] ──► Status: acknowledged
                                                           Official Receipt Generated
                                                           Installment Schedule Updated
                                                           Branch Cash Drawer Debited
```

---

## 5. Serialized Inventory Management

- **Mandatory IMEI / Serial Capture**: For mobile devices, appliances, and high-value items, the specific IMEI 1, IMEI 2, or Serial Number must be scanned or entered upon stock arrival.
- **Unique Status per Unit**:
  ```text
  IMEI: 359182019284711 ──► Status: in_stock ──► Showroom Display
  IMEI: 359182019284712 ──► Status: reserved ──► Agreement #AGR-089 (Awaiting DP)
  IMEI: 359182019284713 ──► Status: disbursed ─► Delivered to Customer Usman
  IMEI: 359182019284714 ──► Status: repossessed ─► Recovered upon default
  ```
- **Disbursement Guard**: A serialized item cannot be released from showroom custody until the agreement is in `approved` status and the minimum required down payment receipt is confirmed.

---

## 6. Collection Officer Workflow & Mobile Operations

1. **Daily Route Assignment**: Collection Officer views today's assigned customers, expected recovery targets, and prioritized overdue accounts.
2. **Customer Field Visit**:
   - Locates customer residence / workplace.
   - Records customer contact status (Met Customer, Met Guarantor, Customer Not Available, Residence Locked).
   - In case of non-payment: Records customer reason and captures **Promise-to-Pay (PTP) Date**.
3. **Cash Collection & Provisional Receipt**:
   - Collects cash installment and submits payment entry.
   - System instantly fires an automated provisional SMS / WhatsApp acknowledgment to the customer.
4. **End-of-Day Reconciliation**:
   - Collector submits physical cash bundle to Branch Cashier.
   - Cashier verifies cash total against collector's submitted entries and executes bulk acknowledgement.

---

## 7. Late Payment & Waiver Audit Specifications

### 7.1 Configurable Late Fee Parameters
Late payment parameters are configured per company:
- `grace_period_days`: Number of days past due date before penalty begins (e.g. 5 days).
- `fee_type`: `fixed` (e.g. PKR 500 per installment) or `percentage` (e.g. 2% per month) or `daily_penalty` (PKR 50 / day).
- `max_penalty_cap`: Maximum late fee accrual per installment (e.g. max PKR 2,000).

### 7.2 Immutable Waiver Audit Trail
Late fees can only be reduced or waived by authorized supervisory roles (`branch_manager`, `company_admin`). Every waiver records:
- Agreement ID and Schedule ID
- Original accrued late fee amount
- Waived amount (partial or full)
- Authorizing User ID and timestamp
- Mandatory justification text (e.g., *"Medical emergency documentation verified"*).

---

## 8. Default & Recovery Escalation Workflow

*Note: Time thresholds are configurable at company level; the following represents the standard baseline escalation pattern:*

```text
  [ Day 1 After Due Date ] ────────► Grace Period Active (Friendly SMS Reminder)
             │
             ▼
  [ Day 6: Due Date + Grace ] ─────► Late Fee Accrued; Account Marked Overdue
             │
             ▼
  [ Day 15 Overdue ] ──────────────► Formal Overdue Notice via WhatsApp/SMS; Call by Tele-collector
             │
             ▼
  [ Day 30 Overdue ] ──────────────► Account Flagged Red; Field Collection Officer Assigned
             │
             ▼
  [ Day 60 Overdue ] ──────────────► Legal Notice issued to Customer & Guarantors; Guarantor Contacted
             │
             ▼
  [ Day 90+ Overdue ] ─────────────► Repossession Order Authorized; Product Recovered or Written Off
```

---

## 9. Legal Document Architecture

The document generation engine (`DocumentService`) produces standardized, print-ready PDF documents:
1. **Customer Application Form**: Comprehensive biographical, residential, and employment dossier.
2. **Customer Verification Report**: Field verification notes, utility bill check, and investigator signature.
3. **Credit Assessment & Approval Sheet**: Income evaluation, risk scoring, and approval committee sign-offs.
4. **Legal Installment Agreement / Contract**: Legally enforceable terms, schedule breakdown, repossession covenants, and legal stamp paper formatting.
5. **Guarantor Undertaking & Affidavit**: Binding legal guarantee and promissory statement signed by guarantors.
6. **Delivery & Handover Note**: Confirmation of serialized product receipt (IMEI/Serial) signed by customer.
7. **Installment Payment Receipt**: 58mm / 80mm thermal receipt and full-page A4 payment acknowledgment.
8. **Customer Statement of Account**: Complete transaction ledger showing total billed, total paid, and balance.
9. **Settlement Letter**: Formal early settlement calculation showing rebate on unearned markup.
10. **Clearance Certificate & NOC**: Formal No Objection Certificate releasing all claims upon agreement completion.

---

## 10. SaaS Subscription Architecture & Plan Tiers

Platform Administrators configure subscription plans to commercialize the platform across retail tenants:

| Plan Tier | Max Users | Max Branches | Max Active Agreements | Serialized Inventory | SMS & WhatsApp Alerts | Monthly Quota |
| :--- | :---: | :---: | :---: | :---: | :---: | :--- |
| **Starter** | 3 | 1 | 100 | Basic | Email Only | Small Single-Shop Retailers |
| **Growth** | 10 | 3 | 500 | Full IMEI/Serial | SMS Gateway Included | Growing Electronics Outlets |
| **Professional** | 25 | 8 | 2,500 | Full IMEI/Serial | SMS + WhatsApp API | Multi-Branch City Showrooms |
| **Enterprise** | Unlimited | Unlimited | Unlimited | Full IMEI/Serial | Dedicated Gateway | Regional Appliance Chains |
