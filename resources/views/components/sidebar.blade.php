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
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('dashboard') }}">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <!-- Operations Section -->
      <li class="nav-heading"><span>Business Operations</span></li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
          <i class="bi bi-people"></i>
          <span>Customers & Guarantors</span>
          <span class="badge bg-success ms-auto small">Phase 05</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('credit.assessments.*') ? 'active' : '' }}" href="{{ route('credit.assessments.index') }}">
          <i class="bi bi-speedometer2"></i>
          <span>Credit Underwriting</span>
          <span class="badge bg-success ms-auto small">Phase 06</span>
        </a>
      </li>

      @if(auth()->user()?->can('credit.approve'))
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('credit.approvals.*') ? 'active' : '' }}" href="{{ route('credit.approvals.index') }}">
            <i class="bi bi-patch-check"></i>
            <span>Approval Queue</span>
            <span class="badge bg-warning text-dark ms-auto small">Sign-off</span>
          </a>
        </li>
      @endif

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('products.*') || request()->routeIs('categories.*') || request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
          <i class="bi bi-boxes"></i>
          <span>Products & Catalog</span>
          <span class="badge bg-success ms-auto small">Phase 07</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
          <i class="bi bi-upc-scan"></i>
          <span>Inventory & Serialized</span>
          <span class="badge bg-success ms-auto small">Phase 07</span>
        </a>
      </li>


      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('pricing.*') ? 'active' : '' }}" href="{{ route('pricing.calculator') }}">
          <i class="bi bi-calculator"></i>
          <span>Pricing Calculator</span>
          <span class="badge bg-success ms-auto small">Phase 08</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('plans.*') ? 'active' : '' }}" href="{{ route('plans.index') }}">
          <i class="bi bi-credit-card-2-front"></i>
          <span>Installment Plans</span>
          <span class="badge bg-success ms-auto small">Phase 08</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('agreements.*') ? 'active' : '' }}" href="{{ route('agreements.index') }}">
          <i class="bi bi-file-earmark-text"></i>
          <span>Installment Contracts</span>
          <span class="badge bg-success ms-auto small">Phase 09</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
          <i class="bi bi-cash-stack"></i>
          <span>Payments & Receipts</span>
          <span class="badge bg-success ms-auto small">Phase 10</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}" href="{{ route('collections.dashboard') }}">
          <i class="bi bi-geo-alt"></i>
          <span>Field Recovery & Visits</span>
          <span class="badge bg-success ms-auto small">Phase 11</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recovery.*') ? 'active' : '' }}" href="{{ route('recovery.dashboard') }}">
          <i class="bi bi-shield-exclamation"></i>
          <span>Late Fees & Recovery</span>
          <span class="badge bg-success ms-auto small">Phase 12</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.hub') }}">
          <i class="bi bi-printer"></i>
          <span>Documents & Print Hub</span>
          <span class="badge bg-success ms-auto small">Phase 13</span>
        </a>
      </li>



      <!-- Administration Section -->
      <li class="nav-heading"><span>Administration & Tenancy</span></li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
          <i class="bi bi-shop"></i>
          <span>Branch Showrooms</span>
          <span class="badge bg-success ms-auto small">Phase 03</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('company.settings.*') ? 'active' : '' }}" href="{{ route('company.settings.edit') }}">
          <i class="bi bi-building-gear"></i>
          <span>Company Profile</span>
          <span class="badge bg-success ms-auto small">Phase 03</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
          <i class="bi bi-people"></i>
          <span>Staff Directory</span>
          <span class="badge bg-success ms-auto small">Phase 04</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
          <i class="bi bi-shield-check"></i>
          <span>Roles & Permissions</span>
          <span class="badge bg-success ms-auto small">Phase 04</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link text-muted" href="#" onclick="alert('Phase 06: Financial Reports, Ledger & Aging will be implemented in Phase 06.')">
          <i class="bi bi-graph-up-arrow"></i>
          <span>Reports & Audits</span>
          <span class="badge bg-secondary ms-auto small">Phase 06</span>
        </a>
      </li>
    </ul>
  </nav>
</aside>
