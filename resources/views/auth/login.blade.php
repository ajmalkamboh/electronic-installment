<x-guest-layout title="Sign In">
  <div class="auth-card">
    <div class="auth-card-header">
      <span class="auth-card-kicker">Secure Access</span>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-subtitle">Sign in to your company workspace to manage installments and collections.</p>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <div class="d-flex align-items-center mb-1">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
          <strong>Authentication Error</strong>
        </div>
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="auth-note-panel">
      <i class="bi bi-shield-lock"></i>
      <span>Protected by company-level multi-tenant boundaries and session integrity controls.</span>
    </div>

    <form class="auth-form" method="POST" action="{{ route('login') }}" novalidate>
      @csrf

      <div class="form-group mb-3">
        <label for="email" class="form-label">Email address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', 'admin@installment.test') }}" placeholder="name@company.com" required autofocus>
        </div>
        @error('email')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group mb-3">
        <div class="auth-helper-row d-flex justify-content-between align-items-center">
          <label for="password" class="form-label mb-0">Password</label>
        </div>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="password" placeholder="Enter your password" required>
          <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()" title="Toggle password visibility">
            <i class="bi bi-eye" id="togglePasswordIcon"></i>
          </button>
        </div>
        @error('password')
          <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
        <label class="form-check-label text-muted small" for="remember">Keep this workstation signed in</label>
      </div>

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Workspace
      </button>
    </form>

    <div class="mt-4 pt-3 border-top">
      <h6 class="text-muted text-uppercase fs-7 fw-bold mb-2">Seed Verification Accounts:</h6>
      <div class="d-flex flex-column gap-2 small">
        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
          <div>
            <strong>Ajmal Admin</strong> <span class="badge bg-primary ms-1">Company Admin</span><br>
            <span class="text-muted">admin@installment.test</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="fillCredentials('admin@installment.test', 'password')">Use</button>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
          <div>
            <strong>Tariq Manager</strong> <span class="badge bg-secondary ms-1">Branch Manager</span><br>
            <span class="text-muted">manager@installment.test</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="fillCredentials('manager@installment.test', 'password')">Use</button>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
          <div>
            <strong>Suspended Staff</strong> <span class="badge bg-danger ms-1">Suspended</span><br>
            <span class="text-muted">suspended@installment.test</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="fillCredentials('suspended@installment.test', 'password')">Test Security</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const icon = document.getElementById('togglePasswordIcon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    function fillCredentials(email, password) {
      document.getElementById('email').value = email;
      document.getElementById('password').value = password;
    }
  </script>
</x-guest-layout>
