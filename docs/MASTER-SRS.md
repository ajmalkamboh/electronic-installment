# Master Software Requirements Specification (SRS)

## 1. Introduction

### 1.1 Purpose & Scope
This Master Software Requirements Specification (SRS) defines the complete functional and non-functional requirements for the **Electronic Installment SaaS** platform. The system is an enterprise-grade, multi-tenant software-as-a-service application designed for retail enterprises that sell consumer electronics, mobile devices, solar energy systems, and home appliances through flexible installment-based agreements.

### 1.2 Target Market & Operating Context
- **Primary Market**: Pakistani SME retailers and regional appliance chains operating 1 to 10+ showrooms.
- **National Regulatory & Identification Context**: Uses Pakistani Computerized National Identity Card (CNIC, 13 digits: `XXXXX-XXXXXXX-X`), local mobile phone numbers (`+92-3XX-XXXXXXX`), and base currency in Pakistani Rupee (PKR).
- **Core Business Challenge**: Mitigating high default rates in unsecured consumer financing through rigorous customer identity verification, guarantor vetting, credit scoring, strict serialized asset tracking (IMEI/Serial), automated payment allocation, and auditable debt recovery workflows.

---

## 2. System Actors & User Personas

| Actor / Persona | Hierarchy Level | Primary System Responsibilities |
| :--- | :--- | :--- |
| **Platform Super Admin** | Global | Manages multi-tenant company onboarding, SaaS subscription tiers, billing cycles, quota enforcement, and system-wide health monitoring. |
| **Company Owner** | Tenant | Root tenant administrator; full executive control over company profile, subscription, all branches, financial audits, and organizational policies. |
| **Company Admin** | Tenant | Operations manager; oversees cross-branch inventory, company-wide customer databases, pricing templates, and senior employee assignments. |
| **Branch Manager** | Branch | Operational supervisor of a physical showroom; reviews credit assessments, authorizes agreements, approves late-fee waivers, and reconciles daily cashier cash drawers. |
| **Credit Assessment Officer**| Branch / Shared | Underwriting specialist; investigates customer applications, conducts physical residence visits, interviews personal guarantors, and calculates credit risk scores. |
| **Sales Officer / Cashier** | Branch | Customer-facing staff; processes customer intake, drafts agreements, accepts down payments, collects over-the-counter monthly installments, and issues thermal receipts. |
| **Field Collection Officer** | Branch | Mobile field agent; visits delinquent customers at residential or workplace addresses, captures promises-to-pay (PTP), collects field cash, and submits collection batches. |
| **Auditor / Accountant** | Tenant / Branch | Financial controller; audits payment ledgers, verifies cashier drawer acknowledgements, authorizes payment adjustments/reversals, and generates tax reports. |
| **Customer** | External | Consumer purchasing electronics on installment; receives SMS/WhatsApp receipts, payment due reminders, and account statements. |

---

## 3. Functional Requirements (Mapped to 18-Phase Architecture)

### 3.1 Phase 01: Repository + AppDashboard + Architecture Foundation
- **FR-01.1**: Clean Laravel 13 framework running on PHP 8.3+ and MySQL 8.4+ InnoDB with `Asia/Karachi` timezone and PKR currency defaults.
- **FR-01.2**: AppDashboard Pro Bootstrap 5.3.8 integration preserving full visual fidelity, responsive drawer navigation, dark/light theme persistence, and zero SPA framework dependencies.

### 3.2 Phase 02: Master SRS + Business Rules + Database Architecture
- **FR-02.1**: Master documentation suite covering detailed SRS, business rules catalog, 34-entity database schema, decision registers, and 18-phase implementation roadmap.
- **FR-02.2**: Formal data-layer entity-scope matrix establishing explicit tenant ownership (`company_id`) and branch ownership (`branch_id`).

### 3.3 Phase 03: SaaS Tenant + Company + Branch Foundation
- **FR-03.1**: Tenant company onboarding with legal name, NTN/STRN tax registration, corporate contact details, and status (`active`, `suspended`, `trial`, `cancelled`).
- **FR-03.2**: Multi-branch management enabling companies to establish physical showrooms with distinct branch codes, city, physical address, and designated headquarters branch.
- **FR-03.3**: Server-side tenant isolation enforced via `CompanyScope` GlobalScope, `TenantContext` service singleton, and `BelongsToCompany` trait, with absolute zero-trust on user-submitted tenant parameters.

### 3.4 Phase 04: Users + Roles + Permissions + Employee Structure
- **FR-04.1**: Granular Role-Based Access Control (RBAC) supporting predefined and custom tenant roles.
- **FR-04.2**: User lifecycle management with security status validation (`active`, `suspended`, `inactive`). Suspended accounts are barred from logging in and instantly ejected mid-session.
- **FR-04.3**: Employee assignment linking staff members to a primary branch while allowing cross-branch operational authorization for supervisory roles.
- **FR-04.4**: Security auditing tracking `last_login_at`, `last_login_ip`, and authentication rate limiting (`5 attempts / minute`).

