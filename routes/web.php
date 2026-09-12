<?php

use App\Http\Controllers\Admin\SaaSPlanController;
use App\Http\Controllers\Admin\SubscriptionBillingController;
use App\Http\Controllers\Admin\SuperAdminDashboardController;
use App\Http\Controllers\Admin\TenantManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\AccountingController;
use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\BlacklistController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BranchSwitchController;
use App\Http\Controllers\Tenant\CollectionController;
use App\Http\Controllers\Tenant\CompanySettingsController;
use App\Http\Controllers\Tenant\CreditApprovalController;
use App\Http\Controllers\Tenant\CreditAssessmentController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\CustomerVerificationController;
use App\Http\Controllers\Tenant\DocumentController;
use App\Http\Controllers\Tenant\GuarantorController;
use App\Http\Controllers\Tenant\InstallmentAgreementController;
use App\Http\Controllers\Tenant\InstallmentPlanController;
use App\Http\Controllers\Tenant\InventoryController;
use App\Http\Controllers\Tenant\NotificationController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Tenant\PricingCalculatorController;
use App\Http\Controllers\Tenant\ProductCategoryController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\RecoveryController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\SupplierController;
use App\Http\Controllers\Tenant\TenantSubscriptionController;
use App\Http\Controllers\Tenant\TransferController;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

