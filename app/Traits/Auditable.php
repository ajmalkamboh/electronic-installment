<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Hidden or sensitive attributes to exclude from audit diffs.
     */
    protected array $auditExcluded = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->recordModelAudit('created', null, $model->filterAuditAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }

            $old = [];
            $new = [];

            foreach ($dirty as $key => $value) {
                if ($model->shouldExcludeFromAudit($key)) {
                    continue;
                }
                $old[$key] = $model->getOriginal($key);
                $new[$key] = $value;
            }

            if (! empty($new)) {
                $model->recordModelAudit('updated', $old, $new);
            }
        });

        static::deleted(function (Model $model) {
            $model->recordModelAudit('deleted', $model->filterAuditAttributes($model->getOriginal()), null);
        });
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->orderByDesc('created_at');
    }

    /**
     * Record an audit event manually or automatically.
     */
    public function recordModelAudit(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $notes = null): ?AuditLog
    {
        // Don't record audits if running certain background CLI tasks unless needed
        $user = Auth::user();
        $companyId = $this->company_id ?? app(TenantContext::class)->getCompanyId() ?? $user?->company_id;

        return AuditLog::create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl() ?: 'cli',
            'ip_address' => Request::ip() ?: '127.0.0.1',
            'user_agent' => Request::userAgent() ?: 'System/CLI',
            'notes' => $notes,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Explicit public helper to record business workflow audits.
     */
    public function logAudit(string $action, ?string $notes = null, ?array $customOld = null, ?array $customNew = null): AuditLog
    {
        return $this->recordModelAudit($action, $customOld, $customNew, $notes);
    }

    protected function shouldExcludeFromAudit(string $attribute): bool
    {
        return in_array($attribute, $this->auditExcluded, true);
    }

    protected function filterAuditAttributes(array $attributes): array
    {
        return array_filter($attributes, fn ($key) => ! $this->shouldExcludeFromAudit($key), ARRAY_FILTER_USE_KEY);
    }
}
