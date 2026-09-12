<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's baseline tenant and authentication records.
     */
    public function run(): void
    {
        // 1. Primary Tenant Company
        $company = Company::create([
            'ulid' => (string) Str::ulid(),
            'name' => 'Premier Electronics',
            'slug' => 'premier-electronics',
            'legal_name' => 'Premier Electronics SMC-Pvt Ltd',
            'ntn_strn' => '7382910-4',
            'phone' => '+92 42 37210000',
            'email' => 'info@premierelectronics.pk',
            'city' => 'Lahore',
            'address' => 'Mall Road Commercial Zone, Lahore',
            'currency' => 'PKR',
            'status' => 'active',
        ]);

        // 2. Primary Branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Lahore Main Branch',
            'code' => 'LHR-01',
            'city' => 'Lahore',
            'address' => 'Shop 12-14, Hall Road, Lahore',
            'phone' => '+92 42 37210001',
            'email' => 'lahore@premierelectronics.pk',
            'is_main' => true,
            'status' => 'active',
        ]);

        // 3. Secondary Branch
        $branchFSD = Branch::create([
            'company_id' => $company->id,
            'ulid' => (string) Str::ulid(),
            'name' => 'Faisalabad Branch',
            'code' => 'FSD-01',
            'city' => 'Faisalabad',
            'address' => 'Katchery Bazaar, Faisalabad',
            'phone' => '+92 41 2600001',
            'email' => 'fsd@premierelectronics.pk',
            'is_main' => false,
            'status' => 'active',
        ]);

        // 4. Primary Company Administrator
        User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Ajmal Admin',
            'email' => 'admin@installment.test',
            'password' => Hash::make('password'),
            'role' => 'company_admin',
            'status' => 'active',
            'phone' => '+92 300 1234567',
        ]);

        // 5. Branch Manager
        User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Tariq Manager',
            'email' => 'manager@installment.test',
            'password' => Hash::make('password'),
            'role' => 'branch_manager',
            'status' => 'active',
            'phone' => '+92 301 7654321',
        ]);

        // 6. Suspended User (for security tests)
        User::create([
            'ulid' => (string) Str::ulid(),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Suspended Staff',
            'email' => 'suspended@installment.test',
            'password' => Hash::make('password'),
            'role' => 'viewer',
            'status' => 'suspended',
            'phone' => '+92 302 9998877',
        ]);

        // 7. Roles & Permissions & Link Staff
        $this->call(RoleAndPermissionSeeder::class);

        // 8. SaaS Plans, Subscriptions & Platform Super Admin
        $this->call(SaaSPlanSeeder::class);
    }
}
