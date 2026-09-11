# Comprehensive Business Rules Catalog

This document defines the formal, non-negotiable business rules and mathematical formulas governing the **Electronic Installment SaaS** platform.

---

## 1. Tenancy & Data Access Rules (BR-TEN)

- **BR-TEN-01 (Data Isolation)**: Every tenant-owned database table must include a `company_id` column. Queries must automatically scope by `company_id` via Eloquent Global Scope (`CompanyScope`).
- **BR-TEN-02 (Zero-Trust Input)**: The application must never trust `company_id` or `tenant_id` from user-submitted HTTP requests, forms, or query parameters. The active company ID must always be derived from the server-side `TenantContext`.
- **BR-TEN-03 (Account Status Guard)**: If a user account status is set to `suspended` or `inactive`, or if the parent company status is not `active`, all requests must immediately be terminated with an HTTP 403 or redirect to login.
- **BR-TEN-04 (Cross-Branch Visibility)**:
  - Customers, Guarantors, and Product Masters are **Shared** company-wide: any branch can view customer credit history and register new agreements.
  - Physical Inventory and Cash Drawers are **Branch-Specific**: cashiers can only disburse items currently in their branch's inventory and post cash to their active branch drawer.
  - Cross-Branch Payments: A customer may pay an installment at any branch within the same company. The payment records both the `issuing_branch_id` of the agreement and the `collecting_branch_id` where cash was physically handed over.

---

## 2. Customer & Identity Rules (BR-CUST)

- **BR-CUST-01 (CNIC Format & Uniqueness)**: Customer CNIC must strictly adhere to the 13-digit Pakistani format: `XXXXX-XXXXXXX-X`. CNIC must be globally unique within each tenant company.
- **BR-CUST-02 (Multi-Agreement Capability)**: A customer may hold multiple concurrent or sequential agreements, provided their aggregate monthly installment obligations do not exceed their authorized Debt-to-Income credit limit.
- **BR-CUST-03 (Customer Credit Profile Lifecycle)**:
  - Credit Score range: `0 to 100`.
  - New Customer default score: `50` (Standard Risk).
  - Score increases by `+2` for each consecutive on-time installment paid.
  - Score decreases by `-5` for installments paid within grace period, and `-15` for installments exceeding 30 days overdue.
  - Automatic Blacklisting: Any customer with an agreement reaching `defaulted` (90+ days overdue) or subject to a legal repossession order is automatically assigned `blacklisted` status, barring all future agreements across all branches.

---

## 3. Guarantor & Reference Rules (BR-GUAR)

- **BR-GUAR-01 (Guarantor Minimums)**: Every installment agreement requires a minimum of **1 verified guarantor** (2 recommended for financing exceeding PKR 150,000).
- **BR-GUAR-02 (Disqualification Criteria)**:
  - A customer cannot act as their own guarantor.
  - An individual who is currently listed as a debtor on an overdue agreement cannot act as a guarantor for another customer.
  - An individual cannot act as an active guarantor on more than **3 concurrent agreements** within the same company.
- **BR-GUAR-03 (Guarantor Verification)**: A guarantor's CNIC, physical residential address, employer name, and mobile number must be documented and physically or telephonically verified prior to credit approval.

---

## 4. Inventory & Serialization Rules (BR-INV)

- **BR-INV-01 (Mandatory Serialization)**: High-value consumer electronics (smartphones, tablets, laptops, motorcycles, LED TVs, refrigerators, air conditioners) require mandatory unique serialization (`imei_1`, optional `imei_2`, or `serial_number`) upon stock intake.
- **BR-INV-02 (Serialized State Machine)**:
  - `in_stock`: Physically present in showroom and available for sale.
  - `reserved`: Agreement approved; item temporarily held pending down payment confirmation (maximum hold: 48 hours).
  - `allocated`: Down payment received; item assigned to specific agreement.
  - `disbursed`: Physically handed over to customer with signed Delivery Handover Note.
  - `repossessed`: Recovered from customer following default; undergoes refurbishing/inspection.
- **BR-INV-03 (Single Allocation Constraint)**: An individual serialized item (unique IMEI/Serial) can never be allocated to more than one active agreement simultaneously.

---

## 5. Pricing & Markup Engine Rules (BR-PRIC)

### 5.1 Pricing Formulas
Let:
- $CP$ = Base Cash Price (Showroom cash purchase price)
- $DP$ = Down Payment amount paid upfront by customer
- $FA$ = Financed Principal Amount ($FA = CP - DP$)
- $T$ = Agreement Tenure in months ($3, 6, 12, 18, 24$)
- $M$ = Total Financed Markup (Profit margin)
- $TP$ = Total Agreement Contract Price ($TP = DP + FA + M$)
- $EMI$ = Monthly Installment Amount ($EMI = \frac{FA + M}{T}$)

#### Formula A: Percentage Annualized / Flat Markup
$$M = FA \times \left( \frac{\text{Annual Markup \%}}{100} \right) \times \left( \frac{T}{12} \right)$$

#### Formula B: Fixed Surcharge Markup
$$M = \text{Predefined Fixed Profit Amount}$$

#### Formula C: Tiered Plan-Based Markup
Predefined rate tables configured per tenure (e.g. 3 Months = 10%, 6 Months = 18%, 12 Months = 28%, 18 Months = 38%).

### 5.2 Down Payment Constraints
- **BR-PRIC-01 (Minimum Down Payment)**: Down payment must equal or exceed the minimum threshold defined in the company plan template (standard: minimum 20% of Cash Price, minimum 15% for VIP repeat customers).
- **BR-PRIC-02 (Disbursement Lock)**: The physical disbursement of a serialized item is mathematically locked until the cashier posts a verified down payment receipt equal to or greater than the agreed down payment amount.