// Guest root redirect
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Tenant Routes
Route::middleware(['auth', TenantMiddleware::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Branch Context Switching
    Route::post('/tenant/switch-branch', [BranchSwitchController::class, 'switch'])->name('tenant.switch-branch');

    // Branch Management CRUD
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store')->middleware('plan.limit:branches');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::post('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');

    // Company Profile & Tenant Settings
    Route::get('/company/settings', [CompanySettingsController::class, 'edit'])->name('company.settings.edit');
    Route::put('/company/settings', [CompanySettingsController::class, 'update'])->name('company.settings.update');

    // Tenant SaaS Subscription & Limits Hub (Phase 17)
    Route::get('/subscription', [TenantSubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/upgrade', [TenantSubscriptionController::class, 'upgrade'])->name('subscription.upgrade');

    // Employee & Staff Lifecycle Management
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store')->middleware('plan.limit:users');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{staff}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');

    // Role & Permissions (RBAC) Management
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Customer & Debtor Management
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // Guarantors
    Route::post('/customers/{customer}/guarantors', [GuarantorController::class, 'store'])->name('customers.guarantors.store');
    Route::post('/guarantors/{guarantor}/toggle-verified', [GuarantorController::class, 'toggleVerified'])->name('guarantors.toggle-verified');
    Route::delete('/guarantors/{guarantor}', [GuarantorController::class, 'destroy'])->name('guarantors.destroy');

    // Field Verifications
    Route::post('/customers/{customer}/verifications', [CustomerVerificationController::class, 'store'])->name('customers.verifications.store');

    // Credit Underwriting & Assessments (Phase 06)
    Route::get('/credit/assessments', [CreditAssessmentController::class, 'index'])->name('credit.assessments.index');
    Route::get('/customers/{customer}/assessments/create', [CreditAssessmentController::class, 'create'])->name('customers.assessments.create');
    Route::post('/customers/{customer}/assessments', [CreditAssessmentController::class, 'store'])->name('customers.assessments.store');
    Route::get('/credit/assessments/{creditAssessment}', [CreditAssessmentController::class, 'show'])->name('credit.assessments.show');

    // Credit Approvals (Phase 06)
    Route::get('/credit/approvals', [CreditApprovalController::class, 'index'])->name('credit.approvals.index');
    Route::post('/credit/assessments/{creditAssessment}/approve', [CreditApprovalController::class, 'store'])->name('credit.assessments.approve');

    // Blacklist Registry (Phase 06)
    Route::post('/customers/{customer}/blacklist', [BlacklistController::class, 'toggle'])->name('customers.blacklist.toggle');

    // Product Categories (Phase 07)
    Route::get('/categories', [ProductCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [ProductCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [ProductCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [ProductCategoryController::class, 'destroy'])->name('categories.destroy');

    // Wholesale Suppliers (Phase 07)
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Products Master Catalog (Phase 07)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Showroom Inventory & Serialized Hardware (Phase 07)
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/serialized', [InventoryController::class, 'serializedIndex'])->name('inventory.serialized');
    Route::get('/inventory/receipt', [InventoryController::class, 'createReceipt'])->name('inventory.receipt.create');
    Route::post('/inventory/receipt', [InventoryController::class, 'storeReceipt'])->name('inventory.receipt.store');
    Route::get('/inventory/serialized/{item}/transfer', [InventoryController::class, 'createTransfer'])->name('inventory.transfer.create');
    Route::post('/inventory/serialized/{item}/transfer', [InventoryController::class, 'storeTransfer'])->name('inventory.transfer.store');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');

    // Installment Plans & Pricing Engine (Phase 08)
    Route::get('/plans', [InstallmentPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [InstallmentPlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [InstallmentPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [InstallmentPlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [InstallmentPlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle', [InstallmentPlanController::class, 'toggle'])->name('plans.toggle');
    Route::delete('/plans/{plan}', [InstallmentPlanController::class, 'destroy'])->name('plans.destroy');

    // Pricing Calculator & Quotation Simulator (Phase 08)
    Route::get('/pricing/calculator', [PricingCalculatorController::class, 'index'])->name('pricing.calculator');
    Route::post('/pricing/calculate', [PricingCalculatorController::class, 'calculate'])->name('pricing.calculate');

    // Installment Agreements & Contracts (Phase 09)
    Route::get('/agreements', [InstallmentAgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/create', [InstallmentAgreementController::class, 'create'])->name('agreements.create');
    Route::post('/agreements', [InstallmentAgreementController::class, 'store'])->name('agreements.store')->middleware('plan.limit:agreements');
    Route::get('/agreements/{agreement}', [InstallmentAgreementController::class, 'show'])->name('agreements.show');
    Route::post('/agreements/{agreement}/submit', [InstallmentAgreementController::class, 'submit'])->name('agreements.submit');
    Route::post('/agreements/{agreement}/approve', [InstallmentAgreementController::class, 'approve'])->name('agreements.approve');
    Route::post('/agreements/{agreement}/down-payment', [InstallmentAgreementController::class, 'recordDownPayment'])->name('agreements.down-payment');
    Route::post('/agreements/{agreement}/disburse', [InstallmentAgreementController::class, 'disburse'])->name('agreements.disburse');
    Route::post('/agreements/{agreement}/cancel', [InstallmentAgreementController::class, 'cancel'])->name('agreements.cancel');
    Route::get('/agreements/{agreement}/print', [InstallmentAgreementController::class, 'print'])->name('agreements.print');

    // Payment Engine & Schedule Ledger (Phase 10)
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/print', [PaymentController::class, 'print'])->name('payments.print');

    // Collection Officer & Recovery Workflow (Phase 11)
    Route::get('/collections/dashboard', [CollectionController::class, 'dashboard'])->name('collections.dashboard');
    Route::get('/collections/run-sheet', [CollectionController::class, 'runSheet'])->name('collections.run-sheet');
    Route::get('/collections/run-sheet/print', [CollectionController::class, 'printRunSheet'])->name('collections.run-sheet.print');
    Route::get('/collections/assignments', [CollectionController::class, 'assignments'])->name('collections.assignments');
    Route::post('/collections/assignments', [CollectionController::class, 'assign'])->name('collections.assignments.store');
    Route::post('/collections/logs', [CollectionController::class, 'logVisit'])->name('collections.logs.store');
    Route::post('/collections/payments', [CollectionController::class, 'collectFieldPayment'])->name('collections.payments.store');
    Route::get('/collections/handovers', [CollectionController::class, 'handovers'])->name('collections.handovers');
    Route::post('/collections/handovers/acknowledge', [CollectionController::class, 'acknowledgeHandover'])->name('collections.handovers.acknowledge');

    // Late Fee & Recovery Workflow (Phase 12)
    Route::get('/recovery', [RecoveryController::class, 'dashboard'])->name('recovery.dashboard');
    Route::post('/recovery/run-assessment', [RecoveryController::class, 'runAssessment'])->name('recovery.run-assessment');
    Route::get('/recovery/late-fees', [RecoveryController::class, 'lateFees'])->name('recovery.late-fees');
    Route::post('/recovery/late-fees/{schedule}/waive', [RecoveryController::class, 'waiveLateFee'])->name('recovery.late-fees.waive');
    Route::get('/recovery/cases', [RecoveryController::class, 'cases'])->name('recovery.cases.index');
    Route::get('/recovery/cases/{case}', [RecoveryController::class, 'showCase'])->name('recovery.cases.show');
    Route::post('/recovery/cases/{case}/issue-notice', [RecoveryController::class, 'issueNotice'])->name('recovery.cases.issue-notice');
    Route::get('/recovery/notices/{notice}/print', [RecoveryController::class, 'printNotice'])->name('recovery.notices.print');
    Route::post('/recovery/cases/{case}/authorize-repossession', [RecoveryController::class, 'authorizeRepossession'])->name('recovery.cases.authorize-repossession');
    Route::post('/recovery/cases/{case}/execute-repossession', [RecoveryController::class, 'executeRepossession'])->name('recovery.cases.execute-repossession');
    Route::post('/recovery/cases/{case}/write-off', [RecoveryController::class, 'writeOff'])->name('recovery.cases.write-off');

    // Legal Documents, Print Center & PDF Engine (Phase 13)
    Route::get('/documents/hub', [DocumentController::class, 'hub'])->name('documents.hub');
    Route::get('/documents/agreement/{agreement}', [DocumentController::class, 'agreementCenter'])->name('documents.agreement');
    Route::get('/documents/print/{type}/{agreement}', [DocumentController::class, 'print'])->name('documents.print');
    Route::get('/documents/receipt/{payment}', [DocumentController::class, 'printReceipt'])->name('documents.receipt');
    Route::post('/documents/agreement/{agreement}/noc', [DocumentController::class, 'issueNoc'])->name('documents.issue-noc');

    // Financial & General Ledger Engine (Phase 14)
    Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('accounting.coa');
    Route::post('/accounting/chart-of-accounts', [AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
    Route::get('/accounting/journal', [AccountingController::class, 'journal'])->name('accounting.journal');
    Route::get('/accounting/journal/create', [AccountingController::class, 'createJournal'])->name('accounting.journal.create');
    Route::post('/accounting/journal', [AccountingController::class, 'storeJournal'])->name('accounting.journal.store');
    Route::get('/accounting/trial-balance', [AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', [AccountingController::class, 'profitLoss'])->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    Route::get('/accounting/cash-book', [AccountingController::class, 'cashBook'])->name('accounting.cash-book');
    Route::get('/accounting/account/{account}/ledger', [AccountingController::class, 'accountLedger'])->name('accounting.account-ledger');

    // SMS & WhatsApp Notifications Engine (Phase 15)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [NotificationController::class, 'sendCustom'])->name('notifications.send');
    Route::post('/notifications/{notification}/retry', [NotificationController::class, 'retry'])->name('notifications.retry');
    Route::get('/notifications/templates', [NotificationController::class, 'templates'])->name('notifications.templates');
    Route::put('/notifications/templates/{template}', [NotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
    Route::get('/notifications/settings', [NotificationController::class, 'settings'])->name('notifications.settings');
    Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.settings.update');

    // Reporting & Analytics Engine (Phase 16)
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('/analytics/aging', [AnalyticsController::class, 'aging'])->name('analytics.aging');
    Route::get('/analytics/collections', [AnalyticsController::class, 'collections'])->name('analytics.collections');
    Route::get('/analytics/branches', [AnalyticsController::class, 'branches'])->name('analytics.branches');
    Route::get('/analytics/products', [AnalyticsController::class, 'products'])->name('analytics.products');
    Route::get('/analytics/export/{type}', [AnalyticsController::class, 'export'])->name('analytics.export');

    // Multi-Branch Inventory Transfers & Gate Passes
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('/transfers/{transfer}/dispatch', [TransferController::class, 'dispatchOrder'])->name('transfers.dispatch');
    Route::post('/transfers/{transfer}/receive', [TransferController::class, 'receiveOrder'])->name('transfers.receive');
    Route::post('/transfers/{transfer}/cancel', [TransferController::class, 'cancel'])->name('transfers.cancel');
    Route::get('/transfers/{transfer}/gate-pass', [TransferController::class, 'gatePass'])->name('transfers.gate-pass');
});

// Platform Super Admin Command Center (Phase 17)
Route::middleware(['auth', 'super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

    // Tenant Companies Management
    Route::get('/companies', [TenantManagementController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [TenantManagementController::class, 'create'])->name('companies.create');
    Route::post('/companies', [TenantManagementController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}', [TenantManagementController::class, 'show'])->name('companies.show');
    Route::post('/companies/{company}/change-plan', [TenantManagementController::class, 'changePlan'])->name('companies.change-plan');
    Route::post('/companies/{company}/extend-subscription', [TenantManagementController::class, 'extendSubscription'])->name('companies.extend-subscription');
    Route::post('/companies/{company}/toggle-status', [TenantManagementController::class, 'toggleStatus'])->name('companies.toggle-status');

    // SaaS Pricing Plans CRUD
    Route::get('/plans', [SaaSPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [SaaSPlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [SaaSPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [SaaSPlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [SaaSPlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle-active', [SaaSPlanController::class, 'toggleActive'])->name('plans.toggle-active');

    // Subscriptions & Platform Billing
    Route::get('/subscriptions', [SubscriptionBillingController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{subscription}/record-payment', [SubscriptionBillingController::class, 'recordPayment'])->name('subscriptions.record-payment');
});
