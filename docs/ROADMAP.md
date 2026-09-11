# Implementation Roadmap: Electronic Installment SaaS

## Roadmap Overview

The Electronic Installment SaaS is implemented sequentially through ten production-grade phases. Each phase is independently verifiable, adheres strictly to the architectural standards established in Phase 01, and avoids unapproved technical detours.

---

### Phase 01: Foundation, Repository Setup & Architecture (COMPLETED)
- [x] Inspect local WAMP environment, PHP 8.3+, MySQL 8.4+, and AppDashboard Pro template.
- [x] Initialize Git repository with main branch and link to GitHub remote origin.
- [x] Initialize clean Laravel 13 framework with Livewire 3 and Bootstrap 5.3.8.
- [x] Establish MySQL database foundation with InnoDB, UTF-8 MB4, and `Asia/Karachi` timezone.
- [x] Build multi-tenant architecture (`CompanyScope`, `TenantContext`, `BelongsToCompany` trait).
- [x] Build branch hierarchy and seed baseline company, branches, and authorized roles.
- [x] Integrate AppDashboard layout, header context, sidebar navigation, and theme toggle.
- [x] Implement rate-limited authentication, user status checks, and security middleware.
- [x] Create authentic dashboard with real zero-state database metrics and no fake data.
- [x] Produce comprehensive architecture, business domain, ADR, and roadmap documentation.

---

### Phase 02: Customer Management & Guarantor Verification
- [ ] Implement Customer model (CNIC, Bio, Photos, Residence type, Utility bill verification).
- [ ] Implement Guarantor and Personal Reference relationships (minimum 2 guarantors).
- [ ] Implement Livewire Customer Registration wizard with instant CNIC duplicate check.
- [ ] Implement Customer Credit Profile and verification workflow (Pending -> Verified -> Blacklisted).
- [ ] Document verification checklist and automated risk score calculation.

---

### Phase 03: Product Catalog & Serialized Inventory (IMEI / Serial Numbers)
- [ ] Product Categories (Smartphones, LED TVs, Refrigerators, ACs, Solar, Motorcycles).
- [ ] Brand and Model master catalog.
- [ ] Serialized Item Tracking: Mandatory IMEI 1, IMEI 2, or Serial Number capture on stock arrival.
- [ ] Branch Stock allocation and Showroom inventory status (`in_stock`, `reserved`, `allocated`, `repossessed`).
- [ ] Stock transfer workflow between branches with dispatch and receipt acknowledgment.

---

### Phase 04: Installment Calculation Engine & Agreement Generator
- [ ] Calculation Engine Service (independent from UI controllers):
  - Fixed markup amount, flat annual percentage, and customizable down payment %.
  - Flexible frequencies: Monthly, Bi-weekly, Weekly installment plans.
- [ ] Agreement Model and Payment Schedule Generator:
  - Generates exact due dates, schedule breakdown, and contract terms.
- [ ] Contract Printing: Formatted printable legal installment agreement and affidavit.
- [ ] Specific Serialized Item (IMEI) allocation to agreement.

---

### Phase 05: Payment Collection, Cash Drawer & Receipts
- [ ] Point-of-Sale Installment Collection screen (search customer by CNIC, Mobile, or Agreement #).
- [ ] Automatic payment allocation engine:
  1. Late Fees & Penalties.
  2. Overdue Installments.
  3. Current Due Installment.
  4. Advance Installments.
- [ ] Cash Drawer / Till management (Daily open/close reconciliation per cashier).
- [ ] Instant Thermal Receipt printing (58mm / 80mm format) and digital receipt token generation.

---

### Phase 06: Recovery, Late Fees & Defaulter Management
- [ ] Automated Late Fee Calculation engine triggered via Laravel Scheduler.
- [ ] Grace period configuration (configurable at company and agreement level).
- [ ] Supervisor Late Fee Waiver authorization flow with audit trail.
- [ ] Field Collection Officer mobile interface for recording on-site visits and recovery notes.
- [ ] Customer Defaulter aging buckets: 1–30 days, 31–60 days, 61–90 days, 90+ days.

---

### Phase 07: Financial Ledger & Accounting Audits
- [ ] Double-entry style internal installment ledger:
  - Cash Account, Financed Accounts Receivable, Earned Markup, Unearned Markup, Late Fee Income.
- [ ] Strict Financial Immutability: No hard updates or deletes.
- [ ] Reversal / Adjustment workflow with required managerial authorization and audit trail.
- [ ] Daily Collection Summary and Cash-to-Bank deposit reconciliation.

---

### Phase 08: Multi-Branch & Consolidated Reporting
- [ ] Real-time Branch Comparison Dashboard (Sales volume, Recovery %, Overdue ratio).
- [ ] Customer Ledger & Statement of Account exportable to PDF and Excel.
- [ ] Inventory Movement & Aging Report (tracking slow-moving serialized items).
- [ ] Collection Officer performance & recovery commission calculation.

---

### Phase 09: WhatsApp, SMS & Notification Engine
- [ ] Integration with Pakistani SMS Gateways (e.g. Branded SMS) and WhatsApp Business API.
- [ ] Scheduled payment reminders (3 days before due date, on due date).
- [ ] Instant SMS / WhatsApp receipt upon payment acknowledgment.
- [ ] Automated overdue alerts with branch contact information.

---

### Phase 10: SaaS Subscription & Platform Administration
- [ ] Platform Super Admin command center for managing multiple tenant companies.
- [ ] Subscription Plans (Starter, Professional, Enterprise) with branch and agreement limits.
- [ ] Tenant Billing, Trial Periods, and Automated Suspension for non-payment.
- [ ] Platform Health Monitoring, Queue telemetry, and Database backup automation.