### 5.3 Three-Stage Pricing Audit Trail (BR-PRIC-03)
Every agreement must store:
1. `standard_markup` & `standard_monthly_installment`: System calculation.
2. `negotiated_markup` & `negotiated_monthly_installment`: Value proposed by salesperson.
3. `final_markup` & `final_monthly_installment`: Approved value authorized by manager.
*Rule*: Any deviation between Standard and Final markup requires mandatory managerial authorization with recorded text justification.

---

## 6. Payment Allocation & Ledger Rules (BR-PMT)

### 6.1 Priority Allocation Hierarchy (BR-PMT-01)
When an installment payment $P$ is received, the allocation engine applies funds strictly in the following priority order:
1. **Tier 1: Unpaid Late Fees & Penalties**: Surcharges accrued on overdue installments.
2. **Tier 2: Earliest Overdue Installments**: Chronologically earliest unpaid installments (Principal + Markup).
3. **Tier 3: Current Due Installment**: Installment due in the current billing cycle.
4. **Tier 4: Advance Future Installments**: Applied towards subsequent future schedule dates.

### 6.2 Partial Payment Rules (BR-PMT-02)
- An installment schedule line has three status states: `unpaid`, `partially_paid`, `paid`.
- If $P < \text{Installment Amount Due}$, the schedule line is marked `partially_paid`, recording `paid_amount = P` and `remaining_amount = Due - P`.
- An installment is **NEVER** marked `paid` until $100\%$ of its scheduled amount is satisfied.

### 6.3 Advance Payment Rules (BR-PMT-03)
- If received payment exceeds current due amount, the excess is applied to the immediately following monthly installment schedule line.
- If customer pays off the entire remaining financed balance early, a **Rebate on Unearned Markup** may be calculated:
  $$\text{Rebate} = \text{Remaining Unearned Markup} \times \text{Company Early Settlement Rebate \%}$$

---

## 7. Payment Acknowledgement & Dual Custody Rules (BR-ACK)

- **BR-ACK-01 (Method vs Status Separation)**:
  - `Payment Method`: `cash`, `bank_transfer`, `cheque`, `raast`, `easypaisa`, `jazzcash`.
  - `Payment Status`: `submitted` &rarr; `pending_verification` &rarr; `acknowledged` &rarr; `reversed`.
- **BR-ACK-02 (Dual Custody on Field Collections)**:
  - Cash collected in the field by a Collection Officer is flagged `submitted`.
  - The customer receives an automated provisional SMS receipt.
  - Funds do not update the branch cash drawer until the collector physically hands over cash to the Branch Cashier and the cashier formally marks the payment `acknowledged`.
- **BR-ACK-03 (Bank Transfer Verification)**: Payments submitted via online bank transfer or Raast remain in `pending_verification` until the accountant verifies bank statement clearance.

---

## 8. Late Payment & Waiver Rules (BR-LATE)

- **BR-LATE-01 (Grace Period)**: Late fees do not accrue during the company-configured grace period (default: 5 calendar days following the installment due date).
- **BR-LATE-02 (Penalty Calculation Models)**:
  - *Fixed Surcharge*: Flat fee (e.g. PKR 500) applied once on Day 6.
  - *Daily Penalty*: Accrues daily (e.g. PKR 50/day) starting on Day 6 until payment is received.
  - *Monthly Percentage*: Flat percentage (e.g. 2%) applied on the overdue installment amount.
- **BR-LATE-03 (Maximum Fee Cap)**: Total accumulated late fee on any single installment must not exceed the configured maximum cap (default: PKR 2,000 or 25% of installment amount, whichever is lower).
- **BR-LATE-04 (Auditable Late Fee Waiver)**:
  - A cashier or collection officer cannot waive late fees.
  - Late fees may only be waived or discounted by an authorized `branch_manager` or `company_admin`.
  - Every waiver records: original fee, waived amount, approving user ID, timestamp, and mandatory reason.

---

## 9. Default Escalation & Recovery Rules (BR-REC)

- **BR-REC-01 (Configurable Escalation Timeline)**:
  - `1–5 Days Overdue`: Grace Period (Friendly automated SMS reminder).
  - `6–14 Days Overdue`: Stage 1 Overdue (Late fee applied; automated WhatsApp reminder).
  - `15–29 Days Overdue`: Stage 2 Delinquent (Tele-collection call; formal demand notice).
  - `30–59 Days Overdue`: Stage 3 Critical (Field Collection Officer assigned for residential visit).
  - `60–89 Days Overdue`: Stage 4 Pre-Legal (Final Legal Notice served to Customer and Guarantors).
  - `90+ Days Overdue`: Stage 5 Default / Legal (Account marked `defaulted`; repossession order issued; customer blacklisted).

---

## 10. Financial Immutability & Reversal Rules (BR-FIN)

- **BR-FIN-01 (Zero Hard Deletes)**: No financial transaction, payment, receipt, or schedule line may ever be deleted or directly overwritten.
- **BR-FIN-02 (Compensating Reversal Pattern)**:
  - If a payment was recorded erroneously (e.g. wrong amount, duplicate receipt), a compensating **Reversal Transaction** must be generated.
  - The reversal creates equal and opposite negative entries in the payment allocation and cash drawer tables.
  - Reversals require authorized supervisor approval and reference the original payment receipt ID.

---

## 11. SaaS Subscription & Quota Rules (BR-SAAS)

- **BR-SAAS-01 (Strict Quota Enforcement)**:
  - If a company reaches its plan tier limit for `max_users`, new user creation is blocked until the subscription is upgraded.
  - If a company reaches its limit for `max_branches`, branch creation is blocked.
  - If an account is past its subscription renewal date by $> 7$ days, the tenant status transitions to `suspended`, blocking non-administrative user access across all branches.
