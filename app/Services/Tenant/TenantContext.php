<?php

namespace App\Services\Tenant;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;

class TenantContext
{
    protected ?Company $company = null;
    protected ?Branch $branch = null;

    /**
     * Set the current active company.
     */
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Get the current active company.
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * Get the current active company ID.
     */
    public function getCompanyId(): ?int
    {
        return $this->company?->id;
    }

    /**
     * Set the current active branch.
     */
    public function setBranch(?Branch $branch): void
    {
        $this->branch = $branch;
    }

    /**
     * Get the current active branch.
     */
    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    /**
     * Get the current active branch ID.
     */
    public function getBranchId(): ?int
    {
        return $this->branch?->id;
    }

    /**
     * Initialize context from an authenticated user.
     */
    public function initializeForUser(User $user): void
    {
        if ($user->company) {
            $this->setCompany($user->company);
        }

        if ($user->branch) {
            $this->setBranch($user->branch);
        }
    }

    /**
     * Clear the tenant context.
     */
    public function clear(): void
    {
        $this->company = null;
        $this->branch = null;
    }
}
