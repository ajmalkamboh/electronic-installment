<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'Login' }} - {{ config('app.name', 'Electronic Installment') }}</title>
  <meta name="description" content="Electronic Installment SaaS - Production-grade installment management platform">

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
  @livewireStyles
</head>

<body>
  <div class="auth-layout">
    <div class="auth-shell">
      <aside class="auth-brand-panel">
        <a href="{{ url('/') }}" class="auth-logo">
          <span class="fs-4 fw-bold text-white"><i class="bi bi-wallet2 text-primary me-2"></i>{{ config('app.name', 'Electronic Installment') }}</span>
        </a>

        <div class="auth-brand-copy">
          <span>Enterprise SaaS</span>
          <h2>Streamlined installment operations from a centralized command center.</h2>
          <p>Automate customer credit assessment, payment plans, installment collection tracking, and inventory allocation with institutional confidence.</p>
        </div>

        <div class="auth-signal-grid">
          <div>
            <strong>100%</strong>
            <span>Tenant Isolated</span>
          </div>
          <div>
            <strong>Multi-Branch</strong>
            <span>Enabled</span>
          </div>
          <div>
            <strong>Audit Trail</strong>
            <span>Protected</span>
          </div>
        </div>

        <div class="auth-brand-card">
          <i class="bi bi-shield-check text-success"></i>
          <div>
            <strong>Strict Data Isolation Active</strong>
            <span>Cross-tenant protection, session auditing, and role-based access enforce strict confidentiality.</span>
          </div>
        </div>
      </aside>

      <main class="auth-main">
        <div class="auth-main-inner">
          <a href="{{ url('/') }}" class="auth-logo auth-logo-mobile">
            <span class="fs-4 fw-bold text-dark"><i class="bi bi-wallet2 text-primary me-2"></i>{{ config('app.name', 'Electronic Installment') }}</span>
          </a>

          {{ $slot }}

          <footer class="footer-centered">
            <div class="footer-copyright">
              &copy; {{ date('Y') }} <strong>{{ config('app.name', 'Electronic Installment SaaS') }}</strong>. All Rights Reserved.
            </div>
            <div class="footer-links">
              <span>Production Foundation v1.0</span>
            </div>
          </footer>
        </div>
      </main>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
  @livewireScripts
</body>

</html>
