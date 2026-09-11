# System Architecture & Technical Specification

## 1. System Technology Stack

- **Core Framework**: Laravel 13.x (PHP 8.3+)
- **Runtime Environment**: WAMP (Apache 2.4.65, PHP 8.3.28, MySQL 8.4.7 InnoDB)
- **Frontend Architecture**: Laravel Blade + Livewire 3/4 + Bootstrap 5.3.8 (AppDashboard Pro)
- **Database Engine**: MySQL 8.4 InnoDB (`utf8mb4` charset, `utf8mb4_unicode_ci` collation, `defaultStringLength(191)`)
- **Asynchronous Processing**: Laravel Queue (`database` driver), Laravel Scheduler
- **Notification Engine**: Laravel Notification System (SMS Gateways, WhatsApp API, Email, In-App)
- **Timezone**: `Asia/Karachi` (PKT, UTC+5)
- **Base Currency**: PKR (Pakistani Rupee)

---

## 2. Entity Scope Matrix & Data Ownership

To eliminate architectural ambiguity, every entity in the system is explicitly categorized across five organizational tiers.

| Entity | Tier | Tenant Owned (`company_id`) | Branch Owned (`branch_id`) | Shared Within Tenant | Global Platform | Architectural Reasoning |
| :--- | :--- | :---: | :---: | :---: | :---: | :--- |
| **Platform Super Admin** | Global | No | No | No | **Yes** | Manages SaaS subscriptions, billing, and platform health. |
| **SaaS Plan & Subscription** | Global | Yes (Tenant Sub) | No | No | **Yes** | Defined globally by platform operator; subscriptions belong to companies. |
| **Company (Tenant)** | Company | **Root Tenant** | No | No | No | The top-level legal business enterprise selling on installment plans. |
| **Branch** | Branch | **Yes** | **Root Branch**| No | No | Physical showroom/outlet; must belong to a single company. |
| **User / Staff** | Branch | **Yes** | **Yes** (Nullable)| No | No | Belongs to Company; optionally assigned to a Branch (Super Admins have no branch). |
| **Customer** | Shared | **Yes** | No | **Yes** | No | Customers are shared across all company branches so credit history and payments are unified. |
| **Customer Credit Profile** | Shared | **Yes** | No | **Yes** | No | Centralized credit score, past defaults, and overall eligibility across all branches. |
| **Guarantor & Reference** | Shared | **Yes** | No | **Yes** | No | Tied to the Customer/Agreement; accessible company-wide for verification. |
| **Customer Verification** | Shared | **Yes** | Optional | **Yes** | No | Physical address/utility bill audit; initiated at branch, shared company-wide. |
| **Credit Assessment** | Shared | **Yes** | Optional | **Yes** | No | Credit scoring analysis evaluating risk before financing approval. |
| **Product Category & Brand** | Company | **Yes** | No | **Yes** | No | Standard product hierarchy defined once per company. |
| **Product Master** | Company | **Yes** | No | **Yes** | No | Model definitions, specs, cash prices, and standard markup rules. |
| **Supplier** | Company | **Yes** | No | **Yes** | No | Commercial vendors supplying electronic stock to the company. |
| **Branch Inventory Stock** | Branch | **Yes** | **Yes** | No | No | Physical non-serialized quantity on hand at an individual showroom. |
| **Serialized Item (IMEI/Serial)**| Branch | **Yes** | **Yes** | No | No | Distinct physical item with IMEI/Serial located at a specific showroom until sold. |
| **Stock Movement / Transfer** | Branch | **Yes** | **Yes** (Origin/Dest)| No | No | Audit trail of inventory transferred between showrooms or supplier receipts. |
| **Installment Plan Template** | Company | **Yes** | No | **Yes** | No | Standard company financing templates (e.g. 6 Months / 20%, 12 Months / 35%). |
| **Installment Agreement** | Branch | **Yes** | **Yes** (Origin) | **Viewable** | No | Binding contract issued by a specific branch; viewable and payable company-wide. |
| **Payment Schedule** | Branch | **Yes** | **Yes** (via Agr) | **Viewable** | No | Breakdown of installment due dates belonging to an agreement. |
| **Installment Payment** | Branch | **Yes** | **Yes** (Collected) | **Viewable** | No | Cash or bank transaction recorded at a specific branch/drawer. |
| **Payment Allocation** | Branch | **Yes** | **Yes** (via Pmt) | **Viewable** | No | Mathematical allocation of received cash against fees, overdue, and principal. |
| **Receipt** | Branch | **Yes** | **Yes** | No | No | Sequentially numbered print token issued at a specific cash drawer. |
| **Late Fee & Waiver** | Branch | **Yes** | **Yes** (via Agr) | No | No | System-accrued fee or managerial waiver tied to an agreement and branch audit. |
| **Cash Drawer / Till** | Branch | **Yes** | **Yes** | No | No | Physical or daily cashier drawer balance reconciled per shift at a branch. |
| **Collection Officer Visit** | Branch | **Yes** | **Yes** (Officer) | No | No | Field recovery record, customer contact, promise-to-pay, and GPS tag. |
| **System Audit Log** | Company | **Yes** | Optional | **Yes** | Optional | Immutable security record of status transitions, reversals, and user actions. |

