# Architecture Decision Records (ADR)

## ADR 001: Multi-Tenancy Strategy — Single Database with Scoped Tenancy

### Status: Accepted
### Context
The platform is an installment SaaS for Pakistani SME retailers selling electronics and home appliances. Tenants range from single-outlet shops to regional businesses with 2 to 10 branches. We evaluated:
- **Option A**: Single database with `company_id` column and Eloquent global scopes.
- **Option B**: Multi-database (separate database per company).
- **Option C**: Third-party tenancy packages (e.g. Stancl/Tenancy).

### Decision
Adopt **Option A (Single Database with Scoped Tenancy)** using Laravel-native global scopes (`CompanyScope`) and a dedicated `TenantContext` service.
- **Rationale**:
  - Pakistani SME retailers have moderate transaction volumes (hundreds to thousands of agreements per year).
  - Running database migrations across hundreds of tenant databases introduces substantial operational risk, deployment latency, and complex disaster recovery.
  - Single database allows instantaneous schema updates, unified cross-tenant platform reporting for SaaS admins, and simplified backup procedures.
  - Application security is enforced strictly via `CompanyScope` and `TenantMiddleware`, ensuring that tenant isolation is guaranteed at the SQL builder level.

---

## ADR 002: Identifier Strategy — BIGINT Primary Key + ULID Public Token

### Status: Accepted
### Context
Financial agreements and invoices require publicly accessible identifiers (URLs, API endpoints, QR codes, receipts). Exposing auto-incrementing integer IDs exposes business volume to competitors (enumeration attacks). Conversely, using pure UUIDv4 as primary keys creates severe MySQL B-Tree fragmentation and slow indexing performance.

### Decision
Adopt a **Dual-Key Strategy**:
- `id`: `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY` for internal relational foreign keys, joins, and indexing speed.
- `ulid`: `CHAR(26) UNIQUE INDEX` (Universally Unique Lexicographically Sortable Identifier) for public references, API routes, URLs, and external receipts.
- **Rationale**:
  - ULIDs are 128-bit identifiers, sortable by timestamp, collision-resistant, URL-safe (Crockford's Base32), and eliminate B-Tree fragmentation in MySQL.

---

## ADR 003: Frontend Architecture — Blade + Livewire 3 + AppDashboard Pro

### Status: Accepted
### Context
The application must feel like a commercial SaaS product with responsive navigation, dark/light theme support, accessible tables, and rich dashboards. We evaluated SPAs (React, Vue, Inertia) vs Server-Side Blade + Livewire.

### Decision
Use **Laravel Blade + Livewire 3 + Bootstrap 5.3.8 (AppDashboard Pro)** without Vue, React, Inertia, or Tailwind.
- **Rationale**:
  - The purchased AppDashboard Pro template is built on Bootstrap 5.3.8 and Vanilla JavaScript with rich components, charts, and datatables.
  - Livewire 3 gives reactive, dynamic form interactions (e.g., real-time installment calculations, CNIC lookups, and branch switches) directly in PHP without duplicating validation and domain logic in JavaScript.
  - Eliminates node_modules build overhead and complex SPA state synchronization.

---

## ADR 004: Branch Hierarchy & Shared Customer Model

### Status: Accepted
### Context
In installment businesses, a customer might register at Branch A, but pay an installment at Branch B or request a new product agreement at Branch C. If customers are siloed strictly by branch, duplicate accounts and credit risks proliferate.

### Decision
Classify entities into strict hierarchy tiers:
- **Customers, Guarantors, Credit Records, Product Definitions**: Scoped at the **Company** level (shared across branches).
- **Physical Inventory, Serialized Items (IMEI), Cash Drawers, Staff**: Scoped at the **Branch** level.
- **Installment Agreements**: Owned by the issuing Branch, but payable and viewable at any Branch within the same Company.

---

## ADR 005: Financial Immutability & Correction Pattern

### Status: Accepted
### Context
Financial installment platforms handle cash disbursements, down payments, and recurring collections. Mistakes by cashiers occur periodically.

### Decision
Prohibit destructive updates (`UPDATE payments SET amount = ...`) and hard deletes on all financial tables.
- All financial records are immutable.
- Corrections are executed via compensating **Reversal / Adjustment Transactions** linked to the original receipt and authorized by supervisory roles.
