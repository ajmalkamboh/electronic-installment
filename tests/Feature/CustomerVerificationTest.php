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

class CustomerVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $officer;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'United Electronics',
            'slug' => 'united-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Main Outlet',
            'code' => 'UNITED-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        $officerRole = Role::where('name', 'credit_officer')->firstOrFail();

        $this->officer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $officerRole->id,
            'role' => 'credit_officer',
            'name' => 'Verification Officer Jamil',
            'email' => 'jamil@unitedelectronics.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->officer->roles()->sync([$officerRole->id]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'cnic' => '35201-8889990-1',
            'full_name' => 'Bilal Hassan',
            'gender' => 'male',
            'mobile_primary' => '0300-8889990',
            'present_address' => 'House 12, Gulshan Ravi, Lahore',
            'residence_type' => 'rented',
            'status' => 'pending_verification',
        ]);
    }

    public function test_investigator_can_log_field_verification_report(): void
    {
        $response = $this->actingAs($this->officer)->post(route('customers.verifications.store', $this->customer), [
            'verification_type' => 'field_visit',
            'residence_confirmed' => 1,
            'workplace_confirmed' => 1,
            'investigator_notes' => 'Visited residence physically. Met applicant and neighbor verified 3 years stay.',
            'outcome' => 'approved',
            'verified_at' => '2026-03-05 14:30:00',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customer_verifications', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'verified_by_user_id' => $this->officer->id,
            'verification_type' => 'field_visit',
            'residence_confirmed' => true,
            'outcome' => 'approved',
        ]);
    }

    public function test_approved_verification_transitions_customer_to_active(): void
    {
        $this->assertEquals('pending_verification', $this->customer->status);

        $response = $this->actingAs($this->officer)->post(route('customers.verifications.store', $this->customer), [
            'verification_type' => 'field_visit',
            'residence_confirmed' => 1,
            'investigator_notes' => 'Everything confirmed cleanly.',
            'outcome' => 'approved',
            'verified_at' => '2026-03-05 15:00:00',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('active', $this->customer->fresh()->status);
    }

    public function test_rejected_verification_transitions_customer_to_restricted(): void
    {
        $response = $this->actingAs($this->officer)->post(route('customers.verifications.store', $this->customer), [
            'verification_type' => 'field_visit',
            'residence_confirmed' => 0,
            'investigator_notes' => 'Fake address given. Neighbors never heard of this person.',
            'outcome' => 'rejected',
            'verified_at' => '2026-03-05 16:00:00',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('restricted', $this->customer->fresh()->status);
    }

    public function test_cannot_log_verification_for_another_company_customer(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Competitor Store',
            'slug' => 'competitor-store',
            'status' => 'active',
        ]);

        $foreignCustomer = Customer::create([
            'company_id' => $otherCompany->id,
            'cnic' => '35201-0001112-2',
            'full_name' => 'Foreign Applicant',
            'gender' => 'male',
            'mobile_primary' => '0300-0001112',
            'present_address' => 'Peshawar',
            'residence_type' => 'owned',
            'status' => 'pending_verification',
        ]);

        $response = $this->actingAs($this->officer)->post(route('customers.verifications.store', $foreignCustomer), [
            'verification_type' => 'field_visit',
            'residence_confirmed' => 1,
            'investigator_notes' => 'Attempting cross-tenant injection.',
            'outcome' => 'approved',
            'verified_at' => '2026-03-05 17:00:00',
        ]);

        $response->assertStatus(404);
    }
}
