<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Services\Security\AuditLogService;
use App\Services\Security\SystemHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SuperAdminAuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected SystemHealthService $healthService
    ) {}

    /**
     * Display platform-wide audit logs with company filter.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['company_id', 'event', 'auditable_type', 'user_id', 'date_from', 'date_to', 'search']);

        $logs = $this->auditLogService->queryLogs(
            filters: $filters,
            companyId: null,
            perPage: 30
        );

        $companies = Company::orderBy('name')->get(['id', 'name']);

        return view('admin.audit_logs.index', [
            'logs' => $logs,
            'companies' => $companies,
            'filters' => $filters,
        ]);
    }

    /**
     * View detailed audit log item.
     */
    public function show(AuditLog $auditLog): View
    {
        return view('admin.audit_logs.show', [
            'log' => $auditLog->load(['user', 'company']),
        ]);
    }

    /**
     * Display system health and production diagnostics.
     */
    public function systemHealth(): View
    {
        $diagnostics = $this->healthService->getSystemHealth();

        return view('admin.health.index', [
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * Manually trigger database snapshot backup from super admin.
     */
    public function triggerBackup(): RedirectResponse
    {
        try {
            Artisan::call('system:backup-database', ['--clean-days' => 30]);
            $output = Artisan::output();

            return back()->with('status', 'Database backup snapshot generated successfully. '.trim($output));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate database backup: '.$e->getMessage());
        }
    }
}
