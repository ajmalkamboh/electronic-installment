<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Query audit logs with rich filtering and pagination.
     *
     * @param  array<string, mixed>  $filters
     */
    public function queryLogs(array $filters = [], ?int $companyId = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with(['user:id,name,email', 'company:id,name'])
            ->latest('id');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        } elseif (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['event'])) {
            $query->where('action', $filters['event']);
        } elseif (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['auditable_type'])) {
            $type = $filters['auditable_type'];
            if (! str_contains($type, '\\')) {
                $type = 'App\\Models\\'.$type;
            }
            $query->where('auditable_type', $type);
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%'.$filters['ip_address'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Log a security or business event directly.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logEvent(
        string $event,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $userId = null,
        ?int $companyId = null
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? (auth()->check() ? auth()->user()->company_id : null),
            'user_id' => $userId ?? (auth()->check() ? auth()->id() : null),
            'action' => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * Helper to log authentication and security policy actions.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logSecurityAction(string $action, string $description, array $metadata = []): AuditLog
    {
        $payload = array_merge(['description' => $description], $metadata);

        return $this->logEvent(
            event: $action,
            model: null,
            oldValues: null,
            newValues: $payload
        );
    }

    /**
     * Get recent activity logs for dashboard display.
     *
     * @return Collection<int, AuditLog>
     */
    public function getRecentActivity(?int $companyId = null, int $limit = 10): Collection
    {
        $query = AuditLog::query()
            ->with(['user:id,name,email'])
            ->latest('id')
            ->limit($limit);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
