<?php

namespace App\Scopes;

use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $companyId = $tenantContext->getCompanyId();

        if ($companyId !== null) {
            $builder->where($model->qualifyColumn('company_id'), '=', $companyId);
        }
    }
}
