# Master 18-Phase Implementation Roadmap: Electronic Installment SaaS

This document establishes the official 18-phase execution plan for the **Electronic Installment SaaS** platform. Each phase is implemented sequentially, thoroughly verified, committed to version control, and formally reviewed before proceeding to the next.

---

### Phase 01: Repository + AppDashboard + Architecture (COMPLETED)
- [x] Inspect local WAMP environment, PHP 8.3+, MySQL 8.4+, and AppDashboard Pro template.
- [x] Initialize Git repository with `main` branch and link to GitHub remote origin.
- [x] Initialize clean Laravel 13 framework with Livewire 3 and Bootstrap 5.3.8.
- [x] Establish MySQL database foundation with InnoDB, UTF-8 MB4, and `Asia/Karachi` timezone.
- [x] Build multi-tenant architecture (`CompanyScope`, `TenantContext`, `BelongsToCompany` trait).
- [x] Build branch hierarchy and seed baseline company, branches, and authorized roles.
- [x] Integrate AppDashboard layout, header context, sidebar navigation, and theme toggle.
- [x] Implement rate-limited authentication, user status checks, and security middleware.
- [x] Create authentic dashboard with real zero-state database metrics and no fake data.

---

### Phase 02: Master SRS + Business Rules + Database Architecture (COMPLETED)
- [x] Author comprehensive Master SRS (`docs/MASTER-SRS.md`) with functional requirements (FR-01 to FR-18) and non-functional requirements.
- [x] Author formal Business Rules Catalog (`docs/BUSINESS-RULES.md`) covering tenancy, customer eligibility, pricing formulas, payment allocation priority, payment submission vs acknowledgement, late-fee formulas, and recovery escalations.
- [x] Author Master Database Architecture (`docs/DATABASE-SCHEMA.md`) with complete 34-entity table specifications and Mermaid ERDs.
- [x] Resolve open business decisions in Decision Register (`docs/DECISION-REGISTER.md`).
- [x] Align master roadmap with the confirmed 18-phase structure.

---

### Phase 03: SaaS Tenant + Company + Branch Foundation (NEXT PHASE)
- [ ] Migrate full tenant configuration and company profile settings.
- [ ] Implement branch management CRUD (Create, Read, Update, Deactivate branch).
- [ ] Implement multi-branch context switcher in UI header with session persistence.
- [ ] Build tenant-aware Eloquent model scopes across all foundation models.
- [ ] Implement tenant onboarding wizard for new company registration.

---

### Phase 04: Users + Roles + Permissions + Employee Structure
- [ ] Implement comprehensive Role-Based Access Control (RBAC) with granular permissions.
- [ ] Implement employee management interface (Admin, Manager, Credit Officer, Cashier, Collector, Accountant).
- [ ] Implement branch staff assignment and transfer capabilities.
- [ ] Implement employee security controls (password policies, status toggling, audit trails).

---

### Phase 05: Customer + Guarantor + Reference + Verification
- [ ] Implement Customer model & migrations (13-digit Pakistani CNIC with duplicate guard).
- [ ] Implement Guarantor and Personal Reference relationships ($1..N$ guarantors per customer/agreement).
- [ ] Build reactive Livewire Customer Registration wizard with instant CNIC uniqueness check.
- [ ] Build Customer Verification dossier recording physical residence visits, utility bills, and neighborhood inquiries.
- [ ] Implement Customer Document upload manager (CNIC front/back, electricity bills, salary slips).

---

### Phase 06: Credit Assessment + Credit Approval
- [ ] Implement Customer Credit Profile and dynamic credit scoring engine (0–100 score).
- [ ] Implement Debt-to-Income (DTI) ratio calculator assessing customer monthly financial capacity.
- [ ] Build multi-tier Credit Approval workflow (Credit Officer recommendation &rarr; Branch Manager approval).
- [ ] Implement Blacklist management engine barring delinquent debtors across all branches.

---

