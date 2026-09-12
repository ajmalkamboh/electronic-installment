@props(['company', 'branch'])

<aside class="sidebar">
  <!-- Sidebar Header -->
  <div class="sidebar-header">
    <a href="{{ route('dashboard') }}" class="sidebar-logo">
      <span class="sidebar-logo-mark">
        <i class="bi bi-wallet2 text-primary fs-4"></i>
      </span>
      <span class="sidebar-logo-text">
        <span class="sidebar-logo-name">Installment</span>
        <span class="sidebar-logo-label">SaaS Suite</span>
      </span>
    </a>
    <button class="sidebar-close" type="button" title="Close Sidebar">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <!-- Sidebar Navigation -->
  <nav class="sidebar-nav">
    <ul class="nav-menu">

      <!-- Main Overview -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
          <i class="bi bi-grid-1x2-fill"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <!-- ========================================================= -->
      <!-- 1. SALES & INSTALLMENT CONTRACTS -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>Sales &amp; Contracts</span></li>

      <!-- Customers & Dossiers Submenu -->
      @php
        $isCustomersActive = request()->routeIs('customers.*');
      @endphp
      <li class="nav-item has-submenu {{ $isCustomersActive ? 'open' : '' }}">
        <a class="nav-link {{ $isCustomersActive ? 'active' : '' }}" href="#">
          <i class="bi bi-people-fill"></i>
          <span>Customers</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isCustomersActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}">
              <span>All Customers</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('customers.create') ? 'active' : '' }}" href="{{ route('customers.create') }}">
              <span>Register Customer</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Credit Underwriting Submenu -->
      @php
        $isCreditActive = request()->routeIs('credit.*');
      @endphp
      <li class="nav-item has-submenu {{ $isCreditActive ? 'open' : '' }}">
        <a class="nav-link {{ $isCreditActive ? 'active' : '' }}" href="#">
          <i class="bi bi-speedometer2"></i>
          <span>Credit Underwriting</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isCreditActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('credit.assessments.*') ? 'active' : '' }}" href="{{ route('credit.assessments.index') }}">
              <span>Credit Assessments</span>
            </a>
          </li>
          @if(auth()->user()?->can('credit.approve') || auth()->user()?->isCompanyAdmin())
            <li>
              <a class="nav-link {{ request()->routeIs('credit.approvals.*') ? 'active' : '' }}" href="{{ route('credit.approvals.index') }}">
                <span>Approval Queue</span>
              </a>
            </li>
          @endif
        </ul>
      </li>

      <!-- Agreements & Quotations Submenu -->
      @php
        $isContractsActive = request()->routeIs('agreements.*') || request()->routeIs('plans.*') || request()->routeIs('pricing.*');
      @endphp
      <li class="nav-item has-submenu {{ $isContractsActive ? 'open' : '' }}">
        <a class="nav-link {{ $isContractsActive ? 'active' : '' }}" href="#">
          <i class="bi bi-file-earmark-text-fill"></i>
          <span>Contracts &amp; Plans</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isContractsActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('agreements.index') ? 'active' : '' }}" href="{{ route('agreements.index') }}">
              <span>Installment Contracts</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('agreements.create') ? 'active' : '' }}" href="{{ route('agreements.create') }}">
              <span>New Contract</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('plans.*') ? 'active' : '' }}" href="{{ route('plans.index') }}">
              <span>Installment Plans</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('pricing.*') ? 'active' : '' }}" href="{{ route('pricing.calculator') }}">
              <span>Pricing Calculator</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- ========================================================= -->
      <!-- 2. COLLECTIONS & FIELD RECOVERY -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>Collections &amp; Recovery</span></li>

      <!-- Payments & Cashier Submenu -->
      @php
        $isPaymentsActive = request()->routeIs('payments.*') || request()->routeIs('accounting.cash-book*');
      @endphp
      <li class="nav-item has-submenu {{ $isPaymentsActive ? 'open' : '' }}">
        <a class="nav-link {{ $isPaymentsActive ? 'active' : '' }}" href="#">
          <i class="bi bi-cash-stack"></i>
          <span>Payments &amp; Till</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isPaymentsActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('payments.index') ? 'active' : '' }}" href="{{ route('payments.index') }}">
              <span>Payments Ledger</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('payments.create') ? 'active' : '' }}" href="{{ route('payments.create') }}">
              <span>Receive Payment</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.cash-book*') ? 'active' : '' }}" href="{{ route('accounting.cash-book') }}">
              <span>Showroom Cash Book</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Field Collection Submenu -->
      @php
        $isCollectionsActive = request()->routeIs('collections.*');
      @endphp
      <li class="nav-item has-submenu {{ $isCollectionsActive ? 'open' : '' }}">
        <a class="nav-link {{ $isCollectionsActive ? 'active' : '' }}" href="#">
          <i class="bi bi-geo-alt-fill"></i>
          <span>Field Collection</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isCollectionsActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('collections.dashboard') ? 'active' : '' }}" href="{{ route('collections.dashboard') }}">
              <span>Field Ops Dashboard</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('collections.run-sheet*') ? 'active' : '' }}" href="{{ route('collections.run-sheet') }}">
              <span>Daily Run Sheets</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('collections.handovers*') ? 'active' : '' }}" href="{{ route('collections.handovers') }}">
              <span>Cash Handovers</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Delinquency & Recovery Submenu -->
      @php
        $isRecoveryActive = request()->routeIs('recovery.*');
      @endphp
      <li class="nav-item has-submenu {{ $isRecoveryActive ? 'open' : '' }}">
        <a class="nav-link {{ $isRecoveryActive ? 'active' : '' }}" href="#">
          <i class="bi bi-shield-exclamation"></i>
          <span>Delinquency &amp; Recovery</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isRecoveryActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('recovery.dashboard') ? 'active' : '' }}" href="{{ route('recovery.dashboard') }}">
              <span>Recovery Command</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('recovery.cases.*') ? 'active' : '' }}" href="{{ route('recovery.cases.index') }}">
              <span>Recovery Cases</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('recovery.late-fees*') ? 'active' : '' }}" href="{{ route('recovery.late-fees') }}">
              <span>Late Fees &amp; Waivers</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- ========================================================= -->
      <!-- 3. INVENTORY & LOGISTICS -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>Catalog &amp; Inventory</span></li>

      <!-- Product Catalog Submenu -->
      @php
        $isCatalogActive = request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('suppliers.*');
      @endphp
      <li class="nav-item has-submenu {{ $isCatalogActive ? 'open' : '' }}">
        <a class="nav-link {{ $isCatalogActive ? 'active' : '' }}" href="#">
          <i class="bi bi-boxes"></i>
          <span>Products &amp; Catalog</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isCatalogActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}">
              <span>Product Catalog</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('products.create') ? 'active' : '' }}" href="{{ route('products.create') }}">
              <span>Add New Product</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
              <span>Categories</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
              <span>Suppliers</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Warehouse & Serialized Stock Submenu -->
      @php
        $isInventoryActive = request()->routeIs('inventory.*') || request()->routeIs('transfers.*');
      @endphp
      <li class="nav-item has-submenu {{ $isInventoryActive ? 'open' : '' }}">
        <a class="nav-link {{ $isInventoryActive ? 'active' : '' }}" href="#">
          <i class="bi bi-upc-scan"></i>
          <span>Inventory &amp; Transfers</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isInventoryActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
              <span>Stock Overview</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('inventory.serialized*') ? 'active' : '' }}" href="{{ route('inventory.serialized') }}">
              <span>Serialized (IMEI / SN)</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('inventory.receipt*') ? 'active' : '' }}" href="{{ route('inventory.receipt.create') }}">
              <span>Stock Receipt Intake</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('transfers.index') ? 'active' : '' }}" href="{{ route('transfers.index') }}">
              <span>Stock Transfers</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('transfers.create') ? 'active' : '' }}" href="{{ route('transfers.create') }}">
              <span>New Transfer / Gate Pass</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- ========================================================= -->
      <!-- 4. FINANCIAL ACCOUNTING -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>General Ledger &amp; Accounts</span></li>

      @php
        $isAccountingActive = request()->routeIs('accounting.*') && !request()->routeIs('accounting.cash-book*');
      @endphp
      <li class="nav-item has-submenu {{ $isAccountingActive ? 'open' : '' }}">
        <a class="nav-link {{ $isAccountingActive ? 'active' : '' }}" href="#">
          <i class="bi bi-journal-bookmark-fill"></i>
          <span>Financial Accounting</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isAccountingActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.coa*') ? 'active' : '' }}" href="{{ route('accounting.coa') }}">
              <span>Chart of Accounts</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.journal') ? 'active' : '' }}" href="{{ route('accounting.journal') }}">
              <span>Journal Entries</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.journal.create') ? 'active' : '' }}" href="{{ route('accounting.journal.create') }}">
              <span>New Journal Voucher</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.trial-balance*') ? 'active' : '' }}" href="{{ route('accounting.trial-balance') }}">
              <span>Trial Balance</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.profit-loss*') ? 'active' : '' }}" href="{{ route('accounting.profit-loss') }}">
              <span>Profit &amp; Loss</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('accounting.balance-sheet*') ? 'active' : '' }}" href="{{ route('accounting.balance-sheet') }}">
              <span>Balance Sheet</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- ========================================================= -->
      <!-- 5. BUSINESS INTELLIGENCE & DOCUMENTS -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>Reports &amp; Intelligence</span></li>

      <!-- Executive Analytics Submenu -->
      @php
        $isAnalyticsActive = request()->routeIs('analytics.*');
      @endphp
      <li class="nav-item has-submenu {{ $isAnalyticsActive ? 'open' : '' }}">
        <a class="nav-link {{ $isAnalyticsActive ? 'active' : '' }}" href="#">
          <i class="bi bi-graph-up-arrow"></i>
          <span>Analytics &amp; Reports</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isAnalyticsActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('analytics.dashboard*') ? 'active' : '' }}" href="{{ route('analytics.dashboard') }}">
              <span>Executive Dashboard</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('analytics.aging*') ? 'active' : '' }}" href="{{ route('analytics.aging') }}">
              <span>Portfolio Aging &amp; PAR</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('analytics.collections*') ? 'active' : '' }}" href="{{ route('analytics.collections') }}">
              <span>Collection Efficiency</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('analytics.branches*') ? 'active' : '' }}" href="{{ route('analytics.branches') }}">
              <span>Branch Leaderboard</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('analytics.products*') ? 'active' : '' }}" href="{{ route('analytics.products') }}">
              <span>Category Profitability</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- Legal Documents Print Hub (Direct) -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.hub') }}">
          <i class="bi bi-printer-fill"></i>
          <span>Documents &amp; Print Hub</span>
        </a>
      </li>

      <!-- Communications & Outbox Submenu -->
      @php
        $isCommsActive = request()->routeIs('notifications.*');
      @endphp
      <li class="nav-item has-submenu {{ $isCommsActive ? 'open' : '' }}">
        <a class="nav-link {{ $isCommsActive ? 'active' : '' }}" href="#">
          <i class="bi bi-chat-left-dots-fill"></i>
          <span>Communications</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isCommsActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('notifications.index*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
              <span>Notification Outbox</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('notifications.templates*') ? 'active' : '' }}" href="{{ route('notifications.templates') }}">
              <span>Message Templates</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('notifications.settings*') ? 'active' : '' }}" href="{{ route('notifications.settings') }}">
              <span>Gateway Settings</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- ========================================================= -->
      <!-- 6. ADMINISTRATION & TENANCY -->
      <!-- ========================================================= -->
      <li class="nav-heading"><span>Administration</span></li>

      <!-- Settings & Access Submenu -->
      @php
        $isAdminActive = request()->routeIs('company.settings.*') || request()->routeIs('branches.*') || request()->routeIs('staff.*') || request()->routeIs('roles.*') || request()->routeIs('tenant.security.audit-logs*');
      @endphp
      <li class="nav-item has-submenu {{ $isAdminActive ? 'open' : '' }}">
        <a class="nav-link {{ $isAdminActive ? 'active' : '' }}" href="#">
          <i class="bi bi-gear-wide-connected"></i>
          <span>Company Settings</span>
          <i class="bi bi-chevron-down nav-arrow"></i>
        </a>
        <ul class="nav-submenu {{ $isAdminActive ? 'show' : '' }}">
          <li>
            <a class="nav-link {{ request()->routeIs('company.settings.*') ? 'active' : '' }}" href="{{ route('company.settings.edit') }}">
              <span>Company Profile</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
              <span>Branch Showrooms</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
              <span>Staff Directory</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
              <span>Roles &amp; Permissions</span>
            </a>
          </li>
          <li>
            <a class="nav-link {{ request()->routeIs('tenant.security.audit-logs*') ? 'active' : '' }}" href="{{ route('tenant.security.audit-logs') }}">
              <span>Security &amp; Audit Trail</span>
            </a>
          </li>
        </ul>
      </li>

      <!-- SaaS Subscription & Limits (Direct) -->
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('subscription.*') ? 'active' : '' }}" href="{{ route('subscription.index') }}">
          <i class="bi bi-credit-card-2-front-fill"></i>
          <span>SaaS &amp; Subscription</span>
        </a>
      </li>

      <!-- ========================================================= -->
      <!-- 7. PLATFORM SUPER ADMIN (ROOT ONLY) -->
      <!-- ========================================================= -->
      @if(auth()->user()?->isSuperAdmin())
        <li class="nav-heading"><span class="text-danger fw-bold">Platform Super Admin</span></li>

        @php
          $isSuperAdminActive = request()->routeIs('admin.*');
        @endphp
        <li class="nav-item has-submenu {{ $isSuperAdminActive ? 'open' : '' }}">
          <a class="nav-link {{ $isSuperAdminActive ? 'active' : '' }}" href="#">
            <i class="bi bi-shield-lock-fill text-danger"></i>
            <span>Platform Root</span>
            <i class="bi bi-chevron-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu {{ $isSuperAdminActive ? 'show' : '' }}">
            <li>
              <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <span>Command Center</span>
              </a>
            </li>
            <li>
              <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                <span>Tenants Directory</span>
              </a>
            </li>
            <li>
              <a class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" href="{{ route('admin.plans.index') }}">
                <span>SaaS Pricing Plans</span>
              </a>
            </li>
            <li>
              <a class="nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">
                <span>Subscriptions Ledger</span>
              </a>
            </li>
            <li>
              <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">
                <span>Audit Trail Logs</span>
              </a>
            </li>
            <li>
              <a class="nav-link {{ request()->routeIs('admin.health.*') ? 'active' : '' }}" href="{{ route('admin.health.index') }}">
                <span>Diagnostics &amp; Health</span>
              </a>
            </li>
          </ul>
        </li>
      @endif

    </ul>
  </nav>
</aside>
