<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    public function test_backup_command_executes_successfully(): void
    {
        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');

        $exitCode = Artisan::call('system:backup-database', ['--clean-days' => 15]);

        $this->assertEquals(0, $exitCode);
        $this->assertTrue(File::exists($backupDir));

        $files = File::files($backupDir);
        $this->assertNotEmpty($files);
    }
}
