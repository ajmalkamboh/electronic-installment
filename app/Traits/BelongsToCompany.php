<?php

namespace App\Traits;

use App\Models\Company;
use App\Scopes\CompanyScope;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Boot the BelongsToCompany trait.
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                /** @var TenantContext $tenantContext */
                $tenantContext = app(TenantContext::class);
                $companyId = $tenantContext->getCompanyId();

                if ($companyId) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    /**
     * Get the company that owns the record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope query to all companies (bypass tenant scope for platform admin / jobs).
     */
    public function scopeWithoutCompany(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }
}
