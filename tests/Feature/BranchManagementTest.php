<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $mainBranch;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Premier Electronics',
            'slug' => 'premier-electronics',
            'status' => 'active',
        ]);

        $this->mainBranch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Main Branch',
            'code' => 'LHR-01',
            'city' => 'Lahore',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->mainBranch->id,
            'name' => 'Ajmal Admin',
            'email' => 'admin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_branch_listing(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/branches');

        $response->assertStatus(200);
        $response->assertSee('Lahore Main Branch');
        $response->assertSee('LHR-01');
    }

    public function test_admin_can_create_new_branch(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/branches', [
            'name' => 'Rawalpindi Saddar Branch',
            'code' => 'RWP-01',
            'city' => 'Rawalpindi',
            'address' => 'Shop 4-5, Saddar Bazaar',
            'phone' => '+92 51 5550001',
            'email' => 'rwp@premierelectronics.pk',
            'is_main' => false,
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'company_id' => $this->company->id,
            'code' => 'RWP-01',
            'name' => 'Rawalpindi Saddar Branch',
            'status' => 'active',
        ]);
    }

    public function test_branch_code_must_be_unique_within_same_company(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/branches', [
            'name' => 'Duplicate Code Branch',
            'code' => 'LHR-01', // Already exists in Premier Electronics
            'city' => 'Lahore',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_identical_branch_code_allowed_in_different_company(): void
    {
        // Other Company
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Other Retailer',
            'slug' => 'other-retailer',
            'status' => 'active',
        ]);

        $otherAdmin = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $otherCompany->id,
            'name' => 'Other Admin',
            'email' => 'other@retailer.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        // Other company can create LHR-01 without collision
        $response = $this->actingAs($otherAdmin)->post('/branches', [
            'name' => 'Other Lahore Outlet',
            'code' => 'LHR-01',
            'city' => 'Lahore',
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'company_id' => $otherCompany->id,
            'code' => 'LHR-01',
        ]);
    }

    public function test_admin_can_update_branch(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Old Branch Name',
            'code' => 'OLD-01',
            'city' => 'Old City',
            'is_main' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->put("/branches/{$branch->id}", [
            'name' => 'Updated Branch Name',
            'code' => 'NEW-01',
            'city' => 'New City',
            'status' => 'active',
            'is_main' => false,
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch Name',
            'code' => 'NEW-01',
        ]);
    }

    public function test_cannot_deactivate_main_headquarters_branch(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/branches/{$this->mainBranch->id}/toggle-status");

        $response->assertSessionHas('error');
        $this->assertEquals('active', $this->mainBranch->fresh()->status);
    }

    public function test_non_main_branch_can_be_deactivated_and_reactivated(): void
    {
        $branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Secondary Branch',
            'code' => 'SEC-01',
            'is_main' => false,
            'status' => 'active',
        ]);

        // Deactivate
        $this->actingAs($this->adminUser)->post("/branches/{$branch->id}/toggle-status");
        $this->assertEquals('inactive', $branch->fresh()->status);

        // Reactivate
        $this->actingAs($this->adminUser)->post("/branches/{$branch->id}/toggle-status");
        $this->assertEquals('active', $branch->fresh()->status);
    }
}
