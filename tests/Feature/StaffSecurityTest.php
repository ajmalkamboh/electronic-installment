<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Secure Retail',
            'slug' => 'secure-retail',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Outlet',
            'code' => 'OUT-01',
            'is_main' => true,
            'status' => 'active',
        ]);
    }

    public function test_suspended_staff_cannot_login(): void
    {
        $suspendedUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Suspended Staff',
            'email' => 'blocked@secureretail.pk',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'status' => 'suspended',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'blocked@secureretail.pk',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_suspended_staff_is_ejected_mid_session(): void
    {
        $activeUser = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Active Staff',
            'email' => 'active@secureretail.pk',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'status' => 'active',
        ]);

        // First request is authorized
        $response = $this->actingAs($activeUser)->get(route('dashboard'));
        $response->assertStatus(200);

        // Administrator suspends the user mid-session
        $activeUser->update(['status' => 'suspended']);

        // Next request immediately ejects the user
        $subsequentResponse = $this->actingAs($activeUser)->get(route('dashboard'));
        $subsequentResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_inactive_company_users_are_ejected_mid_session(): void
    {
        $user = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Company User',
            'email' => 'user@secureretail.pk',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);

        // Deactivate company
        $this->company->update(['status' => 'suspended']);

        $subsequentResponse = $this->actingAs($user)->get(route('dashboard'));
        $subsequentResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
