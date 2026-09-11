<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InstallmentPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InstallmentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $plans = [
                [
                    'name' => '3-Month Quick Financed Plan',
                    'slug' => '3-month-quick',
                    'tenure_months' => 3,
                    'markup_calculation_model' => 'flat_percentage',
                    'default_markup_rate_pct' => 15.00,
                    'min_down_payment_pct' => 20.00,
                    'installment_frequency' => 'monthly',
                    'description' => 'Fast 90-day low-markup installment plan for smartphones and small appliances.',
                ],
                [
                    'name' => '6-Month Semi-Annual Plan',
                    'slug' => '6-month-semi-annual',
                    'tenure_months' => 6,
                    'markup_calculation_model' => 'flat_percentage',
                    'default_markup_rate_pct' => 20.00,
                    'min_down_payment_pct' => 20.00,
                    'installment_frequency' => 'monthly',
                    'description' => 'Standard half-year plan popular for refrigerators, LED TVs, and mid-range electronics.',
                ],
                [
                    'name' => '12-Month Showroom Standard Plan',
                    'slug' => '12-month-standard',
                    'tenure_months' => 12,
                    'markup_calculation_model' => 'flat_percentage',
                    'default_markup_rate_pct' => 25.00,
                    'min_down_payment_pct' => 20.00,
                    'installment_frequency' => 'monthly',
                    'description' => 'Our most popular annual showroom installment plan. Fixed flat rate over 12 equal monthly installments.',
                ],
                [
                    'name' => '18-Month Extended Home Plan',
                    'slug' => '18-month-extended',
                    'tenure_months' => 18,
                    'markup_calculation_model' => 'flat_percentage',
                    'default_markup_rate_pct' => 30.00,
                    'min_down_payment_pct' => 25.00,
                    'installment_frequency' => 'monthly',
                    'description' => 'Extended 1.5-year financing for large air conditioners, deep freezers, and solar inverter packages.',
                ],
                [
                    'name' => '24-Month Long-Term Package',
                    'slug' => '24-month-long-term',
                    'tenure_months' => 24,
                    'markup_calculation_model' => 'flat_percentage',
                    'default_markup_rate_pct' => 35.00,
                    'min_down_payment_pct' => 25.00,
                    'installment_frequency' => 'monthly',
                    'description' => 'Maximum 2-year tenure for high-value complete wedding packages and solar system installations.',
                ],
            ];

            foreach ($plans as $planData) {
                InstallmentPlan::updateOrCreate(
                    ['company_id' => $company->id, 'slug' => $planData['slug']],
                    array_merge($planData, ['company_id' => $company->id, 'is_active' => true])
                );
            }
        }
    }
}
