<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Premier Electronics',
            'slug' => 'premier-electronics',
            'legal_name' => 'Premier Electronics SMC-Pvt Ltd',
            'currency' => 'PKR',
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

        $this->adminUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Ajmal Admin',
            'email' => 'admin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
        ]);

        $this->staffUser = User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Staff User',
            'email' => 'staff@installment.test',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_company_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/company/settings');

        $response->assertStatus(200);
        $response->assertSee('Premier Electronics');
        $response->assertSee('Corporate Profile');
    }

    public function test_admin_can_update_company_profile(): void
    {
        $response = $this->actingAs($this->adminUser)->put('/company/settings', [
            'name' => 'Premier Electronics Group',
            'legal_name' => 'Premier Electronics Holdings Pvt Ltd',
            'ntn_strn' => '9988776-5',
            'phone' => '+92 42 37219999',
            'email' => 'corporate@premierelectronics.pk',
            'city' => 'Lahore',
            'address' => 'Floor 4, Mall Tower, Lahore',
            'currency' => 'PKR',
            'receipt_header' => 'Specialist in Installment Financing',
            'receipt_footer' => 'Pay by 5th of every month. Thank you.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', [
            'id' => $this->company->id,
            'name' => 'Premier Electronics Group',
            'ntn_strn' => '9988776-5',
            'receipt_header' => 'Specialist in Installment Financing',
        ]);
    }

    public function test_non_admin_cannot_access_company_settings(): void
    {
        $response = $this->actingAs($this->staffUser)->get('/company/settings');

        $response->assertStatus(403);
    }
}
