<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCreditProfile;
use App\Models\Guarantor;
use App\Services\Credit\CreditScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreditScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    protected CreditScoringEngine $engine;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CreditScoringEngine();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Apex Electronics',
            'slug' => 'apex-electronics',
            'status' => 'active',
        ]);
    }

    public function test_it_calculates_dti_percentage_accurately(): void
    {
        // Monthly Income: 100,000, Proposed: 25,000, Debts: 5,000 => Total: 30,000 => DTI: 30.0%
        $dti = $this->engine->calculateDti(100000.0, 25000.0, 5000.0);
        $this->assertEquals(30.0, $dti);

        // Zero income handling
        $zeroIncomeDti = $this->engine->calculateDti(0.0, 15000.0, 0.0);
        $this->assertEquals(100.0, $zeroIncomeDti);

        // DTI capped at 100%
        $cappedDti = $this->engine->calculateDti(20000.0, 30000.0, 10000.0);
        $this->assertEquals(100.0, $cappedDti);
    }

    public function test_it_evaluates_risk_tier_correctly(): void
    {
        // Low risk: High score & low DTI
        $this->assertEquals('low', $this->engine->evaluateRiskTier(25.0, 80));

        // Medium risk: Score >= 55 and DTI <= 40%
        $this->assertEquals('medium', $this->engine->evaluateRiskTier(38.0, 60));

        // High risk: DTI up to 50% or score 35-54
        $this->assertEquals('high', $this->engine->evaluateRiskTier(45.0, 45));

        // Critical risk: DTI > 50% or score < 35
        $this->assertEquals('critical', $this->engine->evaluateRiskTier(55.0, 30));
    }

    public function test_it_computes_holistic_credit_score_with_penalties_and_bonuses(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'cnic' => '35201-1111111-1',
            'full_name' => 'Tariq Mehmood',
            'mobile_primary' => '0300-1112233',
            'present_address' => 'Model Town, Lahore',
            'residence_type' => 'owned',
            'residence_tenure_years' => 6,
            'monthly_household_income' => 120000.00,
            'status' => 'active',
        ]);

        CustomerCreditProfile::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'credit_score' => 50,
            'max_authorized_credit' => 150000.00,
            'total_dpd_days' => 0,
            'completed_agreements_count' => 2,
        ]);

        // Add 2 verified guarantors
        Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'full_name' => 'Guarantor One',
            'cnic' => '35201-2222222-2',
            'relationship' => 'brother',
            'mobile' => '0300-2223344',
            'address' => 'Lahore',
            'is_verified' => true,
        ]);
        Guarantor::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'full_name' => 'Guarantor Two',
            'cnic' => '35201-3333333-3',
            'relationship' => 'uncle',
            'mobile' => '0300-3334455',
            'address' => 'Lahore',
            'is_verified' => true,
        ]);

        // Income: 120,000, Proposed: 20,000 => DTI: 16.67% (Score: 35)
        // Residence: Owned >= 5 yrs (Score: 25)
        // Guarantors: 2 verified (Score: 25)
        // Track record: 2 completed agreements (Score: 14)
        // Total should be >= 90
        $score = $this->engine->computeCreditScore($customer, 120000.0, 20000.0, 0.0);
        $this->assertGreaterThanOrEqual(90, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_it_returns_zero_score_for_blacklisted_customer(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'ulid' => (string) Str::ulid(),
            'cnic' => '35201-9999999-9',
            'full_name' => 'Blacklisted Individual',
            'mobile_primary' => '0300-9999999',
            'present_address' => 'Gulberg, Lahore',
            'residence_type' => 'owned',
            'residence_tenure_years' => 10,
            'monthly_household_income' => 200000.00,
            'status' => 'blacklisted',
        ]);


        $score = $this->engine->computeCreditScore($customer, 200000.0, 10000.0, 0.0);
        $this->assertEquals(0, $score);
    }
}
