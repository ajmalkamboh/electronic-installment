<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Premier Electronics',
            'slug' => 'premier-electronics',
            'status' => 'active',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Main',
            'code' => 'LHR-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $this->user = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ajmal Admin',
            'email' => 'admin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@installment.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@installment.test',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_user_cannot_authenticate(): void
    {
        $suspendedUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Suspended Staff',
            'email' => 'suspended@installment.test',
            'password' => Hash::make('password'),
            'role' => 'viewer',
            'status' => 'suspended',
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@installment.test',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Premier Electronics');
        $response->assertSee('Lahore Main');
        $response->assertSee('Operating Dashboard');
    }

    public function test_user_can_logout(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}
