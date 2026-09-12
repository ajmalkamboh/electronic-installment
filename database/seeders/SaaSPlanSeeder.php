<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SaaSPlan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SaaSPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed standard SaaS plan tiers
        $starter = SaaSPlan::firstOrCreate(['slug' => 'starter'], [
            'ulid' => (string) Str::ulid(),
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Essential installment tracking for single-outlet electronics retailers and mobile shops.',
            'price_monthly' => 4999.00,
            'price_yearly' => 49990.00,
            'max_users' => 3,
            'max_branches' => 1,
            'max_active_agreements' => 50,
            'max_monthly_transactions' => 300,
            'features' => ['documents_pdf', 'basic_reporting'],
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 1,
        ]);

        $growth = SaaSPlan::firstOrCreate(['slug' => 'growth'], [
            'ulid' => (string) Str::ulid(),
            'name' => 'Growth',
            'slug' => 'growth',
            'description' => 'Expanding electronics stores with multiple counters and automated customer communication.',
            'price_monthly' => 14999.00,
            'price_yearly' => 149990.00,
            'max_users' => 10,
            'max_branches' => 3,
            'max_active_agreements' => 300,
            'max_monthly_transactions' => 1500,
            'features' => ['documents_pdf', 'basic_reporting', 'sms_notifications', 'inventory_transfers', 'credit_scoring'],
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 2,
        ]);

        $professional = SaaSPlan::firstOrCreate(['slug' => 'professional'], [
            'ulid' => (string) Str::ulid(),
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'High-volume multi-branch retail chains needing WhatsApp integration, double-entry ledger, and portfolio analytics.',
            'price_monthly' => 34999.00,
            'price_yearly' => 349990.00,
            'max_users' => 25,
            'max_branches' => 8,
            'max_active_agreements' => 1000,
            'max_monthly_transactions' => 5000,
            'features' => ['documents_pdf', 'basic_reporting', 'sms_notifications', 'whatsapp_notifications', 'inventory_transfers', 'credit_scoring', 'general_ledger', 'advanced_analytics'],
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 3,
        ]);

        $enterprise = SaaSPlan::firstOrCreate(['slug' => 'enterprise'], [
            'ulid' => (string) Str::ulid(),
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Nationwide franchise networks requiring custom branding, priority support, and high-capacity limits.',
            'price_monthly' => 69999.00,
            'price_yearly' => 699990.00,
            'max_users' => 100,
            'max_branches' => 25,
            'max_active_agreements' => 5000,
            'max_monthly_transactions' => 25000,
            'features' => ['*'],
            'is_active' => true,
            'trial_days' => 30,
            'sort_order' => 4,
        ]);

        // 2. Seed Platform Super Admin
        User::firstOrCreate(['email' => 'superadmin@installment.test'], [
            'ulid' => (string) Str::ulid(),
            'company_id' => null,
            'branch_id' => null,
            'name' => 'Platform Super Admin',
            'email' => 'superadmin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'phone' => '+92 300 0000000',
        ]);

        // 3. Bind default tenant (Premier Electronics) to Professional subscription if not already bound
        $company = Company::first();
        if ($company && ! $company->subscriptions()->exists()) {
            Subscription::create([
                'company_id' => $company->id,
                'saas_plan_id' => $professional->id,
                'status' => 'active',
                'billing_cycle' => 'yearly',
                'starts_at' => Carbon::now()->subMonths(1),
                'ends_at' => Carbon::now()->addMonths(11),
                'trial_ends_at' => null,
                'grace_days' => 7,
                'amount_paid' => $professional->price_yearly,
                'payment_method' => 'bank_transfer',
                'notes' => 'Baseline annual subscription seeded.',
            ]);

            $company->status = 'active';
            $company->save();
        }
    }
}
