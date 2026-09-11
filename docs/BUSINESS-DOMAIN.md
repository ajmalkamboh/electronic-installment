# Business Domain Specification & Workflow

## 1. Domain Entities & Responsibilities

| Entity | Domain Scope | Primary Responsibilities | Key Relationships |
| :--- | :--- | :--- | :--- |
| **Platform** | Global | Central SaaS operator managing tenants, subscription tiers, and global system health. | Has many Companies. |
| **Company (Tenant)** | Tenant | Business enterprise selling electronics/appliances via installment plans. | Has many Branches, Users, Customers, Agreements, Inventory. |
| **Branch** | Operational | Physical store/showroom where stock is held, items are disbursed, and cash is collected. | Belongs to Company; has many Users, Branch Inventory, Collections. |
| **User & Role** | Security | Staff member (Company Owner, Branch Manager, Credit Officer, Collection Officer, Accountant). | Belongs to Company and Branch; has assigned roles. |
| **Customer** | Shared | Individual purchasing products on installment terms. Identified by CNIC/National ID. | Belongs to Company; has many Guarantors, Credit Records, Agreements. |
| **Guarantor & Reference**| Shared | Secondary parties guaranteeing the installment agreement; verified before approval. | Belongs to Customer and Agreement. |
| **Credit Assessment** | Risk | Underwriting record verifying income, utility bills, residence, and guarantor legitimacy. | Belongs to Customer; evaluated by Credit Officer. |
| **Product & Category** | Shared | Electronic/appliance items (Smartphones, TVs, Refrigerators, ACs, Solar Systems). | Belongs to Company; categorized; has many Serialized Items. |
| **Serialized Item** | Branch | Distinct physical unit tracked by IMEI, Serial Number, or Asset Tag. | Belongs to Product and Branch; allocated to Agreement upon approval. |
| **Installment Plan** | Configurable| Template defining tenure (3, 6, 12, 18, 24 months), markup %, down payment %, and fee rules. | Belongs to Company; selected during agreement creation. |
| **Installment Agreement**| Operational | Legally binding contract between Tenant and Customer detailing financing terms. | Belongs to Company, Customer, and Branch; has many Schedules. |
| **Payment Schedule** | Financial | Chronological breakdown of individual monthly/weekly installments with due dates. | Belongs to Installment Agreement; has many Payments. |
| **Installment Payment** | Financial | Immutable cash or bank receipt recording payment against scheduled installments. | Belongs to Schedule and Agreement; recorded by Collection Officer. |
| **Receipt** | Financial | Sequentially numbered audit token issued to customer upon receipt of down payment or installment. | Belongs to Payment. |
| **Late Fee & Grace Period**| Financial | Automatic surcharge applied when installment exceeds grace period without waiver. | Belongs to Schedule; requires supervisor approval to waive. |
| **Audit Log** | Security | Tamper-evident trail of all status transitions, financial reversals, and administrative actions. | Polymorphic; belongs to Company and triggering User. |

---

## 2. End-to-End Operational Business Workflow

```text
  [ Customer Registration ]
             │
             ▼
  [ Customer Verification ] ── (CNIC, Address, Utility Bills)
             │
             ▼
[ Guarantor & Reference Check ] ── (Guarantor Verification & Neighborhood Inquiries)
             │
             ▼
  [ Credit Risk Assessment ] ── (Evaluation by Credit Officer)
             │
             ▼
    [ Credit Approval ] ────── (Authorized by Branch Manager / Company Admin)
             │
             ▼
    [ Product Selection ] ──── (Selection of specific Model & Color)
             │
             ▼
  [ Installment Calculation ] ─ (Tenure, Fixed/Percentage Markup, Down Payment %)
             │
             ▼
 [ Agreement Generation ] ──── (Contract Signing & Terms Acceptance)
             │
             ▼
    [ Down Payment Receipt ] ── (Cashier records initial down payment & generates Receipt)
             │
             ▼
   [ Product Allocation ] ──── (Specific IMEI / Serial Number disbursed from Branch Stock)
             │
             ▼
 [ Payment Schedule Active ] ── (Monthly/Weekly Due Dates established)
             │
             ▼
  [ Installment Collection ] ── (Over-the-counter or Field Collection Officer)
             │
             ▼
 [ Instant Payment Receipt ] ── (Digital SMS/WhatsApp + Printed Thermal Receipt)
             │
             ▼
    [ Ledger & Audit Update ] ─ (Immutable financial entry reducing outstanding balance)
             │
             ▼
  [ Agreement Completion ] ─── (NOC issued, item ownership transferred, ledger closed)
```

---

## 3. Financial Architecture & Audit Principles

### 3.1 Immutability of Financial Transactions
Financial ledger entries, installment payments, and cash receipts must **NEVER** be destructively updated or deleted. If a cashier records an incorrect amount or mistypes a receipt:
1. The original transaction remains permanently recorded in the ledger.
2. A compensating **Reversal / Adjustment Transaction** is generated, referencing the original receipt ID with explicit supervisor authorization.
3. The corrected payment is recorded as a new transaction.
4. An immutable audit trail links the original, reversal, and corrected entries.

### 3.2 Installment Calculation Engine
The installment engine supports:
- **Cash Sale Price**: Base price of the product without financing.
- **Financed Amount**: Cash Price minus Down Payment.
- **Markup Models**:
  - *Fixed Markup Amount*: Added directly to the financed principal.
  - *Percentage Annualized / Flat Markup*: Computed over agreed tenure.
- **Total Payable**: Down Payment + Financed Principal + Total Markup.
- **Monthly Installment**: (Financed Principal + Total Markup) / Number of Installments.
- **Payment Allocation Hierarchy**:
  1. Outstanding Late Fees (unless explicitly waived).
  2. Earliest Overdue Installment Principal & Markup.
  3. Current Due Installment.
  4. Advance Payment towards future installments.

---

## 4. Inventory & Serialized Asset Management

- **Showroom Tracking**: Inventory is tracked at the branch level.
- **Strict Serialization**: High-value electronics (smartphones, laptops, motorbikes, appliances) require mandatory IMEI or Serial Number entry upon receipt.
- **Asset Allocation**: A serialized item cannot be allocated to more than one agreement. Once an installment agreement is approved and down payment received, the IMEI transitions from `in_stock` to `allocated_to_agreement`.
- **Default Repossession**: In case of default and legal recovery, the serialized item transitions to `repossessed` status and undergoes condition inspection.

---

## 5. Multi-Channel Notifications

- **Payment Due Reminder**: Dispatched 3 days and 1 day prior to due date (SMS & WhatsApp).
- **Payment Received Confirmation**: Instant real-time SMS and WhatsApp receipt with remaining balance and transaction token.
- **Overdue Notice**: Dispatched 1 day after grace period expiration with accrued late fee details.
- **Channels Supported**: SMS (Local Pakistani Gateways), WhatsApp Business API, Email, and In-App Notifications.
