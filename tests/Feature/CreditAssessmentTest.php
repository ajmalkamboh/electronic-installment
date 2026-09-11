<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CreditAssessment;
use App\Models\Customer;
use App\Models\CustomerCreditProfile;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreditAssessmentTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $creditOfficer;
    protected User $cashier;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Al-Madina Electronics',
            'slug' => 'al-madina-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Faisalabad Main',
            'code' => 'FSD-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        // 1. Credit Officer
        $officerRole = Role::where('name', 'credit_officer')->firstOrFail();
        $this->creditOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $officerRole->id,
            'role' => 'credit_officer',
            'name' => 'Imran Credit Officer',
            'email' => 'officer@almadina.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->creditOfficer->roles()->sync([$officerRole->id]);

        // 2. Cashier (no credit.assess permission)
        $cashierRole = Role::where('name', 'cashier')->firstOrFail();
        $this->cashier = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $cashierRole->id,
            'role' => 'cashier',
            'name' => 'Waqas Cashier',
            'email' => 'cashier@almadina.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->cashier->roles()->sync([$cashierRole->id]);

        // 3. Customer
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'cnic' => '33100-1234567-1',
            'full_name' => 'Shahid Afridi',
            'mobile_primary' => '0300-7654321',
            'present_address' => 'D-Ground, Faisalabad',
            'residence_type' => 'owned',
            'residence_tenure_years' => 5,
            'monthly_household_income' => 85000.00,
            'status' => 'active',
        ]);

        CustomerCreditProfile::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'credit_score' => 50,
            'max_authorized_credit' => 150000.00,
            'total_dpd_days' => 0,
        ]);
    }

    public function test_credit_officer_can_view_assessments_index(): void
    {
        $response = $this->actingAs($this->creditOfficer)
            ->get(route('credit.assessments.index'));

        $response->assertStatus(200);
        $response->assertSee('Credit Underwriting Center');
    }

    public function test_unauthorized_user_cannot_access_credit_center(): void
    {
        $response = $this->actingAs($this->cashier)
            ->get(route('credit.assessments.index'));

        $response->assertStatus(403);
    }

    public function test_credit_officer_can_view_assessment_create_form(): void
    {
        $response = $this->actingAs($this->creditOfficer)
            ->get(route('customers.assessments.create', $this->customer));

        $response->assertStatus(200);
        $response->assertSee('Credit Risk Underwriting Sheet');
        $response->assertSee('Shahid Afridi');
    }

    public function test_credit_officer_can_submit_assessment_with_dti_calculation(): void
    {
        $payload = [
            'monthly_income' => 90000.00,
            'existing_debt_obligations' => 5000.00,
            'proposed_installment_limit' => 22000.00, // Total = 27,000 => DTI = 30.0%
            'recommended_limit' => 200000.00,
            'recommendation' => 'approved',
            'conditions_summary' => 'Standard 20% down payment required',
            'assessment_notes' => 'Customer has stable business income verified via shop visit.',
        ];

        $response = $this->actingAs($this->creditOfficer)
            ->post(route('customers.assessments.store', $this->customer), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('credit_assessments', [
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'assessed_by_user_id' => $this->creditOfficer->id,
            'monthly_income' => 90000.00,
            'existing_debt_obligations' => 5000.00,
            'proposed_installment_limit' => 22000.00,
            'calculated_dti_percentage' => 30.00,
            'recommendation' => 'approved',
            'status' => 'pending_approval',
        ]);
    }

    public function test_submitting_new_assessment_supersedes_previous_pending_assessment(): void
    {
        $firstAssessment = CreditAssessment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'assessed_by_user_id' => $this->creditOfficer->id,
            'ulid' => (string) Str::ulid(),
            'monthly_income' => 70000.00,
            'existing_debt_obligations' => 0.00,
            'proposed_installment_limit' => 20000.00,
            'calculated_dti_percentage' => 28.57,
            'score' => 60,
            'risk_tier' => 'medium',
            'recommended_limit' => 150000.00,
            'recommendation' => 'approved',
            'status' => 'pending_approval',
            'assessed_at' => now()->subDay(),
        ]);

        $payload = [
            'monthly_income' => 85000.00,
            'existing_debt_obligations' => 0.00,
            'proposed_installment_limit' => 25000.00,
            'recommended_limit' => 180000.00,
            'recommendation' => 'approved',
            'conditions_summary' => 'Updated terms',
        ];

        $this->actingAs($this->creditOfficer)
            ->post(route('customers.assessments.store', $this->customer), $payload);

        $this->assertEquals('superseded', $firstAssessment->fresh()->status);
    }

    public function test_blacklisted_customer_cannot_be_assessed(): void
    {
        $this->customer->update(['status' => 'blacklisted']);

        $response = $this->actingAs($this->creditOfficer)
            ->get(route('customers.assessments.create', $this->customer));

        $response->assertRedirect(route('customers.show', $this->customer));
        $response->assertSessionHas('error');
    }

    public function test_cross_tenant_isolation_prevents_assessing_other_company_customer(): void
    {
        $otherCompany = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Other Retail',
            'slug' => 'other-retail',
            'status' => 'active',
        ]);

        $otherCustomer = Customer::create([
            'company_id' => $otherCompany->id,
            'ulid' => (string) Str::ulid(),
            'cnic' => '33100-9999999-1',
            'full_name' => 'Foreign Applicant',
            'mobile_primary' => '0300-9998877',
            'present_address' => 'Multan',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->creditOfficer)
            ->get(route('customers.assessments.create', $otherCustomer));

        $response->assertStatus(404);
    }
}
