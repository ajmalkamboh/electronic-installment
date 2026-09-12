<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_owasp_security_headers_are_present_on_web_requests(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    public function test_model_lifecycle_automatically_creates_audit_log(): void
    {
        $company = Company::create([
            'name' => 'Auto Audit Co',
            'code' => 'AACO',
            'status' => 'active',
            'subdomain' => 'autoaudit',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Muhammad Ali',
            'cnic' => '35201-1111111-1',
            'mobile_primary' => '03001234567',
            'present_address' => '123 Main Road, Lahore',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => 'created',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);

        // Test update audit trail
        $customer->update(['full_name' => 'Muhammad Ali Khan']);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => 'updated',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_tenant_can_view_own_audit_logs_but_cannot_access_other_tenant_entry(): void
    {
        $companyA = Company::create(['name' => 'Alpha Corp', 'code' => 'ALPH', 'status' => 'active', 'subdomain' => 'alpha']);
        $companyB = Company::create(['name' => 'Beta Corp', 'code' => 'BETA', 'status' => 'active', 'subdomain' => 'beta']);

        $userA = User::factory()->create(['company_id' => $companyA->id]);

        $logA = AuditLog::create([
            'company_id' => $companyA->id,
            'action' => 'created',
            'auditable_type' => Customer::class,
            'auditable_id' => 10,
        ]);

        $logB = AuditLog::create([
            'company_id' => $companyB->id,
            'action' => 'created',
            'auditable_type' => Customer::class,
            'auditable_id' => 20,
        ]);

        $this->actingAs($userA);

        // Tenant can view their own log index
        $indexResponse = $this->get(route('tenant.security.audit-logs'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Audit Trail');

        // Tenant can view their own entry
        $showResponse = $this->get(route('tenant.security.audit-logs.show', $logA->id));
        $showResponse->assertStatus(200);

        // Tenant forbidden from viewing other company's log entry
        $forbiddenResponse = $this->get(route('tenant.security.audit-logs.show', $logB->id));
        $forbiddenResponse->assertStatus(403);
    }

    public function test_super_admin_can_access_platform_audit_and_system_health(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@installment.test',
            'role' => 'super_admin',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('admin.audit-logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Platform Audit Trail');

        $healthResponse = $this->get(route('admin.health.index'));
        $healthResponse->assertStatus(200);
        $healthResponse->assertSee('Production Diagnostics');

        $backupResponse = $this->post(route('admin.backup.trigger'));
        $backupResponse->assertRedirect();
        $backupResponse->assertSessionHas('status');
    }
}
