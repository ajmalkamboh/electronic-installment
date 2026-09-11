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

class BranchSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch1;
    protected Branch $branch2;
    protected Branch $inactiveBranch;
    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Premier Electronics',
            'slug' => 'premier-electronics',
            'status' => 'active',
        ]);

        $this->branch1 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Main Branch',
            'code' => 'LHR-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->branch2 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Faisalabad Branch',
            'code' => 'FSD-01',
            'is_main' => false,
            'status' => 'active',
        ]);

        $this->inactiveBranch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Closed Branch',
            'code' => 'CLS-01',
            'is_main' => false,
            'status' => 'inactive',
        ]);

        $this->adminUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Ajmal Admin',
            'email' => 'admin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        $this->staffUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Cashier Staff',
            'email' => 'cashier@installment.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_switch_branch_context(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/tenant/switch-branch', [
            'branch_id' => $this->branch2->id,
        ]);

        $response->assertSessionHas('active_branch_id', $this->branch2->id);

        // Next request inherits active_branch_id
        $dashResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_branch_id' => $this->branch2->id])
            ->get('/dashboard');

        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Faisalabad Branch');
        $dashResponse->assertSee('FSD-01');
    }

    public function test_cannot_switch_to_inactive_branch(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/tenant/switch-branch', [
            'branch_id' => $this->inactiveBranch->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_switch_to_another_company_branch(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor Electronics',
            'slug' => 'competitor',
            'status' => 'active',
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor Branch',
            'code' => 'CMP-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post('/tenant/switch-branch', [
            'branch_id' => $otherBranch->id,
        ]);

        $response->assertStatus(404);
    }

    public function test_branch_locked_staff_cannot_switch_to_other_branch(): void
    {
        $response = $this->actingAs($this->staffUser)->post('/tenant/switch-branch', [
            'branch_id' => $this->branch2->id,
        ]);

        $response->assertStatus(403);
    }
}
