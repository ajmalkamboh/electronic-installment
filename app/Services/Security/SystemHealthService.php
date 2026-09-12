<?php

namespace App\Services\Security;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealthService
{
    /**
     * Run full production diagnostic health check suite.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'backups' => $this->getBackupStatus(),
            'environment' => $this->checkEnvironment(),
            'php' => $this->checkPhpRuntime(),
        ];
    }

    /**
     * Check Database connectivity and latency.
     *
     * @return array<string, mixed>
     */
    public function checkDatabase(): array
    {
        $startTime = microtime(true);
        try {
            DB::select('SELECT 1');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'ok',
                'connection' => config('database.default'),
                'latency_ms' => $latencyMs,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage write ability and disk space.
     *
     * @return array<string, mixed>
     */
    public function checkStorage(): array
    {
        $storagePath = storage_path('app');
        $testFile = $storagePath.DIRECTORY_SEPARATOR.'health_check_probe_'.time().'.tmp';
        $writable = false;

        try {
            File::ensureDirectoryExists($storagePath);
            File::put($testFile, 'probe');
            $writable = File::exists($testFile) && File::get($testFile) === 'probe';
            if ($writable) {
                File::delete($testFile);
            }
        } catch (Exception) {
            $writable = false;
        }

        $freeBytes = @disk_free_space($storagePath);
        $totalBytes = @disk_total_space($storagePath);

        return [
            'status' => $writable ? 'ok' : 'error',
            'writable' => $writable,
            'free_space_gb' => $freeBytes !== false ? round($freeBytes / 1024 / 1024 / 1024, 2) : null,
            'total_space_gb' => $totalBytes !== false ? round($totalBytes / 1024 / 1024 / 1024, 2) : null,
        ];
    }

    /**
     * Check Cache readiness.
     *
     * @return array<string, mixed>
     */
    public function checkCache(): array
    {
        $probeKey = 'health_check_cache_'.time();
        try {
            Cache::put($probeKey, 'ok', 10);
            $cached = Cache::get($probeKey);
            Cache::forget($probeKey);

            return [
                'status' => $cached === 'ok' ? 'ok' : 'error',
                'driver' => config('cache.default'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve status of system backups.
     *
     * @return array<string, mixed>
     */
    public function getBackupStatus(): array
    {
        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');

        if (! File::exists($backupDir)) {
            return [
                'exists' => false,
                'count' => 0,
                'latest_backup' => null,
                'total_size_mb' => 0,
            ];
        }

        $files = File::files($backupDir);
        $count = count($files);
        $latest = null;
        $latestTimestamp = 0;
        $totalBytes = 0;

        foreach ($files as $file) {
            $totalBytes += $file->getSize();
            $mTime = $file->getMTime();
            if ($mTime > $latestTimestamp) {
                $latestTimestamp = $mTime;
                $latest = [
                    'filename' => $file->getFilename(),
                    'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                    'created_at' => Carbon::createFromTimestamp($mTime)->toIso8601String(),
                ];
            }
        }

        return [
            'exists' => true,
            'count' => $count,
            'latest_backup' => $latest,
            'total_size_mb' => round($totalBytes / 1024 / 1024, 2),
        ];
    }

    /**
     * Check deployment environment security posture.
     *
     * @return array<string, mixed>
     */
    public function checkEnvironment(): array
    {
        $env = config('app.env');
        $debug = config('app.debug');

        return [
            'env' => $env,
            'debug' => $debug,
            'is_production_safe' => ($env === 'production' && $debug === false) || ($env !== 'production'),
            'https' => request()->secure(),
        ];
    }

    /**
     * Check PHP extensions and runtime.
     *
     * @return array<string, mixed>
     */
    public function checkPhpRuntime(): array
    {
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'curl', 'json', 'bcmath', 'fileinfo'];
        $missing = [];

        foreach ($requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        return [
            'version' => PHP_VERSION,
            'extensions_ok' => empty($missing),
            'missing_extensions' => $missing,
        ];
    }
}
