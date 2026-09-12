<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Security\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService;
    }

    public function test_can_log_event_and_retrieve_via_service(): void
    {
        $company = Company::create([
            'name' => 'Audit Test Co',
            'code' => 'ATCO',
            'status' => 'active',
            'subdomain' => 'attest',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $log = $this->service->logEvent(
            event: 'payment_received',
            model: null,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'completed', 'amount' => 5000],
            ipAddress: '192.168.1.50',
            userAgent: 'Mozilla/5.0 Test',
            userId: $user->id,
            companyId: $company->id
        );

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('payment_received', $log->event);
        $this->assertEquals($company->id, $log->company_id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals('192.168.1.50', $log->ip_address);
        $this->assertEquals(['status' => 'completed', 'amount' => 5000], $log->new_values);

        // Test querying
        $results = $this->service->queryLogs(
            filters: ['event' => 'payment_received'],
            companyId: $company->id
        );

        $this->assertCount(1, $results->items());
    }

    public function test_audit_logs_are_immutable(): void
    {
        $log = AuditLog::create([
            'event' => 'created',
            'ip_address' => '127.0.0.1',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records are strictly immutable');

        $log->update(['event' => 'tampered']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $log = AuditLog::create([
            'event' => 'created',
            'ip_address' => '127.0.0.1',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AuditLog records cannot be deleted');

        $log->delete();
    }

    public function test_diff_accessor_identifies_changed_attributes(): void
    {
        $log = AuditLog::create([
            'event' => 'updated',
            'old_values' => ['status' => 'draft', 'price' => 100],
            'new_values' => ['status' => 'active', 'price' => 120],
        ]);

        $diff = $log->diff;

        $this->assertArrayHasKey('status', $diff);
        $this->assertEquals('draft', $diff['status']['old']);
        $this->assertEquals('active', $diff['status']['new']);

        $this->assertArrayHasKey('price', $diff);
        $this->assertEquals(100, $diff['price']['old']);
        $this->assertEquals(120, $diff['price']['new']);
    }

    public function test_tenant_isolation_in_audit_query(): void
    {
        $company1 = Company::create(['name' => 'Company One', 'code' => 'CO1', 'status' => 'active', 'subdomain' => 'co1']);
        $company2 = Company::create(['name' => 'Company Two', 'code' => 'CO2', 'status' => 'active', 'subdomain' => 'co2']);

        AuditLog::create([
            'company_id' => $company1->id,
            'event' => 'action_comp_1',
        ]);

        AuditLog::create([
            'company_id' => $company2->id,
            'event' => 'action_comp_2',
        ]);

        $company1Logs = $this->service->queryLogs([], companyId: $company1->id);
        $this->assertCount(1, $company1Logs->items());
        $this->assertEquals('action_comp_1', $company1Logs->items()[0]->event);

        $allLogs = $this->service->queryLogs([], companyId: null);
        $this->assertCount(2, $allLogs->items());
    }
}
