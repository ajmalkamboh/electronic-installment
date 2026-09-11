<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Test Tech Ltd',
            'slug' => 'test-tech',
            'legal_name' => 'Test Tech Private Limited',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Gulberg Branch',
            'code' => 'GLB-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $this->managerRole = Role::where('name', 'branch_manager')->firstOrFail();

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Master Admin',
            'email' => 'admin@testtech.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'employee_code' => 'EMP-001',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
    }

    public function test_company_admin_can_view_staff_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('staff.index'));

        $response->assertStatus(200);
        $response->assertSee('Staff & Employee Management', false);
        $response->assertSee('Master Admin');
        $response->assertSee('EMP-001');
    }

    public function test_company_admin_can_create_staff_member(): void
    {
        $response = $this->actingAs($this->admin)->post(route('staff.store'), [
            'name' => 'Zubair Cashier',
            'email' => 'zubair@testtech.pk',
            'employee_code' => 'EMP-002',
            'cnic' => '35201-9876543-1',
            'phone' => '+92 300 9876543',
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'designation' => 'Assistant Manager',
            'joining_date' => '2026-03-01',
            'salary' => 75000,
            'password' => 'secretPass123',
            'password_confirmation' => 'secretPass123',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'company_id' => $this->company->id,
            'email' => 'zubair@testtech.pk',
            'employee_code' => 'EMP-002',
            'designation' => 'Assistant Manager',
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'status' => 'active',
        ]);
    }

    public function test_employee_code_must_be_unique_within_company(): void
    {
        $response = $this->actingAs($this->admin)->post(route('staff.store'), [
            'name' => 'Duplicate Code Employee',
            'email' => 'duplicate@testtech.pk',
            'employee_code' => 'EMP-001', // already used by $this->admin
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'password' => 'secretPass123',
            'password_confirmation' => 'secretPass123',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('employee_code');
    }

    public function test_different_companies_can_use_same_employee_code(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Other Retail',
            'slug' => 'other-retail',
            'status' => 'active',
        ]);

        $userInOtherCompany = User::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Admin',
            'email' => 'other@otherretail.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'employee_code' => 'EMP-002', // EMP-002 in other company
        ]);

        // Same EMP-002 should succeed in $this->company
        $response = $this->actingAs($this->admin)->post(route('staff.store'), [
            'name' => 'Staff in First Company',
            'email' => 'first@testtech.pk',
            'employee_code' => 'EMP-002',
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'password' => 'secretPass123',
            'password_confirmation' => 'secretPass123',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('users', [
            'company_id' => $this->company->id,
            'employee_code' => 'EMP-002',
        ]);
    }

    public function test_company_admin_can_update_staff_and_transfer_branch(): void
    {
        $branch2 = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'DHA Branch',
            'code' => 'DHA-01',
            'status' => 'active',
        ]);

        $staff = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'role' => 'branch_manager',
            'name' => 'Transferable Staff',
            'email' => 'transfer@testtech.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'employee_code' => 'EMP-003',
        ]);

        $response = $this->actingAs($this->admin)->put(route('staff.update', $staff), [
            'name' => 'Transferable Staff Updated',
            'email' => 'transfer@testtech.pk',
            'employee_code' => 'EMP-003',
            'branch_id' => $branch2->id, // Transferred to branch2
            'role_id' => $this->managerRole->id,
            'designation' => 'Branch Lead',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'branch_id' => $branch2->id,
            'designation' => 'Branch Lead',
        ]);
    }

    public function test_company_admin_can_toggle_staff_status(): void
    {
        $staff = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $this->managerRole->id,
            'role' => 'branch_manager',
            'name' => 'Toggled Staff',
            'email' => 'toggle@testtech.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'employee_code' => 'EMP-004',
        ]);

        // Toggle from active -> suspended
        $response = $this->actingAs($this->admin)->post(route('staff.toggle-status', $staff));
        $response->assertSessionHas('success');
        $this->assertEquals('suspended', $staff->fresh()->status);

        // Toggle from suspended -> active
        $response = $this->actingAs($this->admin)->post(route('staff.toggle-status', $staff));
        $response->assertSessionHas('success');
        $this->assertEquals('active', $staff->fresh()->status);
    }

    public function test_admin_cannot_suspend_self(): void
    {
        $response = $this->actingAs($this->admin)->post(route('staff.toggle-status', $this->admin));

        $response->assertSessionHasErrors('error');
        $this->assertEquals('active', $this->admin->fresh()->status);
    }

    public function test_cannot_manage_staff_of_another_company(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor',
            'slug' => 'competitor',
            'status' => 'active',
        ]);

        $foreignStaff = User::create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign User',
            'email' => 'foreign@other.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('staff.edit', $foreignStaff));
        $response->assertStatus(404);

        $response = $this->actingAs($this->admin)->put(route('staff.update', $foreignStaff), [
            'name' => 'Hacked Name',
            'email' => 'foreign@other.pk',
            'role_id' => $this->managerRole->id,
            'status' => 'active',
        ]);
        $response->assertStatus(404);
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $staff = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Password Reset User',
            'email' => 'resetme@testtech.pk',
            'password' => Hash::make('oldPassword1'),
            'status' => 'active',
            'employee_code' => 'EMP-005',
        ]);

        $response = $this->actingAs($this->admin)->post(route('staff.reset-password', $staff), [
            'password' => 'newSecretPass2026',
            'password_confirmation' => 'newSecretPass2026',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newSecretPass2026', $staff->fresh()->password));
    }
}
