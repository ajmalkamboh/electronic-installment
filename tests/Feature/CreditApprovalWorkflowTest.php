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

class CreditApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $branchManager;
    protected User $creditOfficer;
    protected User $cashier;
    protected Customer $customer;
    protected CreditAssessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Royal Electronics',
            'slug' => 'royal-electronics',
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Rawalpindi Showroom',
            'code' => 'RWP-01',
            'is_main' => true,
            'status' => 'active',
        ]);

        // Branch Manager (has credit.approve)
        $managerRole = Role::where('name', 'branch_manager')->firstOrFail();
        $this->branchManager = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $managerRole->id,
            'role' => 'branch_manager',
            'name' => 'Rashid Manager',
            'email' => 'manager@royal.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->branchManager->roles()->sync([$managerRole->id]);

        // Credit Officer (has credit.assess, but NOT credit.approve)
        $officerRole = Role::where('name', 'credit_officer')->firstOrFail();
        $this->creditOfficer = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $officerRole->id,
            'role' => 'credit_officer',
            'name' => 'Usman Officer',
            'email' => 'officer@royal.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->creditOfficer->roles()->sync([$officerRole->id]);

        // Cashier (no credit permissions)
        $cashierRole = Role::where('name', 'cashier')->firstOrFail();
        $this->cashier = User::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'role_id' => $cashierRole->id,
            'role' => 'cashier',
            'name' => 'Zain Cashier',
            'email' => 'cashier@royal.pk',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $this->cashier->roles()->sync([$cashierRole->id]);

        // Customer
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'cnic' => '37405-1234567-3',
            'full_name' => 'Babar Azam',
            'mobile_primary' => '0312-9876543',
            'present_address' => 'Saddar, Rawalpindi',
            'residence_type' => 'owned',
            'residence_tenure_years' => 4,
            'monthly_household_income' => 110000.00,
            'status' => 'active',
        ]);

        CustomerCreditProfile::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'credit_score' => 50,
            'max_authorized_credit' => 100000.00,
            'total_dpd_days' => 0,
        ]);

        // Pending Credit Assessment
        $this->assessment = CreditAssessment::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'assessed_by_user_id' => $this->creditOfficer->id,
            'ulid' => (string) Str::ulid(),
            'monthly_income' => 110000.00,
            'existing_debt_obligations' => 0.00,
            'proposed_installment_limit' => 28000.00,
            'calculated_dti_percentage' => 25.45,
            'score' => 78,
            'risk_tier' => 'low',
            'recommended_limit' => 250000.00,
            'recommendation' => 'approved',
            'conditions_summary' => 'Standard contract terms',
            'status' => 'pending_approval',
            'assessed_at' => now(),
        ]);
    }

    public function test_branch_manager_can_view_approval_queue(): void
    {
        $response = $this->actingAs($this->branchManager)
            ->get(route('credit.approvals.index'));

        $response->assertStatus(200);
        $response->assertSee('Credit Approval Queue');
        $response->assertSee('Babar Azam');
    }

    public function test_credit_officer_cannot_access_approval_queue(): void
    {
        $response = $this->actingAs($this->creditOfficer)
            ->get(route('credit.approvals.index'));

        $response->assertStatus(403);
    }

    public function test_branch_manager_can_approve_credit_assessment(): void
    {
        $payload = [
            'decision' => 'approved',
            'authorized_credit_limit' => 250000.00,
            'conditions_imposed' => '20% down payment confirmed',
            'approval_notes' => 'Strong income profile and residence tenure verified.',
        ];

        $response = $this->actingAs($this->branchManager)
            ->post(route('credit.assessments.approve', $this->assessment), $payload);

        $response->assertRedirect(route('credit.assessments.show', $this->assessment));

        // CreditApproval audit created
        $this->assertDatabaseHas('credit_approvals', [
            'company_id' => $this->company->id,
            'credit_assessment_id' => $this->assessment->id,
            'customer_id' => $this->customer->id,
            'approved_by_user_id' => $this->branchManager->id,
            'decision' => 'approved',
            'authorized_credit_limit' => 250000.00,
        ]);

        // Assessment status updated
        $this->assertEquals('approved', $this->assessment->fresh()->status);

        // Customer credit profile synchronized
        $profile = $this->customer->creditProfile->fresh();
        $this->assertEquals(250000.00, (float) $profile->max_authorized_credit);
        $this->assertEquals(78, $profile->credit_score);
    }

    public function test_branch_manager_can_reject_credit_assessment(): void
    {
        $payload = [
            'decision' => 'rejected',
            'authorized_credit_limit' => 0.00,
            'approval_notes' => 'Delinquency risk too high; insufficient guarantor reliability.',
        ];

        $response = $this->actingAs($this->branchManager)
            ->post(route('credit.assessments.approve', $this->assessment), $payload);

        $response->assertRedirect(route('credit.assessments.show', $this->assessment));

        $this->assertEquals('rejected', $this->assessment->fresh()->status);

        // Limit not changed to anything new
        $profile = $this->customer->creditProfile->fresh();
        $this->assertEquals(100000.00, (float) $profile->max_authorized_credit);
    }

    public function test_blacklisted_customer_cannot_be_approved(): void
    {
        $this->customer->update(['status' => 'blacklisted']);

        $payload = [
            'decision' => 'approved',
            'authorized_credit_limit' => 200000.00,
        ];

        $response = $this->actingAs($this->branchManager)
            ->post(route('credit.assessments.approve', $this->assessment), $payload);

        $response->assertSessionHas('error');
        $this->assertEquals('pending_approval', $this->assessment->fresh()->status);
    }

    public function test_authorized_user_can_blacklist_and_restore_customer(): void
    {
        // Admin or Manager with credit.blacklist
        $response = $this->actingAs($this->branchManager)
            ->post(route('customers.blacklist.toggle', $this->customer), [
                'reason' => 'Severe default on previous electronic contract.',
            ]);

        $response->assertRedirect();

        // Customer blacklisted
        $this->assertEquals('blacklisted', $this->customer->fresh()->status);
        $profile = $this->customer->creditProfile->fresh();
        $this->assertEquals(0, $profile->credit_score);
        $this->assertEquals(0.00, (float) $profile->max_authorized_credit);
        $this->assertEquals('Severe default on previous electronic contract.', $profile->blacklisted_reason);

        // Pending assessments automatically rejected
        $this->assertEquals('rejected', $this->assessment->fresh()->status);

        // Restore customer from blacklist
        $restoreResponse = $this->actingAs($this->branchManager)
            ->post(route('customers.blacklist.toggle', $this->customer));

        $restoreResponse->assertRedirect();
        $this->assertEquals('restricted', $this->customer->fresh()->status);
        $this->assertNull($this->customer->creditProfile->fresh()->blacklisted_reason);
    }
}