### Phase 07: Products + Categories + Suppliers + Inventory
- [ ] Implement Product Category hierarchy and brand catalog.
- [ ] Implement Product Master with cash retail pricing and minimum down payment percentages.
- [ ] Implement Supplier management and wholesale purchase intake.
- [ ] Implement Branch Inventory aggregated quantity tracking.
- [ ] Implement Serialized Item tracking (mandatory IMEI 1, IMEI 2, Serial #, Asset Tag) with state machine (`in_stock` &rarr; `reserved` &rarr; `allocated` &rarr; `disbursed` &rarr; `repossessed`).
- [ ] Implement inter-branch stock movement and transfer workflow.

---

### Phase 08: Installment Plans + Pricing / Markup Engine
- [ ] Build Installment Plan template builder (tenure: 3, 6, 12, 18, 24 months; down payment %; markup rates).
- [ ] Implement Calculation Engine Service (Fixed markup, Percentage markup, Tiered plan markup).
- [ ] Build reactive Livewire installment quote calculator with real-time slider controls.
- [ ] Implement Three-Stage Pricing Audit Trail (Standard &rarr; Negotiated &rarr; Approved with mandatory justification text).

---

### Phase 09: Installment Agreement / Contract
- [ ] Implement core `InstallmentAgreement` entity and lifecycle state machine (`draft` &rarr; `approved` &rarr; `disbursed` &rarr; `active` &rarr; `completed` &rarr; `defaulted`).
- [ ] Implement multi-agreement support per customer.
- [ ] Build contract generation wizard binding Customer, Guarantors, Branch, Product, and specific Serialized Item.
- [ ] Implement mandatory down payment receipt verification lock prior to physical item disbursement.

---

### Phase 10: Payment Schedule + Payment Engine
- [ ] Implement Payment Schedule Generator computing exact chronological monthly/weekly due dates.
- [ ] Implement Priority Payment Allocation Engine (Late fees &rarr; Earliest overdue &rarr; Current due &rarr; Advance).
- [ ] Implement Partial payment and Advance payment calculations.
- [ ] Implement Payment Method vs Status separation (`submitted` &rarr; `pending_verification` &rarr; `acknowledged` &rarr; `reversed`).
- [ ] Implement Cashier Over-the-Counter Payment intake screen.

---

### Phase 11: Collection Officer + Collection Workflow
- [ ] Build Collection Officer daily operational dashboard (assigned accounts, recovery targets, route clusters).
- [ ] Build Mobile Field Collection interface (customer visit logging, promise-to-pay date capture, field cash submission).
- [ ] Implement Dual-Custody drawer reconciliation workflow (provisional receipt &rarr; branch cashier cash handover & acknowledgement).

---

### Phase 12: Late Fee + Grace Period + Recovery
- [ ] Implement automated Late Fee calculation scheduler running daily via cron.
- [ ] Implement company-configurable Grace Periods and fee penalty models (fixed, percentage, daily).
- [ ] Implement Managerial Late-Fee Waiver authorization workflow with audit trail.
- [ ] Implement Multi-Stage Recovery Escalation engine (Grace &rarr; Tele-call &rarr; Field visit &rarr; Legal notice &rarr; Repossession).

---

### Phase 13: Receipts + Documents + PDF Engine
- [ ] Implement POS Thermal Receipt generator (58mm / 80mm formats) with QR verification hash.
- [ ] Implement full-page A4 payment receipt.
- [ ] Build legal document PDF generator:
  - Customer Application Dossier
  - Legal Contract formatted for Pakistani Stamp Paper
  - Guarantor Undertaking & Affidavit
  - Product Delivery Handover Note
  - Customer Statement of Account
  - No Objection Certificate (NOC) & Debt Clearance Certificate.

---

### Phase 14: Accounting / Financial Ledger
- [ ] Implement append-only immutable financial ledger (`financial_ledger_entries`).
- [ ] Implement Reversal / Adjustment workflow with supervisor approval and linked transaction trail.
- [ ] Implement Daily Cash Drawer (Till) open/close reconciliation per cashier shift.
- [ ] Implement Cash-to-Bank deposit reconciliation.

---

### Phase 15: Reports + Dashboards
- [ ] Build executive Branch Comparison Dashboard (Sales volume, Recovery %, Overdue ratios, Stock turnover).
- [ ] Build Defaulter Aging Analysis report (1–30 days, 31–60 days, 61–90 days, 90+ days).
- [ ] Build Customer Account Ledger and statement export (PDF/Excel).
- [ ] Build Collection Officer recovery performance and commission reports.

---

### Phase 16: SMS + Email + WhatsApp + Scheduler
- [ ] Integrate Pakistani SMS Gateways and WhatsApp Business Cloud API.
- [ ] Implement scheduled automated payment due reminders (3 days before, on due date).
- [ ] Implement instant payment receipt dispatch via SMS and WhatsApp.
- [ ] Implement automated overdue demand notices.

---

### Phase 17: SaaS Subscription + Limits (COMPLETED)
- [x] Implement Platform Super Admin command center.
- [x] Implement SaaS Plan tiers (Starter, Growth, Professional, Enterprise) and feature toggles.
- [x] Implement Tenant quota enforcement middleware (users, branches, agreements, monthly transactions).
- [x] Implement Tenant billing lifecycle, trial management, and automated non-payment suspension.

---

### Phase 18: Security + Audit + Performance + Production (COMPLETED)
- [x] Implement comprehensive polymorphic audit logging across all entities (`audit_logs`, `Auditable` trait, diff tracking, immutability hooks).
- [x] Query optimization with composite database indexes and slow-query telemetry.
- [x] Automated database backup scheduler (`system:backup-database --clean-days=30`) with retention pruning and on-demand trigger.
- [x] Security audit, OWASP security headers middleware, production health diagnostics, and production deployment guide (`docs/PRODUCTION-DEPLOYMENT.md`).
