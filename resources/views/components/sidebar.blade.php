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
        <a class="nav-link text-muted" href="#" onclick="alert('Phase 02: Customer Registration & Credit Engine will be implemented in the next phase.')">
          <i class="bi bi-people"></i>
          <span>Customers & Guarantors</span>
          <span class="badge bg-secondary ms-auto small">Phase 02</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link text-muted" href="#" onclick="alert('Phase 03: Installment Agreements & Payment Schedules will be implemented in Phase 03.')">
          <i class="bi bi-file-earmark-text"></i>
          <span>Installment Agreements</span>
          <span class="badge bg-secondary ms-auto small">Phase 03</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link text-muted" href="#" onclick="alert('Phase 04: Installment Collection & Receipts will be implemented in Phase 04.')">
          <i class="bi bi-cash-stack"></i>
          <span>Collections & Receipts</span>
          <span class="badge bg-secondary ms-auto small">Phase 04</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link text-muted" href="#" onclick="alert('Phase 05: Inventory & Serialized Items (IMEI/Serial) will be implemented in Phase 05.')">
          <i class="bi bi-boxes"></i>
          <span>Inventory & Serialized</span>
          <span class="badge bg-secondary ms-auto small">Phase 05</span>
        </a>
      </li>

      <!-- Administration Section -->
      <li class="nav-heading"><span>Administration & Tenancy</span></li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard') }}">
          <i class="bi bi-building"></i>
          <span>Company & Branches</span>
          <span class="badge bg-success ms-auto small">Active</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard') }}">
          <i class="bi bi-shield-check"></i>
          <span>Security & Roles</span>
          <span class="badge bg-success ms-auto small">Active</span>
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
