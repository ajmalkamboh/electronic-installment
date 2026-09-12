<?php

namespace Tests\Unit;

use App\Services\Security\SystemHealthService;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    public function test_system_health_returns_all_subsystem_checks(): void
    {
        $service = new SystemHealthService;
        $health = $service->getSystemHealth();

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('database', $health);
        $this->assertArrayHasKey('storage', $health);
        $this->assertArrayHasKey('cache', $health);
        $this->assertArrayHasKey('backups', $health);
        $this->assertArrayHasKey('environment', $health);
        $this->assertArrayHasKey('php', $health);

        $this->assertEquals('ok', $health['database']['status']);
        $this->assertTrue($health['storage']['writable']);
        $this->assertEquals('ok', $health['cache']['status']);
        $this->assertTrue($health['php']['extensions_ok']);
    }
}