### 3.5 Phase 05: Customer + Guarantor + Reference + Verification
- **FR-05.1**: Customer registration capturing 13-digit Pakistani CNIC (`XXXXX-XXXXXXX-X`), biographical details, primary mobile, WhatsApp number, residential address, residence ownership type (Owned / Rented), and utility bill reference numbers.
- **FR-05.2**: Company-wide unique CNIC enforcement preventing duplicate debtor profiles across branches.
- **FR-05.3**: Guarantor and personal reference profiling (minimum 1 to 2 verified guarantors per agreement) capturing CNIC, occupation, employer address, relationship to customer, and mobile numbers.
- **FR-05.4**: Physical verification workflow recording field investigation findings, residence confirmation, neighbor reputation checks, and document upload (CNIC scans, electricity bills, salary slips).

### 3.6 Phase 06: Credit Assessment + Credit Approval
- **FR-06.1**: Customer Credit Profile tracking dynamic credit score (0–100), repayment reliability index, total active debt across agreements, and historical days-past-due (DPD).
- **FR-06.2**: Automated risk scoring engine evaluating monthly disposable income against proposed installment obligations (Debt-to-Income cap of 40%).
- **FR-06.3**: Multi-tier credit approval workflow allowing Credit Officers to submit recommendations (`Approved`, `Conditional Approval`, `Rejected`) and Branch Managers / Company Admins to grant final authorization.
- **FR-06.4**: Blacklist management preventing new agreements for individuals flagged for prior write-offs, legal recovery, or guarantor defaults.

### 3.7 Phase 07: Products + Categories + Suppliers + Inventory
- **FR-07.1**: Hierarchical product catalog with categories, subcategories, brand, model name, and technical specifications.
- **FR-07.2**: Dual-mode inventory management supporting serialized tracking (IMEI 1, IMEI 2, Serial Number, Asset Tag) for high-value electronics and batch tracking for accessories.
- **FR-07.3**: Branch showroom stock tracking with real-time on-hand quantities.
- **FR-07.4**: Serialized item state machine managing status transitions: `in_stock` &rarr; `reserved` &rarr; `allocated_to_agreement` &rarr; `disbursed` &rarr; `repossessed`.
- **FR-07.5**: Inter-branch stock movement workflow with dispatch logging, in-transit status, and receiving branch acknowledgment.

### 3.8 Phase 08: Installment Plans + Pricing / Markup Engine
- **FR-08.1**: Configurable installment plan templates defining tenure (3, 6, 12, 18, 24 months), minimum down payment %, and markup rules.
- **FR-08.2**: Multi-formula calculation engine supporting:
  - *Fixed Markup*: Base Cash Price + Fixed Amount.
  - *Percentage Markup*: Base Cash Price + (Base Price &times; Flat Annual Rate &times; Tenure/12).
  - *Plan-Based Markup*: Predefined tiered rate schedules.
- **FR-08.3**: Three-tier pricing calculation audit trail capturing:
  1. Standard system calculation.
  2. Negotiated calculation proposed by salesperson.
  3. Final approved calculation authorized by manager with mandatory justification.

### 3.9 Phase 09: Installment Agreement / Contract
- **FR-09.1**: Centralized `InstallmentAgreement` entity linking Customer, Guarantors, Branch, Product, and specific Serialized Item (IMEI).
- **FR-09.2**: Multi-agreement capability allowing a verified customer to maintain multiple simultaneous or sequential agreements.
- **FR-09.3**: Agreement lifecycle state transitions: `draft` &rarr; `under_review` &rarr; `approved` &rarr; `disbursed` &rarr; `active` &rarr; `completed` &rarr; `defaulted` &rarr; `cancelled`.
- **FR-09.4**: Mandatory down payment enforcement: serialized item disbursement is locked until verified down payment receipt is confirmed in the cash drawer.

### 3.10 Phase 10: Payment Schedule + Payment Engine
- **FR-10.1**: Automated chronological payment schedule generator computing exact monthly or weekly installment due dates.
- **FR-10.2**: Mathematical payment allocation engine processing received funds strictly in priority:
  1. Unpaid Late Fees & Penalties.
  2. Earliest Overdue Installments.
  3. Current Due Installment.
  4. Advance Future Installments.
- **FR-10.3**: Partial payment tracking: an installment is marked `partially_paid` with exact remaining balance until fully satisfied. Advance payments mark future installments `paid` or `partially_paid`.
- **FR-10.4**: Separation of Payment Method (`cash`, `bank_transfer`, `cheque`, `raast`, `easypaisa`, `jazzcash`) from Payment Verification Status (`submitted`, `pending_verification`, `acknowledged`, `reversed`).

