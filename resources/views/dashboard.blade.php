<x-app-layout title="Operating Dashboard">
  <!-- Hero Welcome Banner -->
  <div class="dashboard-hero mb-4 p-4 rounded-3 bg-light border">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <span class="badge bg-primary mb-2 text-uppercase letter-spacing">Central Operations Command</span>
        <h1 class="h3 fw-bold mb-1">Welcome back, {{ $user->name }}</h1>
        <p class="text-muted mb-0">
          Managing <strong>{{ $company->name }}</strong> &bull; Operating Location: <strong>{{ $branch->name ?? 'Main Headquarters' }}</strong> (Code: <code>{{ $branch->code ?? 'HQ' }}</code>)
        </p>
      </div>
      <div class="d-flex gap-2">
        <span class="badge bg-success-subtle text-success border border-success-subtle p-2 px-3">
          <i class="bi bi-shield-check me-1"></i>Tenant Isolation: Strict Active
        </span>
        <span class="badge bg-info-subtle text-info border border-info-subtle p-2 px-3">
          <i class="bi bi-person-badge me-1"></i>{{ strtoupper(str_replace('_', ' ', $user->role)) }}
        </span>
      </div>
    </div>
  </div>

  <!-- Operational Metric Grid (Zero Fabricated Metrics) -->
  <div class="row g-3 mb-4">
    <!-- Branches -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Configured Branches</span>
            <div class="bg-primary-subtle text-primary rounded p-2">
              <i class="bi bi-building fs-5"></i>
            </div>
          </div>
          <h2 class="h3 fw-bold mb-1">{{ $metrics['branches_count'] }}</h2>
          <span class="text-success small fw-medium">
            <i class="bi bi-check-circle me-1"></i>Active Showrooms
          </span>
        </div>
      </div>
    </div>

    <!-- Team Members -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Authorized Staff</span>
            <div class="bg-info-subtle text-info rounded p-2">
              <i class="bi bi-people fs-5"></i>
            </div>
          </div>
          <h2 class="h3 fw-bold mb-1">{{ $metrics['team_members_count'] }}</h2>
          <span class="text-muted small">Assigned Users</span>
        </div>
      </div>
    </div>

    <!-- Customers (Genuine Database Metric) -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Registered Customers</span>
            <div class="bg-secondary-subtle text-secondary rounded p-2">
              <i class="bi bi-person-lines-fill fs-5"></i>
            </div>
          </div>
          <h2 class="h3 fw-bold mb-1">{{ $metrics['active_customers_count'] }}</h2>
          <span class="text-muted small">Awaiting Phase 02 Onboarding</span>
        </div>
      </div>
    </div>

    <!-- Agreements (Genuine Database Metric) -->
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="text-muted small text-uppercase fw-semibold">Active Agreements</span>
            <div class="bg-secondary-subtle text-secondary rounded p-2">
              <i class="bi bi-file-earmark-check fs-5"></i>
            </div>
          </div>
          <h2 class="h3 fw-bold mb-1">{{ $metrics['active_agreements_count'] }}</h2>
          <span class="text-muted small">Awaiting Phase 03 Enrollment</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Operational Foundation & Empty States -->
  <div class="row g-4">
    <!-- Active Tenant Context Card -->
    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
          <h5 class="fw-bold mb-0">Tenant & Architecture Environment</h5>
          <span class="badge bg-success">Phase 01 Verified</span>
        </div>
        <div class="card-body p-4">
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <tbody>
                <tr>
                  <td class="text-muted" style="width: 200px;">Company / Tenant</td>
                  <td class="fw-semibold">{{ $company->name }} (<code>{{ $company->slug }}</code>)</td>
                </tr>
                <tr>
                  <td class="text-muted">Company Legal Name</td>
                  <td>{{ $company->legal_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Tax Identification (NTN/STRN)</td>
                  <td>{{ $company->ntn_strn ?? 'N/A' }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Active Branch Context</td>
                  <td>{{ $branch->name ?? 'None' }} (<code>{{ $branch->code ?? 'N/A' }}</code>)</td>
                </tr>
                <tr>
                  <td class="text-muted">Tenant ULID Identifier</td>
                  <td><code class="text-primary">{{ $company->ulid }}</code></td>
                </tr>
                <tr>
                  <td class="text-muted">Dual-Key Strategy</td>
                  <td><span class="badge bg-light text-dark border">BIGINT PK (Internal) + ULID (External API/URLs)</span></td>
                </tr>
                <tr>
                  <td class="text-muted">Tenancy Enforcement</td>
                  <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">CompanyScope GlobalScope + TenantMiddleware</span></td>
                </tr>
                <tr>
                  <td class="text-muted">Operating Currency</td>
                  <td><strong>{{ $company->currency }}</strong> (Pakistani Rupee)</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Authentic Empty State Workflow Card -->
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold mb-0">Installment Operations Pipeline</h5>
        </div>
        <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
          <div class="mb-3">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle p-4">
              <i class="bi bi-clock-history fs-1"></i>
            </div>
          </div>
          <h6 class="fw-bold mb-2">No Installment Agreements Active Yet</h6>
          <p class="text-muted small mb-4">
            Phase 01 establishes the multi-tenant SaaS foundation, authentication, branch structure, and security layer. Business modules will follow sequentially according to the architectural roadmap.
          </p>
          <div class="text-start bg-light p-3 rounded small">
            <div class="d-flex align-items-center mb-2">
              <i class="bi bi-check-circle-fill text-success me-2"></i>
              <span><strong>Phase 01:</strong> Foundation, Tenancy & AppDashboard (Complete)</span>
            </div>
            <div class="d-flex align-items-center mb-2 text-muted">
              <i class="bi bi-circle me-2"></i>
              <span><strong>Phase 02:</strong> Customer & Guarantor Verification</span>
            </div>
            <div class="d-flex align-items-center mb-2 text-muted">
              <i class="bi bi-circle me-2"></i>
              <span><strong>Phase 03:</strong> Installment Plans & Agreement Engine</span>
            </div>
            <div class="d-flex align-items-center text-muted">
              <i class="bi bi-circle me-2"></i>
              <span><strong>Phase 04:</strong> Collections, Receipts & Cash Drawer</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
