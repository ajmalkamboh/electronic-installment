<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Security\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Display tenant-scoped audit trail.
     */
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $filters = $request->only(['event', 'auditable_type', 'date_from', 'date_to', 'search']);

        $logs = $this->auditLogService->queryLogs(
            filters: $filters,
            companyId: $companyId,
            perPage: 25
        );

        return view('tenant.security.audit_logs', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    /**
     * View detailed audit log item.
     */
    public function show(Request $request, AuditLog $auditLog): View
    {
        if ($auditLog->company_id !== $request->user()->company_id) {
            abort(403, 'Unauthorized access to company audit log entry.');
        }

        return view('tenant.security.audit_log_detail', [
            'log' => $auditLog->load(['user', 'company']),
        ]);
    }
}