### 3.11 Phase 11: Collection Officer + Collection Workflow
- **FR-11.1**: Collection Officer daily dashboard displaying assigned delinquent accounts, expected collections, and geographic clusters.
- **FR-11.2**: Mobile field collection interface allowing officers to record customer visits, interaction status, captured promises-to-pay (PTP), and collected cash installments.
- **FR-11.3**: Dual-custody drawer reconciliation: field cash remains in `submitted` status until physical cash is deposited with the Branch Cashier and acknowledged.

### 3.12 Phase 12: Late Fee + Grace Period + Recovery
- **FR-12.1**: Automated daily console scheduler checking overdue installments against company-configured grace periods (e.g. 5 days).
- **FR-12.2**: Configurable late-fee calculation supporting fixed surcharge, monthly percentage penalty, or daily accrual, subject to maximum fee caps.
- **FR-12.3**: Late-fee waiver authorization flow requiring supervisory approval with mandatory reason logging and immutable audit trail.
- **FR-12.4**: Configurable multi-stage recovery escalation workflow: Reminder (Grace) &rarr; Telephonic Call &rarr; Field Officer Visit &rarr; Legal Notice &rarr; Repossession / Write-off.

### 3.13 Phase 13: Receipts + Documents + PDF Engine
- **FR-13.1**: Dual receipt generation supporting 58mm / 80mm thermal POS printing and full-page A4 receipts with QR code verification tokens.
- **FR-13.2**: Standard legal document generation:
  - Customer Application & Dossier
  - Legal Installment Contract formatted for Pakistani Stamp Paper
  - Guarantor Promissory Undertaking & Affidavit
  - Serialized Product Delivery & Handover Note
  - Customer Account Statement & Ledger
  - No Objection Certificate (NOC) & Debt Clearance Certificate.

### 3.14 Phase 14: Accounting / Financial Ledger
- **FR-14.1**: Append-only immutable financial transaction ledger recording every down payment, installment, late fee, and waiver.
- **FR-14.2**: Strict prohibition of hard updates and deletes on financial records.
- **FR-14.3**: Reversal / Adjustment transaction workflow generating compensating debit/credit lines linked to the original transaction ID with managerial authorization.
- **FR-14.4**: Daily cash drawer open/close reconciliation per cashier shift.

### 3.15 Phase 15: Reports + Dashboards
- **FR-15.1**: Real-time Branch Comparison Dashboard comparing sales volume, collection efficiency %, overdue aging ratios, and inventory turnover.
- **FR-15.2**: Defaulter aging analysis categorized into standard aging buckets (1–30 days, 31–60 days, 61–90 days, 90+ days).
- **FR-15.3**: Exportable financial statements (PDF and Excel) for customer ledgers, daily collections, and recovery officer performance.

### 3.16 Phase 16: SMS + Email + WhatsApp + Scheduler
- **FR-16.1**: Multi-channel notification dispatch engine supporting Pakistani SMS Gateways, WhatsApp Business Cloud API, and SMTP email.
- **FR-16.2**: Automated scheduled alerts for upcoming payment reminders (3 days and 1 day prior), payment received confirmation receipts, and overdue demand notices.

### 3.17 Phase 17: SaaS Subscription + Limits
- **FR-17.1**: Tiered SaaS plan management (Starter, Growth, Professional, Enterprise) defining quotas for max users, max branches, max active agreements, and monthly transactions.
- **FR-17.2**: Tenant middleware enforcing plan quota limits, trial expiration gates, and subscription suspension for non-payment.

### 3.18 Phase 18: Security + Audit + Performance + Production
- **FR-18.1**: Comprehensive tamper-evident audit logging for all critical state mutations, approvals, waivers, and reversals.
- **FR-18.2**: Production hardening: query optimization with composite indexes, database backups, and rate-limiting defense.

---

## 4. Non-Functional Requirements (NFR)

- **NFR-01 (Data Isolation)**: Company A data must be mathematically inaccessible to Company B at the SQL query layer via `CompanyScope`.
- **NFR-02 (Auditability & Immutability)**: Financial records must be 100% append-only. Zero hard deletes on financial, agreement, or audit tables.
- **NFR-03 (Performance & Latency)**: Dashboard and report page render times must not exceed 800ms under standard local WAMP/MySQL load.
- **NFR-04 (Concurrency)**: Support concurrent cashier receipt processing on identical branches without race conditions on sequential receipt numbers.
- **NFR-05 (Mobile Usability)**: All operational workflows (collection officer visit recording, cashier intake, customer search) must be fully responsive across mobile, tablet, and desktop viewports.
- **NFR-06 (Data Integrity)**: MySQL foreign keys must enforce relational integrity with explicit `RESTRICT` on financial records and `CASCADE` only on non-transactional metadata.
