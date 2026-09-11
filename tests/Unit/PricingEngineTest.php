<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Pricing\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected PricingEngine $engine;
    protected Company $company;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine();

        $this->company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Apex Electronics',
            'slug' => 'apex-electronics',
            'status' => 'active',
        ]);

        $category = ProductCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Refrigerators',
            'slug' => 'refrigerators',
        ]);

        $this->product = Product::create([
            'company_id' => $this->company->id,
            'category_id' => $category->id,
            'ulid' => (string) Str::ulid(),
            'brand' => 'Dawlance',
            'model_name' => 'Chrome Inverter 91996',
            'sku' => 'DAW-91996-INV',
            'base_cash_price' => 100000.00,
            'min_down_payment_pct' => 20.00,
        ]);
    }

    public function test_it_calculates_flat_percentage_markup_accurately(): void
    {
        // Cash: 100,000, Down Payment: 20,000 => Principal: 80,000
        // Tenure: 12 months, Annual Rate: 25.00%
        // Markup: 80,000 * (25 / 100) * (12 / 12) = 20,000.00
        // Total Payable: 20,000 (DP) + 80,000 (P) + 20,000 (M) = 120,000.00
        // Monthly Installment: (80,000 + 20,000) / 12 = 8,333.33
        $result = $this->engine->calculateCustom(
            cashPrice: 100000.00,
            tenureMonths: 12,
            model: 'flat_percentage',
            markupRate: 25.00,
            downPayment: 20000.00
        );

        $this->assertEquals(100000.00, $result->cashPrice);
        $this->assertEquals(20000.00, $result->downPayment);
        $this->assertEquals(80000.00, $result->financedPrincipal);
        $this->assertEquals(20000.00, $result->markupAmount);
        $this->assertEquals(100000.00, $result->totalFinanced);
        $this->assertEquals(120000.00, $result->totalPayable);
        $this->assertEquals(8333.33, $result->installmentAmount);
        $this->assertFalse($result->requiresManagerApproval);
    }

    public function test_it_calculates_half_year_tenure_correctly(): void
    {
        // Cash: 100,000, Down Payment: 20,000 => Principal: 80,000
        // Tenure: 6 months, Annual Rate: 20.00%
        // Markup: 80,000 * 0.20 * (6 / 12) = 8,000.00
        // Monthly: (80,000 + 8,000) / 6 = 14,666.67
        $result = $this->engine->calculateCustom(
            cashPrice: 100000.00,
            tenureMonths: 6,
            model: 'flat_percentage',
            markupRate: 20.00,
            downPayment: 20000.00
        );

        $this->assertEquals(8000.00, $result->markupAmount);
        $this->assertEquals(88000.00, $result->totalFinanced);
        $this->assertEquals(14666.67, $result->installmentAmount);
    }

    public function test_it_calculates_fixed_markup_amount(): void
    {
        $result = $this->engine->calculateCustom(
            cashPrice: 100000.00,
            tenureMonths: 12,
            model: 'fixed_amount',
            markupRate: 0.00,
            downPayment: 25000.00,
            fixedAmount: 15000.00
        );

        $this->assertEquals(15000.00, $result->markupAmount);
        $this->assertEquals(75000.00, $result->financedPrincipal);
        $this->assertEquals(90000.00, $result->totalFinanced);
        $this->assertEquals(7500.00, $result->installmentAmount);
    }

    public function test_it_flags_manager_approval_when_down_payment_is_below_minimum_policy(): void
    {
        // Product minimum down payment is 20% (Rs. 20,000)
        // User offers custom down payment of only Rs. 10,000 (10%)
        $result = $this->engine->calculateCustom(
            cashPrice: 100000.00,
            tenureMonths: 12,
            model: 'flat_percentage',
            markupRate: 25.00,
            downPayment: 10000.00,
            minDownPaymentPct: 20.00
        );

        $this->assertTrue($result->requiresManagerApproval);
        $this->assertNotNull($result->approvalReason);
        $this->assertStringContainsString('below the minimum 20% threshold', $result->approvalReason);
    }

    public function test_compare_tenures_returns_multi_tenure_schedule(): void
    {
        $comparisons = $this->engine->compareTenures($this->product, 20000.00, [3, 6, 12, 18, 24]);

        $this->assertCount(5, $comparisons);
        $this->assertArrayHasKey(3, $comparisons);
        $this->assertArrayHasKey(6, $comparisons);
        $this->assertArrayHasKey(12, $comparisons);
        $this->assertArrayHasKey(18, $comparisons);
        $this->assertArrayHasKey(24, $comparisons);

        // Shorter tenure has higher monthly payment but lower total markup
        $this->assertGreaterThan(
            $comparisons[12]->installmentAmount,
            $comparisons[3]->installmentAmount
        );
        $this->assertLessThan(
            $comparisons[12]->markupAmount,
            $comparisons[3]->markupAmount
        );
    }
}
