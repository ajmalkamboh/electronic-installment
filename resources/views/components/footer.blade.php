<footer class="footer">
  <div class="footer-content d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="footer-copyright text-muted small">
      &copy; {{ date('Y') }} <strong>{{ config('app.name', 'Electronic Installment SaaS') }}</strong>. All rights reserved.
    </div>
    <div class="footer-meta text-muted small">
      <span class="badge bg-light text-dark border me-2">Laravel v{{ Illuminate\Foundation\Application::VERSION }}</span>
      <span class="badge bg-light text-dark border me-2">PHP v{{ PHP_VERSION }}</span>
      <span class="badge bg-light text-dark border">Bootstrap v5.3.8</span>
    </div>
  </div>
</footer>