---

## 3. Multi-Tenancy Architecture & Scoping

### 3.1 Tenancy Strategy: Single Database with Scoped Tenancy (Option A+)
Every tenant-owned table features a `company_id` foreign key. Query isolation is enforced at the Eloquent SQL layer via:
- **`CompanyScope` GlobalScope**: Automatically appends `WHERE company_id = ?` to all model queries.
- **`BelongsToCompany` Trait**: Injects `company_id` during the model's `creating` lifecycle hook from `TenantContext`.
- **`TenantContext` Singleton**: Resolves and caches the active Company and Branch for the authenticated request.
- **`TenantMiddleware`**: Verifies that user account is active, company subscription is active, and boots the `TenantContext`.

### 3.2 Zero-Trust Security on Tenant Parameters
- The application **NEVER** trusts `tenant_id` or `company_id` from raw HTTP forms, query parameters, or hidden inputs.
- All Eloquent model insertions automatically pull `company_id` from the secure server-side `TenantContext`.
- Any attempt to access a model outside the authenticated user's company triggers an HTTP 404/403.

### 3.3 Tenant-Aware Asynchronous Processing
- **Queue Jobs**: All background jobs (e.g. invoice generation, late-fee batch calculation) implement tenant awareness by storing `company_id` in job payload and bootstrapping `TenantContext` upon execution.
- **Scheduled Tasks**: The Laravel Scheduler runs maintenance routines by iterating through active companies:
  ```php
  Company::where('status', 'active')->each(function (Company $company) {
      app(TenantContext::class)->setCompany($company);
      // Run company-specific late fee calculations or due reminders
  });
  ```
- **Tenant-Aware Notifications**: SMS and WhatsApp notifications automatically utilize the active company's gateway credentials and customized branded header.

---

## 4. Branch Hierarchy & Operational Context

### 4.1 Branch Context vs Company Scope
- A user assigned to **Branch 1** has default visibility into Branch 1's physical inventory, cash drawer, and daily collections.
- However, when registering or looking up a customer, the search executes across the entire **Company** scope.
- If a customer who purchased at Branch 1 walks into **Branch 2** to pay an installment, the cashier at Branch 2 can look up Agreement #AGR-001, accept the cash payment, issue a Branch 2 receipt, and credit the agreement ledger immediately.

---

## 5. Security & Authorization Architecture

### 5.1 Authentication Hardening
- **Rate Limiting**: `LoginController` applies `RateLimiter` (`5 attempts / minute`) based on sanitized email and IP address.
- **Account Status Guard**: `active`, `suspended`, and `inactive` states. Suspended users are immediately rejected during authentication and ejected mid-session if suspended by an admin.
- **Session Protection**: Complete session regeneration and CSRF token refresh upon login and logout.
- **Audit Logging**: Every authentication event records `last_login_at` and `last_login_ip`.

### 5.2 Role-Based Access Control (RBAC)
Supported hierarchical roles:
1. **Platform Super Admin**: Multi-tenant platform operator.
2. **Company Owner**: Full business control over company settings, branches, and financial audits.
3. **Company Admin**: Day-to-day operations manager across all branches.
4. **Branch Manager**: Approves agreements, reviews drawer reconciliation, and approves late-fee waivers for assigned branch.
5. **Credit Officer**: Performs customer verification, guarantor interviews, and credit risk scoring.
6. **Sales Officer / Cashier**: Registers agreements, accepts down payments, and records over-the-counter installments.
7. **Collection Officer**: Field recovery officer recording field visits, promises-to-pay, and mobile cash collections.
8. **Auditor / Accountant**: Read-only ledger audits and financial report exports.

---

## 6. Financial Architecture & Immutability

