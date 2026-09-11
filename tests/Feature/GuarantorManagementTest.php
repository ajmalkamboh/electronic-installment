<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuarantorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Royal Appliances',
            'slug' => 'royal-appliances',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Showroom',
            'code' => 'ROYAL-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Royal Admin',
            'email' => 'admin@royalappliances.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-1111111-1',
            'full_name' => 'Ali Raza',
            'gender' => 'male',
            'mobile_primary' => '0300-1111111',
            'present_address' => 'Samanabad, Lahore',
            'residence_type' => 'owned',
            'status' => 'pending_verification',
        ]);
    }

    public function test_can_add_guarantor_to_customer_dossier(): void
    {
        $response = $this->actingAs($this->admin)->post(route('customers.guarantors.store', $this->customer), [
            'cnic' => '35201-2222222-2',
            'full_name' => 'Usman Raza',
            'relationship' => 'brother',
            'mobile' => '0301-2222222',
            'address' => 'Samanabad, Lahore',
            'occupation' => 'Software Engineer',
            'employer_name' => 'Tech Corp',
            'monthly_income' => 150000.00,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('guarantors', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'cnic' => '35201-2222222-2',
            'full_name' => 'Usman Raza',
            'relationship' => 'brother',
            'is_verified' => false,
        ]);
    }

    public function test_customer_cannot_be_their_own_guarantor(): void
    {
        $response = $this->actingAs($this->admin)->post(route('customers.guarantors.store', $this->customer), [
            'cnic' => '35201-1111111-1', // Same as $this->customer->cnic
            'full_name' => 'Ali Raza',
            'relationship' => 'self',
            'mobile' => '0300-1111111',
            'address' => 'Samanabad',
        ]);

        $response->assertSessionHasErrors('cnic');
    }

    public function test_can_toggle_guarantor_verification_status(): void
    {
        $guarantor = Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'cnic' => '35201-3333333-3',
            'full_name' => 'Waqas Ahmad',
            'relationship' => 'friend',
            'mobile' => '0302-3333333',
            'address' => 'Gulberg, Lahore',
            'is_verified' => false,
        ]);

        // Toggle to true
        $response = $this->actingAs($this->admin)->post(route('guarantors.toggle-verified', $guarantor));
        $response->assertSessionHas('success');
        $this->assertTrue($guarantor->fresh()->is_verified);

        // Toggle back to false
        $response = $this->actingAs($this->admin)->post(route('guarantors.toggle-verified', $guarantor));
        $response->assertSessionHas('success');
        $this->assertFalse($guarantor->fresh()->is_verified);
    }

    public function test_can_remove_guarantor_from_dossier(): void
    {
        $guarantor = Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'cnic' => '35201-4444444-4',
            'full_name' => 'Deletable Guarantor',
            'relationship' => 'colleague',
            'mobile' => '0303-4444444',
            'address' => 'Lahore',
            'is_verified' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('guarantors.destroy', $guarantor));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('guarantors', ['id' => $guarantor->id]);
    }

    public function test_cannot_manage_guarantors_of_another_company(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Foreign Corp',
            'slug' => 'foreign-corp',
            'status' => 'active',
        ]);

        $foreignCustomer = Customer::create([
            'company_id' => $otherCompany->id,
            'cnic' => '35201-9999999-9',
            'full_name' => 'Foreign Customer',
            'gender' => 'male',
            'mobile_primary' => '0300-9999999',
            'present_address' => 'Rawalpindi',
            'residence_type' => 'owned',
            'status' => 'active',
        ]);

        $foreignGuarantor = Guarantor::create([
            'company_id' => $otherCompany->id,
            'customer_id' => $foreignCustomer->id,
            'cnic' => '35201-8888888-8',
            'full_name' => 'Foreign Guarantor',
            'relationship' => 'brother',
            'mobile' => '0301-8888888',
            'address' => 'Rawalpindi',
            'is_verified' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('guarantors.toggle-verified', $foreignGuarantor));
        $response->assertStatus(404);

        $response = $this->actingAs($this->admin)->delete(route('guarantors.destroy', $foreignGuarantor));
        $response->assertStatus(404);
    }
}
