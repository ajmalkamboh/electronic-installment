# System Architecture Specification

## 1. System Technology Overview

- **Core Framework**: Laravel 13.x (PHP 8.3+)
- **Runtime Environment**: WAMP (Apache 2.4.65, PHP 8.3.28, MySQL 8.4.7 InnoDB)
- **Frontend Architecture**: Laravel Blade + Livewire 3+ + Bootstrap 5.3.8 (AppDashboard Pro)
- **Database Engine**: MySQL 8.4 InnoDB (`utf8mb4` charset, `utf8mb4_unicode_ci` collation)
- **Asynchronous Processing**: Laravel Queue (`database` driver), Laravel Scheduler
- **Notification Engine**: Laravel Notification System (Email, SMS, WhatsApp, In-App)
- **Timezone**: `Asia/Karachi` (PKT, UTC+5)
- **Base Currency**: PKR (Pakistani Rupee)

---

## 2. Application Directory Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php       # Rate-limited authentication, session handling & security auditing
│   │   └── DashboardController.php      # Authenticated tenant dashboard with authentic database KPIs
│   └── Middleware/
│       └── TenantMiddleware.php         # Multi-tenant membership, account status & company validation
├── Models/
│   ├── Branch.php                       # Physical showroom/outlet model with ULID & company scoping
│   ├── Company.php                      # Root tenant/organization model
│   └── User.php                         # Application user with role, status, company & branch assignment
├── Providers/
│   └── AppServiceProvider.php           # Singleton bindings (TenantContext) & DB schema defaults
├── Scopes/
│   └── CompanyScope.php                 # Global Eloquent scope enforcing tenant isolation at SQL layer
├── Services/
│   └── Tenant/
│       └── TenantContext.php            # Active tenant & branch session context manager (singleton)
├── Traits/
│   └── BelongsToCompany.php             # Model trait auto-applying CompanyScope and injecting company_id
└── View/
    └── Components/
        ├── AppLayout.php                # Master dashboard layout wrapper
        └── GuestLayout.php              # Authentication layout wrapper
```

---

## 3. Multi-Tenancy Architecture

### 3.1 Tenancy Strategy
The system implements **Single Database with Strict Multi-Tenant Scoping (Option A+)**, optimized for Pakistani SME installment businesses.

- **Isolation Enforcement**: Application and SQL data layer isolation.
- **`CompanyScope` Global Scope**: Automatically appends `WHERE company_id = ?` to all tenant-scoped queries.
- **Automatic Key Injection**: The `BelongsToCompany` trait captures the active company ID from `TenantContext` during Eloquent `creating` events.
- **Zero Input Trust**: The application never accepts `company_id` or `tenant_id` from raw HTTP input, route parameters, or hidden form fields.
- **Bypass Capabilities**: Administrative maintenance commands or cross-tenant queue jobs can explicitly call `withoutCompany()` or `withoutGlobalScope(CompanyScope::class)`.

### 3.2 Tenant Resolution & Lifecycle
1. User authenticates via `/login`.
2. `TenantMiddleware` verifies that:
   - The user account is active (`status === 'active'`).
   - The user's affiliated company is active (`company->status === 'active'`).
3. `TenantContext` is populated with the authenticated user's `Company` and assigned `Branch`.
4. All downstream Eloquent operations inherit this tenant context automatically.

---

## 4. Branch Hierarchy & Data Classification

Entities in the Electronic Installment SaaS are strictly classified into five architectural tiers:

| Tier | Classification | Definition | Examples |
| :--- | :--- | :--- | :--- |
| 1 | **GLOBAL** | Platform-level entities independent of individual companies. | Platform Super Admins, SaaS Subscription Tiers, System Audit Logs. |
| 2 | **COMPANY** | Tenant-level entities shared across all branches of a single company. | Company Profile, Payment Gateway Credentials, Role Definitions, General Settings. |
| 3 | **SHARED** | Entities visible across all company branches for customer convenience and underwriting. | Customers, Customer Guarantors, Credit Assessment History, Product Definitions. |
| 4 | **BRANCH** | Physical operational resources and transactional records specific to an individual showroom. | Showroom Stock/Inventory, Serialized Items (IMEI), Cash Drawers, Branch Staff. |
| 5 | **CONFIGURABLE** | Settings configurable globally or overridden at the branch level. | Late Fee Grace Periods, Minimum Down Payment %, Branch Receipt Headers. |

---

## 5. Security & Authentication Foundation

1. **Brute-Force Defense**: Rate limiting applied using `RateLimiter` (`throttle:5,1`) keying on email and client IP.
2. **Account Status Verification**: Suspended or inactive users are rejected at authentication and ejected mid-session via `TenantMiddleware`.
3. **Session Hardening**: CSRF token validation on all state-changing requests; complete session invalidation and regeneration upon login and logout.
4. **Audit Trail**: Every successful login tracks `last_login_at` and `last_login_ip`.
5. **Soft Deletes**: Enabled across all tenant and branch tables to preserve immutable audit trails for financial compliance.

---

## 6. Frontend Architecture & AppDashboard Integration

- **Design System**: AppDashboard Pro (Bootstrap v5.3.8, Vanilla JavaScript).
- **Zero Framework Bloat**: No React, Vue, Inertia, or Tailwind.
- **Blade Component Architecture**:
  - `layouts/app.blade.php`: Master dashboard layout with responsive drawer, header context, and user dropdown.
  - `layouts/guest.blade.php`: Authentication split-screen layout with enterprise branding.
  - Reusable components: `<x-header>`, `<x-sidebar>`, `<x-footer>`, `<x-app-layout>`, `<x-guest-layout>`.
- **Theme Persistence**: Light and Dark modes managed via Bootstrap 5 `data-bs-theme` and persisted to browser `localStorage`.