### 6.1 Strict Financial Immutability
- Financial transactions, installment receipts, and ledger lines are **append-only**.
- Prohibited: Direct `DELETE` or `UPDATE` on posted financial payments.
- Required: **Reversal / Adjustment Transaction Pattern**:
  ```text
  [ Original Payment Receipt #1042: PKR 10,000 ] (Mistyped by Cashier)
                       │
                       ▼
  [ Reversal Transaction #REV-1042: PKR -10,000 ] (Authorized by Branch Manager)
                       │
                       ▼
  [ Corrected Payment Receipt #1043: PKR 1,000 ] (Corrected amount recorded)
                       │
                       ▼
  [ Linked Audit Log Entry with Reason & Timestamps ]
  ```

### 6.2 Payment Method vs Payment Status
To accommodate field cash collection and multi-person verification, the payment architecture separates payment channel from operational lifecycle:
- **Payment Method**: `cash`, `bank_transfer`, `cheque`, `raast`, `easypaisa`, `jazzcash`.
- **Payment Status**:
  1. `submitted`: Recorded by field Collection Officer; awaiting cash deposit into branch drawer.
  2. `pending_verification`: Bank transfer or cheque awaiting bank clearance.
  3. `acknowledged`: Verified and accepted by Branch Accountant/Cashier; credited to active schedule.
  4. `reversed`: Compensated via an authorized reversal transaction.

### 6.3 Payment Allocation Hierarchy
When a customer pays an amount, the payment engine allocates funds strictly in the following priority:
1. **Accrued Late Fees & Penalties** (unless explicitly waived).
2. **Earliest Overdue Installment** (Principal + Markup).
3. **Current Due Installment**.
4. **Advance Installments** (subsequent future months).
*Rule*: A scheduled installment is only marked `paid` when the cumulative received allocations equal or exceed the scheduled amount. Partial payments leave the installment in `partially_paid` status with an exact remaining balance.

---

## 7. Inventory & Serialized Asset Architecture

### 7.1 Serialized vs Non-Serialized Tracking
- **Serialized Items**: Mandatory unique tracking for high-value electronics (smartphones, tablets, laptops, motorbikes, major appliances). Tracks `imei_1`, `imei_2`, `serial_number`, `asset_tag`, `color`, `condition`.
- **Non-Serialized Items**: Batch quantity tracking for minor electronics, cables, accessories.

### 7.2 Serialized Item State Machine
```text
  [ In Stock (Showroom) ] ────► [ Reserved (Credit Pending) ]
            │                                   │
            ▼                                   ▼
  [ Transferred to Branch ]            [ Allocated to Agreement ]
                                                │
                                                ▼
                                    [ Released / Disbursed ]
                                                │
                                                ▼
                               [ Repossessed (Legal Default) ]
```

---

## 8. Recommended Future Service Architecture

To keep controllers and Livewire components lean, all business logic will reside in dedicated Service and Action classes under `app/Services/`:

```text
app/Services/
├── Customer/
│   ├── CustomerService.php               # Customer registration & duplicate checks
│   ├── CustomerVerificationService.php   # Address & utility bill verification
│   ├── CreditAssessmentService.php       # Credit risk scoring & guarantor rating
│   └── CreditApprovalService.php         # Multi-tier credit approval workflow
├── Product/
│   └── ProductService.php                # Catalog, pricing, and category management
├── Inventory/
│   ├── InventoryService.php              # Stock levels and batch movements
│   ├── SerializedItemService.php         # IMEI/Serial lifecycle and status transitions
│   └── InventoryAllocationService.php    # Reserving & allocating IMEI to agreements
├── Agreement/
│   ├── InstallmentPricingService.php     # Fixed, percentage, and plan-based markup calculation
│   ├── InstallmentScheduleService.php    # Due-date generator (monthly, weekly, custom)
│   └── AgreementService.php              # Contract generation, negotiation audit trail & activation
├── Payment/
│   ├── PaymentService.php                # Payment recording, acknowledgement & drawer posting
│   ├── PaymentAllocationService.php      # Mathematical allocation (fees -> overdue -> current -> advance)
│   ├── LateFeeService.php                # Grace period evaluation & late fee application
│   └── LateFeeWaiverService.php          # Managerial waiver authorization & audit logging
├── Collection/
│   ├── CollectionService.php             # Field officer assignment & daily target monitoring
│   └── RecoveryService.php               # Configurable recovery stage transitions (defaulter management)
├── Accounting/
│   └── LedgerService.php                 # Immutable transaction entries and reversal adjustments
├── Document/
│   ├── ReceiptService.php                # Thermal (58mm/80mm) & digital receipt generation
│   └── DocumentService.php               # PDF generation (Agreement contract, Affidavit, NOC clearance)
└── Notification/
    └── NotificationDispatchService.php   # SMS, WhatsApp, and email dispatch routing
```
