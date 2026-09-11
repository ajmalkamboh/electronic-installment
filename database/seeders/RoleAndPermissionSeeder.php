<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Branches
            ['name' => 'branches.view', 'display_name' => 'View Showrooms', 'group' => 'branches', 'description' => 'View branch showroom list and details'],
            ['name' => 'branches.manage', 'display_name' => 'Manage Showrooms', 'group' => 'branches', 'description' => 'Create, edit, and toggle branch showroom status'],

            // Staff
            ['name' => 'staff.view', 'display_name' => 'View Staff Directory', 'group' => 'staff', 'description' => 'View employee list and profiles'],
            ['name' => 'staff.create', 'display_name' => 'Register Staff Member', 'group' => 'staff', 'description' => 'Register new employee with branch and role'],
            ['name' => 'staff.edit', 'display_name' => 'Edit Staff Member', 'group' => 'staff', 'description' => 'Modify staff profile, branch transfer, and designation'],
            ['name' => 'staff.suspend', 'display_name' => 'Suspend / Activate Staff', 'group' => 'staff', 'description' => 'Toggle employee access status'],
            ['name' => 'staff.delete', 'display_name' => 'Delete Staff Record', 'group' => 'staff', 'description' => 'Soft delete employee records'],

            // Roles & Permissions
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'group' => 'roles', 'description' => 'View system and custom roles'],
            ['name' => 'roles.manage', 'display_name' => 'Manage Roles', 'group' => 'roles', 'description' => 'Create and customize tenant roles and permissions'],

            // Customers
            ['name' => 'customers.view', 'display_name' => 'View Customers', 'group' => 'customers', 'description' => 'Access customer records and dossiers'],
            ['name' => 'customers.create', 'display_name' => 'Register Customer', 'group' => 'customers', 'description' => 'Create customer profile and guarantors'],
            ['name' => 'customers.edit', 'display_name' => 'Edit Customer', 'group' => 'customers', 'description' => 'Update customer information and documents'],
            ['name' => 'customers.verify', 'display_name' => 'Verify Customer Dossier', 'group' => 'customers', 'description' => 'Conduct physical and telephonic verification'],

            // Credit Underwriting
            ['name' => 'credit.assess', 'display_name' => 'Submit Credit Assessment', 'group' => 'credit', 'description' => 'Score customer risk and calculate debt-to-income'],
            ['name' => 'credit.approve', 'display_name' => 'Approve Credit Application', 'group' => 'credit', 'description' => 'Grant formal credit authorization'],
            ['name' => 'credit.blacklist', 'display_name' => 'Blacklist Defaulter', 'group' => 'credit', 'description' => 'Flag delinquent debtor on company blacklist'],

            // Inventory
            ['name' => 'inventory.view', 'display_name' => 'View Stock', 'group' => 'inventory', 'description' => 'Inspect showroom stock and serial numbers'],
            ['name' => 'inventory.manage', 'display_name' => 'Manage Products & Stock', 'group' => 'inventory', 'description' => 'Add products, receive stock, and assign IMEIs'],
            ['name' => 'inventory.dispatch', 'display_name' => 'Dispatch Serialized Item', 'group' => 'inventory', 'description' => 'Allocate and disburse serialized merchandise to customer'],

            // Agreements
            ['name' => 'agreements.view', 'display_name' => 'View Agreements', 'group' => 'agreements', 'description' => 'View contract details and payment schedules'],
            ['name' => 'agreements.create', 'display_name' => 'Create Agreement', 'group' => 'agreements', 'description' => 'Draft installment proposal and calculate markup'],
            ['name' => 'agreements.approve', 'display_name' => 'Approve Agreement', 'group' => 'agreements', 'description' => 'Authorize agreement activation and delivery'],
            ['name' => 'agreements.cancel', 'display_name' => 'Cancel Agreement', 'group' => 'agreements', 'description' => 'Void draft or rejected agreement proposal'],

            // Payments
            ['name' => 'payments.view', 'display_name' => 'View Payment History', 'group' => 'payments', 'description' => 'Review installment transactions and receipts'],
            ['name' => 'payments.collect', 'display_name' => 'Collect Payment', 'group' => 'payments', 'description' => 'Receive cash or digital payment and generate receipt'],
            ['name' => 'payments.verify', 'display_name' => 'Verify Deposit', 'group' => 'payments', 'description' => 'Confirm bank/mobile transfer before acknowledgment'],
            ['name' => 'payments.waive_late_fee', 'display_name' => 'Waive Late Fee', 'group' => 'payments', 'description' => 'Authorize waiver on overdue penalty fees'],

            // Recovery & Field Collection
            ['name' => 'recovery.view', 'display_name' => 'View Delinquent Accounts', 'group' => 'recovery', 'description' => 'Monitor DPD buckets and overdue installment cases'],
            ['name' => 'recovery.visit', 'display_name' => 'Log Field Recovery Visit', 'group' => 'recovery', 'description' => 'Submit field recovery log and visit notes'],
            ['name' => 'recovery.escalate', 'display_name' => 'Escalate Delinquency Stage', 'group' => 'recovery', 'description' => 'Transition account to legal notice or repossession'],
            ['name' => 'recovery.repossess', 'display_name' => 'Authorize Repossession', 'group' => 'recovery', 'description' => 'Execute asset recovery workflow'],

            // Reports & Accounting
            ['name' => 'reports.view', 'display_name' => 'View Operational Reports', 'group' => 'reports', 'description' => 'Access sales, collection, and inventory summaries'],
            ['name' => 'financials.view', 'display_name' => 'View Financial Ledgers', 'group' => 'reports', 'description' => 'Inspect accounting entries and balance sheet'],
            ['name' => 'audit.view', 'display_name' => 'View Security Audit Logs', 'group' => 'reports', 'description' => 'Audit user actions and system events'],

            // Settings
            ['name' => 'settings.manage', 'display_name' => 'Manage Company Settings', 'group' => 'settings', 'description' => 'Update corporate profile, receipt notes, and policies'],
        ];

        foreach ($permissions as $permData) {
            Permission::updateOrCreate(['name' => $permData['name']], $permData);
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        // 1. Company Admin (System)
        $adminRole = Role::updateOrCreate(
            ['name' => 'company_admin', 'company_id' => null],
            [
                'display_name' => 'Company Administrator',
                'description' => 'Full operational and executive authority across all company branches and settings.',
                'is_system' => true,
            ]
        );
        $adminRole->permissions()->sync($allPermissionIds);

        // 2. Branch Manager (System)
        $managerRole = Role::updateOrCreate(
            ['name' => 'branch_manager', 'company_id' => null],
            [
                'display_name' => 'Branch Manager',
                'description' => 'Branch operational oversight, employee management, credit approvals, and agreement authorization.',
                'is_system' => true,
            ]
        );
        $managerPerms = Permission::whereIn('name', [
            'branches.view',
            'staff.view',
            'customers.view', 'customers.create', 'customers.edit', 'customers.verify',
            'credit.assess', 'credit.approve', 'credit.blacklist',
            'inventory.view', 'inventory.manage', 'inventory.dispatch',

            'agreements.view', 'agreements.create', 'agreements.approve',
            'payments.view', 'payments.collect', 'payments.verify', 'payments.waive_late_fee',
            'recovery.view', 'recovery.visit',
            'reports.view',
        ])->pluck('id')->toArray();
        $managerRole->permissions()->sync($managerPerms);

        // 3. Credit Officer (System)
        $creditOfficerRole = Role::updateOrCreate(
            ['name' => 'credit_officer', 'company_id' => null],
            [
                'display_name' => 'Credit & Verification Officer',
                'description' => 'Customer KYC profiling, guarantor verification, and debt-to-income credit risk assessment.',
                'is_system' => true,
            ]
        );
        $creditOfficerPerms = Permission::whereIn('name', [
            'customers.view', 'customers.create', 'customers.edit', 'customers.verify',
            'credit.assess',
            'agreements.view',
            'reports.view',
        ])->pluck('id')->toArray();
        $creditOfficerRole->permissions()->sync($creditOfficerPerms);

        // 4. Cashier (System)
        $cashierRole = Role::updateOrCreate(
            ['name' => 'cashier', 'company_id' => null],
            [
                'display_name' => 'Cashier & Teller',
                'description' => 'Cash drawer handling, payment collection, and installment receipt generation.',
                'is_system' => true,
            ]
        );
        $cashierPerms = Permission::whereIn('name', [
            'customers.view',
            'agreements.view',
            'payments.view', 'payments.collect',
        ])->pluck('id')->toArray();
        $cashierRole->permissions()->sync($cashierPerms);

        // 5. Collection Officer (System)
        $collectionRole = Role::updateOrCreate(
            ['name' => 'collection_officer', 'company_id' => null],
            [
                'display_name' => 'Field Recovery Officer',
                'description' => 'Delinquent account tracking, customer field visits, and payment collection.',
                'is_system' => true,
            ]
        );
        $collectionPerms = Permission::whereIn('name', [
            'customers.view',
            'agreements.view',
            'payments.view', 'payments.collect',
            'recovery.view', 'recovery.visit',
        ])->pluck('id')->toArray();
        $collectionRole->permissions()->sync($collectionPerms);

        // 6. Accountant (System)
        $accountantRole = Role::updateOrCreate(
            ['name' => 'accountant', 'company_id' => null],
            [
                'display_name' => 'Financial Accountant',
                'description' => 'Financial ledger analysis, daily cash reconciliation, and accounting audits.',
                'is_system' => true,
            ]
        );
        $accountantPerms = Permission::whereIn('name', [
            'reports.view', 'financials.view', 'audit.view',
            'payments.view',
            'agreements.view',
            'inventory.view',
        ])->pluck('id')->toArray();
        $accountantRole->permissions()->sync($accountantPerms);

        // Link existing seeded users
        $adminUser = User::where('email', 'admin@installment.test')->first();
        if ($adminUser) {
            $adminUser->update([
                'role_id' => $adminRole->id,
                'employee_code' => 'EMP-001',
                'cnic' => '35201-1234567-1',
                'designation' => 'Executive Director',
                'joining_date' => '2025-01-01',
                'salary' => 250000.00,
            ]);
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $managerUser = User::where('email', 'manager@installment.test')->first();
        if ($managerUser) {
            $managerUser->update([
                'role_id' => $managerRole->id,
                'employee_code' => 'EMP-002',
                'cnic' => '35201-2345678-2',
                'designation' => 'Senior Showroom Manager',
                'joining_date' => '2025-02-01',
                'salary' => 120000.00,
            ]);
            $managerUser->roles()->syncWithoutDetaching([$managerRole->id]);
        }

        $cashierUser = User::where('email', 'cashier@installment.test')->first();
        if ($cashierUser) {
            $cashierUser->update([
                'role_id' => $cashierRole->id,
                'employee_code' => 'EMP-003',
                'cnic' => '35201-3456789-3',
                'designation' => 'Lead Cashier',
                'joining_date' => '2025-03-01',
                'salary' => 65000.00,
            ]);
            $cashierUser->roles()->syncWithoutDetaching([$cashierRole->id]);
        }
    }
}
