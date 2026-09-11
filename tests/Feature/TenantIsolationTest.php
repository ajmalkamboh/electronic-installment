<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_context_binds_correctly(): void
    {
        $company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Alpha Electronics',
            'slug' => 'alpha-electronics',
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Alpha Branch',
            'code' => 'ALPHA-01',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);
        $context->setCompany($company);
        $context->setBranch($branch);

        $this->assertEquals($company->id, $context->getCompanyId());
        $this->assertEquals($branch->id, $context->getBranchId());
        $this->assertEquals('Alpha Electronics', $context->getCompany()->name);
    }

    public function test_tenant_isolation_protects_cross_company_data(): void
    {
        // Tenant A
        $companyA = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Company A',
            'slug' => 'company-a',
            'status' => 'active',
        ]);

        $branchA = Branch::create([
            'company_id' => $companyA->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Branch A',
            'code' => 'BR-A',
            'status' => 'active',
        ]);

        $userA = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'name' => 'User A',
            'email' => 'userA@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        // Tenant B
        $companyB = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Company B',
            'slug' => 'company-b',
            'status' => 'active',
        ]);

        $branchB = Branch::create([
            'company_id' => $companyB->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Branch B',
            'code' => 'BR-B',
            'status' => 'active',
        ]);

        // When User A accesses Dashboard, they see Company A and Branch A, never Company B
        $responseA = $this->actingAs($userA)->get('/dashboard');
        $responseA->assertStatus(200);
        $responseA->assertSee('Company A');
        $responseA->assertSee('Branch A');
        $responseA->assertDontSee('Company B');
        $responseA->assertDontSee('Branch B');

        // Verify branch counts are tenant-specific
        $this->assertEquals(1, Branch::where('company_id', $companyA->id)->count());
        $this->assertEquals(1, Branch::where('company_id', $companyB->id)->count());
    }
}
