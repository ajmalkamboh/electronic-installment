<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected User $cashierUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'RBAC Holdings',
            'slug' => 'rbac-holdings',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Central Hub',
            'code' => 'CENTRAL-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();
        $cashierRole = Role::where('name', 'cashier')->firstOrFail();

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'RBAC Admin',
            'email' => 'admin@rbacholdings.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->cashierUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $cashierRole->id,
            'role' => 'cashier',
            'name' => 'Cashier Employee',
            'email' => 'cashier@rbacholdings.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->cashierUser->roles()->sync([$cashierRole->id]);
    }

    public function test_predefined_system_roles_exist_with_permissions(): void
    {
        $expectedRoles = [
            'company_admin',
            'branch_manager',
            'credit_officer',
            'cashier',
            'collection_officer',
            'accountant',
        ];

        foreach ($expectedRoles as $roleSlug) {
            $role = Role::where('name', $roleSlug)->first();
            $this->assertNotNull($role, "Role {$roleSlug} should exist");
            $this->assertTrue($role->is_system, "Role {$roleSlug} should be flagged as system");
            $this->assertGreaterThan(0, $role->permissions()->count(), "Role {$roleSlug} should have permissions");
        }
    }

    public function test_user_has_permissions_derived_from_role(): void
    {
        // Cashier has payments.collect, but NOT staff.create
        $this->assertTrue($this->cashierUser->hasPermissionTo('payments.collect'));
        $this->assertFalse($this->cashierUser->hasPermissionTo('staff.create'));

        // Admin has universal authority
        $this->assertTrue($this->admin->hasPermissionTo('staff.create'));
        $this->assertTrue($this->admin->hasPermissionTo('payments.collect'));
    }

    public function test_gate_allows_permission_check(): void
    {
        $this->assertTrue(Gate::forUser($this->cashierUser)->allows('payments.collect'));
        $this->assertFalse(Gate::forUser($this->cashierUser)->allows('staff.create'));

        $this->assertTrue(Gate::forUser($this->admin)->allows('staff.create'));
    }

    public function test_company_admin_can_view_roles_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertSee('Access Permissions');
        $response->assertSee('Company Administrator');
        $response->assertSee('Cashier');
    }

    public function test_company_admin_can_create_custom_role(): void
    {
        $perms = Permission::whereIn('name', ['customers.view', 'customers.create'])->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->post(route('roles.store'), [
            'display_name' => 'KYC Verifier',
            'description' => 'Dedicated KYC and document verification agent',
            'permissions' => $perms,
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('roles', [
            'company_id' => $this->company->id,
            'name' => 'kyc_verifier',
            'display_name' => 'KYC Verifier',
            'is_system' => false,
        ]);

        $customRole = Role::where('name', 'kyc_verifier')->firstOrFail();
        $this->assertEquals(2, $customRole->permissions()->count());
    }

    public function test_company_admin_can_update_custom_role_permissions(): void
    {
        $customRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'inventory_auditor',
            'display_name' => 'Inventory Auditor',
            'is_system' => false,
        ]);

        $newPerms = Permission::whereIn('name', ['inventory.view', 'inventory.manage'])->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->put(route('roles.update', $customRole), [
            'display_name' => 'Senior Inventory Auditor',
            'description' => 'Audits all serialized products',
            'permissions' => $newPerms,
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', [
            'id' => $customRole->id,
            'display_name' => 'Senior Inventory Auditor',
        ]);
        $this->assertEquals(2, $customRole->fresh()->permissions()->count());
    }

    public function test_cannot_delete_system_role(): void
    {
        $systemRole = Role::where('name', 'cashier')->firstOrFail();

        $response = $this->actingAs($this->admin)->delete(route('roles.destroy', $systemRole));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_cannot_delete_custom_role_with_assigned_users(): void
    {
        $customRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'receptionist',
            'display_name' => 'Front Desk',
            'is_system' => false,
        ]);

        User::create([
            'company_id' => $this->company->id,
            'role_id' => $customRole->id,
            'role' => 'receptionist',
            'name' => 'Front Desk Staff',
            'email' => 'desk@rbacholdings.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('roles.destroy', $customRole));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_can_delete_unassigned_custom_role(): void
    {
        $customRole = Role::create([
            'company_id' => $this->company->id,
            'name' => 'temporary_role',
            'display_name' => 'Temporary Role',
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('roles.destroy', $customRole));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }
}
