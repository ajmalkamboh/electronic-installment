@props(['user', 'company', 'branch'])

<header class="header">
  <!-- Header Left -->
  <div class="header-left">
    <a href="{{ route('dashboard') }}" class="header-logo">
      <span class="header-logo-mark">
        <i class="bi bi-wallet2 text-primary fs-3"></i>
      </span>
      <span class="fw-bold">{{ config('app.name', 'Electronic Installment') }}</span>
    </a>
    <button class="sidebar-toggle" type="button" title="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>
    <div class="header-context d-none d-md-flex">
      <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">
        <i class="bi bi-building me-1"></i>{{ $company->name ?? 'Default Company' }}
      </span>
      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
        <i class="bi bi-geo-alt me-1"></i>{{ $branch->name ?? 'All Branches' }}
      </span>
    </div>
  </div>

  <!-- Header Search (Desktop) -->
  <div class="header-search">
    <div class="search-form">
      <button type="button"><i class="bi bi-search"></i></button>
      <input type="search" placeholder="Search customer, agreement #, CNIC, or IMEI..." autocomplete="off">
    </div>
  </div>

  <!-- Header Right -->
  <div class="header-right">
    <!-- Desktop Actions -->
    <div class="header-actions-desktop">
      <!-- Theme Toggle -->
      <button class="header-action theme-toggle" type="button" title="Toggle Light/Dark Theme">
        <i class="bi bi-moon icon-dark"></i>
        <i class="bi bi-sun icon-light"></i>
      </button>

      <!-- Fullscreen Toggle -->
      <button class="header-action fullscreen-toggle" type="button" onclick="toggleFullscreen()" title="Fullscreen">
        <i class="bi bi-fullscreen icon-enter"></i>
        <i class="bi bi-fullscreen-exit icon-exit"></i>
      </button>

      <!-- Notifications -->
      <div class="header-action dropdown notification-dropdown">
        <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" type="button">
          <i class="bi bi-bell"></i>
          <span class="badge">1</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
          <div class="notification-header">
            <div>
              <span>System</span>
              <h6>Notifications</h6>
            </div>
          </div>
          <div class="notification-list">
            <div class="notification-item">
              <div class="notification-icon success">
                <i class="bi bi-shield-check"></i>
              </div>
              <div class="notification-content">
                <div class="notification-title">Phase 01 Active</div>
                <div class="notification-text">Foundation, Tenancy, and Security verified.</div>
                <div class="notification-time">Just now</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- User Dropdown -->
      <div class="header-action dropdown user-dropdown">
        <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" type="button">
          <div class="avatar bg-primary text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; font-weight: bold;">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
          </div>
          <span class="user-name">
            <strong>{{ auth()->user()->name ?? 'User' }}</strong>
            <small class="text-capitalize">{{ str_replace('_', ' ', auth()->user()->role ?? 'Staff') }}</small>
          </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li class="dropdown-header">
            <h6>{{ auth()->user()->name ?? 'User' }}</h6>
            <span class="text-muted small">{{ auth()->user()->email ?? '' }}</span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <div class="dropdown-item text-muted small">
              <i class="bi bi-building me-2"></i>{{ $company->name ?? 'Company' }}
            </div>
          </li>
          <li>
            <div class="dropdown-item text-muted small">
              <i class="bi bi-geo-alt me-2"></i>{{ $branch->name ?? 'Branch' }}
            </div>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item text-danger">
                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>

    <!-- Mobile Actions -->
    <div class="header-actions-mobile">
      <button class="header-action mobile-menu-toggle" type="button" title="More">
        <i class="bi bi-three-dots-vertical"></i>
      </button>
    </div>
  </div>
</header>
