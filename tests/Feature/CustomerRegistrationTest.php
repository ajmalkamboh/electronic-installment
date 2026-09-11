<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Metro Appliances',
            'slug' => 'metro-appliances',
            'legal_name' => 'Metro Retail SMC-Pvt Ltd',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Hub',
            'code' => 'LHR-HUB',
            'is_main' => true,
            'status' => 'active',
        ]);

        $adminRole = Role::where('name', 'company_admin')->firstOrFail();

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $adminRole->id,
            'role' => 'company_admin',
            'name' => 'Metro Admin',
            'email' => 'admin@metroappliances.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->admin->roles()->sync([$adminRole->id]);
    }

    public function test_company_admin_can_view_customers_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Customer');
        $response->assertSee('Debtor Directory');
        $response->assertSee('Total Customers');
    }

    public function test_company_admin_can_register_customer_with_guarantor_and_reference(): void
    {
        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            // Customer
            'cnic' => '35201-1122334-1',
            'full_name' => 'Rashid Mehmood',
            'father_or_husband_name' => 'Muhammad Bashir',
            'gender' => 'male',
            'mobile_primary' => '0300-1122334',
            'whatsapp_number' => '0300-1122334',
            'present_address' => 'House 42, Street 5, Gulshan Ravi, Lahore',
            'residence_type' => 'owned',
            'residence_tenure_years' => 5,
            'monthly_household_income' => 85000.00,
            'utility_bill_ref_number' => 'LESCO-998877',

            // Guarantor
            'guarantor_name' => 'Tariq Mehmood',
            'guarantor_cnic' => '35201-9988776-3',
            'guarantor_relationship' => 'brother',
            'guarantor_mobile' => '0301-9988776',
            'guarantor_address' => 'Shop 10, Anarkali, Lahore',
            'guarantor_occupation' => 'Businessman',
            'guarantor_monthly_income' => 120000.00,

            // Reference
            'ref_name' => 'Kamran Shah',
            'ref_relationship' => 'Neighbor',
            'ref_mobile' => '0302-3344556',
            'ref_address' => 'House 40, Street 5, Gulshan Ravi',
        ]);

        $customer = Customer::where('cnic', '35201-1122334-1')->first();
        $this->assertNotNull($customer);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'cnic' => '35201-1122334-1',
            'full_name' => 'Rashid Mehmood',
            'status' => 'pending_verification',
        ]);

        // Assert Guarantor created
        $this->assertDatabaseHas('guarantors', [
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'cnic' => '35201-9988776-3',
            'full_name' => 'Tariq Mehmood',
            'relationship' => 'brother',
        ]);

        // Assert Reference created
        $this->assertDatabaseHas('references', [
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'full_name' => 'Kamran Shah',
        ]);

        // Assert Credit Profile initialized
        $this->assertDatabaseHas('customer_credit_profiles', [
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'credit_score' => 50,
            'max_authorized_credit' => 150000.00,
        ]);
    }

    public function test_cnic_must_be_unique_within_company(): void
    {
        Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-1234567-9',
            'full_name' => 'Existing Customer',
            'gender' => 'male',
            'mobile_primary' => '0300-1234567',
            'present_address' => 'Lahore',
            'residence_type' => 'owned',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'cnic' => '35201-1234567-9', // duplicate
            'full_name' => 'Duplicate Person',
            'gender' => 'male',
            'mobile_primary' => '0300-9999999',
            'present_address' => 'Lahore',
            'residence_type' => 'owned',
            'guarantor_name' => 'Guarantor',
            'guarantor_cnic' => '35201-5555555-5',
            'guarantor_relationship' => 'friend',
            'guarantor_mobile' => '0300-5555555',
            'guarantor_address' => 'Lahore',
        ]);

        $response->assertSessionHasErrors('cnic');
    }

    public function test_different_companies_can_register_same_cnic(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Apex Electronics',
            'slug' => 'apex-electronics',
            'status' => 'active',
        ]);

        Customer::create([
            'company_id' => $otherCompany->id,
            'cnic' => '35201-7777777-1',
            'full_name' => 'Citizen Customer',
            'gender' => 'male',
            'mobile_primary' => '0300-7777777',
            'present_address' => 'Multan',
            'residence_type' => 'owned',
            'status' => 'active',
        ]);

        // Registering same CNIC in $this->company must succeed
        $response = $this->actingAs($this->admin)->post(route('customers.store'), [
            'cnic' => '35201-7777777-1',
            'full_name' => 'Citizen Customer in Metro',
            'gender' => 'male',
            'mobile_primary' => '0300-7777777',
            'present_address' => 'Lahore',
            'residence_type' => 'rented',
            'guarantor_name' => 'Guarantor',
            'guarantor_cnic' => '35201-8888888-8',
            'guarantor_relationship' => 'father',
            'guarantor_mobile' => '0300-8888888',
            'guarantor_address' => 'Lahore',
        ]);

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'cnic' => '35201-7777777-1',
        ]);
    }

    public function test_company_admin_can_view_customer_show_dossier(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-4444444-1',
            'full_name' => 'Shahid Afridi',
            'gender' => 'male',
            'mobile_primary' => '0300-4444444',
            'present_address' => 'Mall Road, Lahore',
            'residence_type' => 'owned',
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.show', $customer));

        $response->assertStatus(200);
        $response->assertSee('Shahid Afridi');
        $response->assertSee('35201-4444444-1');
        $response->assertSee('Legal Guarantors');
        $response->assertSee('Field Verification Audits');
    }

    public function test_company_admin_can_update_customer_profile(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-5555555-1',
            'full_name' => 'Original Name',
            'gender' => 'male',
            'mobile_primary' => '0300-5555555',
            'present_address' => 'Old Address',
            'residence_type' => 'rented',
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($this->admin)->put(route('customers.update', $customer), [
            'cnic' => '35201-5555555-1',
            'full_name' => 'Updated Name',
            'gender' => 'male',
            'mobile_primary' => '0300-5555555',
            'present_address' => 'Brand New Address, Model Town',
            'residence_type' => 'owned',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'full_name' => 'Updated Name',
            'present_address' => 'Brand New Address, Model Town',
            'status' => 'active',
        ]);
    }

    public function test_company_admin_can_toggle_customer_status_and_blacklist(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-6666666-1',
            'full_name' => 'Delinquent Debtor',
            'gender' => 'male',
            'mobile_primary' => '0300-6666666',
            'present_address' => 'Lahore',
            'residence_type' => 'rented',
            'status' => 'active',
        ]);

        // Initialize credit profile
        $customer->creditProfile()->create([
            'company_id' => $this->company->id,
            'credit_score' => 50,
            'max_authorized_credit' => 150000.00,
        ]);

        // Blacklist customer
        $response = $this->actingAs($this->admin)->post(route('customers.toggle-status', $customer), [
            'status' => 'blacklisted',
            'blacklisted_reason' => 'Severe non-payment and default',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('blacklisted', $customer->fresh()->status);
        $this->assertEquals(0, $customer->fresh()->creditProfile->credit_score);
        $this->assertNotNull($customer->fresh()->creditProfile->blacklisted_at);
    }

    public function test_cannot_access_customer_of_another_company(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Another Company',
            'slug' => 'another-company',
            'status' => 'active',
        ]);

        $foreignCustomer = Customer::create([
            'company_id' => $otherCompany->id,
            'cnic' => '35201-0000000-0',
            'full_name' => 'Foreign Customer',
            'gender' => 'male',
            'mobile_primary' => '0300-0000000',
            'present_address' => 'Karachi',
            'residence_type' => 'owned',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('customers.show', $foreignCustomer));
        $response->assertStatus(404);

        $response = $this->actingAs($this->admin)->get(route('customers.edit', $foreignCustomer));
        $response->assertStatus(404);
    }
}
