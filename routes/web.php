<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\BranchController;
use App\Http\Controllers\Tenant\BranchSwitchController;
use App\Http\Controllers\Tenant\CompanySettingsController;
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
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::post('/branches/{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle-status');

    // Company Profile & Tenant Settings
    Route::get('/company/settings', [CompanySettingsController::class, 'edit'])->name('company.settings.edit');
    Route::put('/company/settings', [CompanySettingsController::class, 'update'])->name('company.settings.update');

    // Employee & Staff Lifecycle Management
    Route::get('/staff', [\App\Http\Controllers\Tenant\StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [\App\Http\Controllers\Tenant\StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [\App\Http\Controllers\Tenant\StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [\App\Http\Controllers\Tenant\StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [\App\Http\Controllers\Tenant\StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{staff}/toggle-status', [\App\Http\Controllers\Tenant\StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
    Route::post('/staff/{staff}/reset-password', [\App\Http\Controllers\Tenant\StaffController::class, 'resetPassword'])->name('staff.reset-password');

    // Role & Permissions (RBAC) Management
    Route::get('/roles', [\App\Http\Controllers\Tenant\RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [\App\Http\Controllers\Tenant\RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [\App\Http\Controllers\Tenant\RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [\App\Http\Controllers\Tenant\RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [\App\Http\Controllers\Tenant\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\Tenant\RoleController::class, 'destroy'])->name('roles.destroy');

    // Customer & Debtor Management
    Route::get('/customers', [\App\Http\Controllers\Tenant\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [\App\Http\Controllers\Tenant\CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [\App\Http\Controllers\Tenant\CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [\App\Http\Controllers\Tenant\CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [\App\Http\Controllers\Tenant\CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [\App\Http\Controllers\Tenant\CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers/{customer}/toggle-status', [\App\Http\Controllers\Tenant\CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // Guarantors
    Route::post('/customers/{customer}/guarantors', [\App\Http\Controllers\Tenant\GuarantorController::class, 'store'])->name('customers.guarantors.store');
    Route::post('/guarantors/{guarantor}/toggle-verified', [\App\Http\Controllers\Tenant\GuarantorController::class, 'toggleVerified'])->name('guarantors.toggle-verified');
    Route::delete('/guarantors/{guarantor}', [\App\Http\Controllers\Tenant\GuarantorController::class, 'destroy'])->name('guarantors.destroy');

    // Field Verifications
    Route::post('/customers/{customer}/verifications', [\App\Http\Controllers\Tenant\CustomerVerificationController::class, 'store'])->name('customers.verifications.store');

    // Credit Underwriting & Assessments (Phase 06)
    Route::get('/credit/assessments', [\App\Http\Controllers\Tenant\CreditAssessmentController::class, 'index'])->name('credit.assessments.index');
    Route::get('/customers/{customer}/assessments/create', [\App\Http\Controllers\Tenant\CreditAssessmentController::class, 'create'])->name('customers.assessments.create');
    Route::post('/customers/{customer}/assessments', [\App\Http\Controllers\Tenant\CreditAssessmentController::class, 'store'])->name('customers.assessments.store');
    Route::get('/credit/assessments/{creditAssessment}', [\App\Http\Controllers\Tenant\CreditAssessmentController::class, 'show'])->name('credit.assessments.show');

    // Credit Approvals (Phase 06)
    Route::get('/credit/approvals', [\App\Http\Controllers\Tenant\CreditApprovalController::class, 'index'])->name('credit.approvals.index');
    Route::post('/credit/assessments/{creditAssessment}/approve', [\App\Http\Controllers\Tenant\CreditApprovalController::class, 'store'])->name('credit.assessments.approve');

    // Blacklist Registry (Phase 06)
    Route::post('/customers/{customer}/blacklist', [\App\Http\Controllers\Tenant\BlacklistController::class, 'toggle'])->name('customers.blacklist.toggle');

    // Product Categories (Phase 07)
    Route::get('/categories', [\App\Http\Controllers\Tenant\ProductCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Tenant\ProductCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\App\Http\Controllers\Tenant\ProductCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\App\Http\Controllers\Tenant\ProductCategoryController::class, 'destroy'])->name('categories.destroy');

    // Wholesale Suppliers (Phase 07)
    Route::get('/suppliers', [\App\Http\Controllers\Tenant\SupplierController::class, 'index'])->name('suppliers.index');
    Route::post('/suppliers', [\App\Http\Controllers\Tenant\SupplierController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{supplier}', [\App\Http\Controllers\Tenant\SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [\App\Http\Controllers\Tenant\SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Products Master Catalog (Phase 07)
    Route::get('/products', [\App\Http\Controllers\Tenant\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [\App\Http\Controllers\Tenant\ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [\App\Http\Controllers\Tenant\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [\App\Http\Controllers\Tenant\ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [\App\Http\Controllers\Tenant\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [\App\Http\Controllers\Tenant\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [\App\Http\Controllers\Tenant\ProductController::class, 'destroy'])->name('products.destroy');

    // Showroom Inventory & Serialized Hardware (Phase 07)
    Route::get('/inventory', [\App\Http\Controllers\Tenant\InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/serialized', [\App\Http\Controllers\Tenant\InventoryController::class, 'serializedIndex'])->name('inventory.serialized');
    Route::get('/inventory/receipt', [\App\Http\Controllers\Tenant\InventoryController::class, 'createReceipt'])->name('inventory.receipt.create');
    Route::post('/inventory/receipt', [\App\Http\Controllers\Tenant\InventoryController::class, 'storeReceipt'])->name('inventory.receipt.store');
    Route::get('/inventory/serialized/{item}/transfer', [\App\Http\Controllers\Tenant\InventoryController::class, 'createTransfer'])->name('inventory.transfer.create');
    Route::post('/inventory/serialized/{item}/transfer', [\App\Http\Controllers\Tenant\InventoryController::class, 'storeTransfer'])->name('inventory.transfer.store');
    Route::get('/inventory/movements', [\App\Http\Controllers\Tenant\InventoryController::class, 'movements'])->name('inventory.movements');

    // Installment Plans & Pricing Engine (Phase 08)
    Route::get('/plans', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'toggle'])->name('plans.toggle');
    Route::delete('/plans/{plan}', [\App\Http\Controllers\Tenant\InstallmentPlanController::class, 'destroy'])->name('plans.destroy');

    // Pricing Calculator & Quotation Simulator (Phase 08)
    Route::get('/pricing/calculator', [\App\Http\Controllers\Tenant\PricingCalculatorController::class, 'index'])->name('pricing.calculator');
    Route::post('/pricing/calculate', [\App\Http\Controllers\Tenant\PricingCalculatorController::class, 'calculate'])->name('pricing.calculate');

    // Installment Agreements & Contracts (Phase 09)
    Route::get('/agreements', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/create', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'create'])->name('agreements.create');
    Route::post('/agreements', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'store'])->name('agreements.store');
    Route::get('/agreements/{agreement}', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'show'])->name('agreements.show');
    Route::post('/agreements/{agreement}/submit', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'submit'])->name('agreements.submit');
    Route::post('/agreements/{agreement}/approve', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'approve'])->name('agreements.approve');
    Route::post('/agreements/{agreement}/down-payment', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'recordDownPayment'])->name('agreements.down-payment');
    Route::post('/agreements/{agreement}/disburse', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'disburse'])->name('agreements.disburse');
    Route::post('/agreements/{agreement}/cancel', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'cancel'])->name('agreements.cancel');
    Route::get('/agreements/{agreement}/print', [\App\Http\Controllers\Tenant\InstallmentAgreementController::class, 'print'])->name('agreements.print');

    // Payment Engine & Schedule Ledger (Phase 10)
    Route::get('/payments', [\App\Http\Controllers\Tenant\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [\App\Http\Controllers\Tenant\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [\App\Http\Controllers\Tenant\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [\App\Http\Controllers\Tenant\PaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/print', [\App\Http\Controllers\Tenant\PaymentController::class, 'print'])->name('payments.print');

    // Collection Officer & Recovery Workflow (Phase 11)
    Route::get('/collections/dashboard', [\App\Http\Controllers\Tenant\CollectionController::class, 'dashboard'])->name('collections.dashboard');
    Route::get('/collections/run-sheet', [\App\Http\Controllers\Tenant\CollectionController::class, 'runSheet'])->name('collections.run-sheet');
    Route::get('/collections/run-sheet/print', [\App\Http\Controllers\Tenant\CollectionController::class, 'printRunSheet'])->name('collections.run-sheet.print');
    Route::get('/collections/assignments', [\App\Http\Controllers\Tenant\CollectionController::class, 'assignments'])->name('collections.assignments');
    Route::post('/collections/assignments', [\App\Http\Controllers\Tenant\CollectionController::class, 'assign'])->name('collections.assignments.store');
    Route::post('/collections/logs', [\App\Http\Controllers\Tenant\CollectionController::class, 'logVisit'])->name('collections.logs.store');
    Route::post('/collections/payments', [\App\Http\Controllers\Tenant\CollectionController::class, 'collectFieldPayment'])->name('collections.payments.store');
    Route::get('/collections/handovers', [\App\Http\Controllers\Tenant\CollectionController::class, 'handovers'])->name('collections.handovers');
    Route::post('/collections/handovers/acknowledge', [\App\Http\Controllers\Tenant\CollectionController::class, 'acknowledgeHandover'])->name('collections.handovers.acknowledge');

    // Late Fee & Recovery Workflow (Phase 12)
    Route::get('/recovery', [\App\Http\Controllers\Tenant\RecoveryController::class, 'dashboard'])->name('recovery.dashboard');
    Route::post('/recovery/run-assessment', [\App\Http\Controllers\Tenant\RecoveryController::class, 'runAssessment'])->name('recovery.run-assessment');
    Route::get('/recovery/late-fees', [\App\Http\Controllers\Tenant\RecoveryController::class, 'lateFees'])->name('recovery.late-fees');
    Route::post('/recovery/late-fees/{schedule}/waive', [\App\Http\Controllers\Tenant\RecoveryController::class, 'waiveLateFee'])->name('recovery.late-fees.waive');
    Route::get('/recovery/cases', [\App\Http\Controllers\Tenant\RecoveryController::class, 'cases'])->name('recovery.cases.index');
    Route::get('/recovery/cases/{case}', [\App\Http\Controllers\Tenant\RecoveryController::class, 'showCase'])->name('recovery.cases.show');
    Route::post('/recovery/cases/{case}/issue-notice', [\App\Http\Controllers\Tenant\RecoveryController::class, 'issueNotice'])->name('recovery.cases.issue-notice');
    Route::get('/recovery/notices/{notice}/print', [\App\Http\Controllers\Tenant\RecoveryController::class, 'printNotice'])->name('recovery.notices.print');
    Route::post('/recovery/cases/{case}/authorize-repossession', [\App\Http\Controllers\Tenant\RecoveryController::class, 'authorizeRepossession'])->name('recovery.cases.authorize-repossession');
    Route::post('/recovery/cases/{case}/execute-repossession', [\App\Http\Controllers\Tenant\RecoveryController::class, 'executeRepossession'])->name('recovery.cases.execute-repossession');
    Route::post('/recovery/cases/{case}/write-off', [\App\Http\Controllers\Tenant\RecoveryController::class, 'writeOff'])->name('recovery.cases.write-off');

    // Legal Documents, Print Center & PDF Engine (Phase 13)
    Route::get('/documents/hub', [\App\Http\Controllers\Tenant\DocumentController::class, 'hub'])->name('documents.hub');
    Route::get('/documents/agreement/{agreement}', [\App\Http\Controllers\Tenant\DocumentController::class, 'agreementCenter'])->name('documents.agreement');
    Route::get('/documents/print/{type}/{agreement}', [\App\Http\Controllers\Tenant\DocumentController::class, 'print'])->name('documents.print');
    Route::get('/documents/receipt/{payment}', [\App\Http\Controllers\Tenant\DocumentController::class, 'printReceipt'])->name('documents.receipt');
    Route::post('/documents/agreement/{agreement}/noc', [\App\Http\Controllers\Tenant\DocumentController::class, 'issueNoc'])->name('documents.issue-noc');

    // Financial & General Ledger Engine (Phase 14)
    Route::get('/accounting/chart-of-accounts', [\App\Http\Controllers\Tenant\AccountingController::class, 'chartOfAccounts'])->name('accounting.coa');
    Route::post('/accounting/chart-of-accounts', [\App\Http\Controllers\Tenant\AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
    Route::get('/accounting/journal', [\App\Http\Controllers\Tenant\AccountingController::class, 'journal'])->name('accounting.journal');
    Route::get('/accounting/journal/create', [\App\Http\Controllers\Tenant\AccountingController::class, 'createJournal'])->name('accounting.journal.create');
    Route::post('/accounting/journal', [\App\Http\Controllers\Tenant\AccountingController::class, 'storeJournal'])->name('accounting.journal.store');
    Route::get('/accounting/trial-balance', [\App\Http\Controllers\Tenant\AccountingController::class, 'trialBalance'])->name('accounting.trial-balance');
    Route::get('/accounting/profit-loss', [\App\Http\Controllers\Tenant\AccountingController::class, 'profitLoss'])->name('accounting.profit-loss');
    Route::get('/accounting/balance-sheet', [\App\Http\Controllers\Tenant\AccountingController::class, 'balanceSheet'])->name('accounting.balance-sheet');
    Route::get('/accounting/cash-book', [\App\Http\Controllers\Tenant\AccountingController::class, 'cashBook'])->name('accounting.cash-book');
    Route::get('/accounting/account/{account}/ledger', [\App\Http\Controllers\Tenant\AccountingController::class, 'accountLedger'])->name('accounting.account-ledger');

    // SMS & WhatsApp Notifications Engine (Phase 15)
    Route::get('/notifications', [\App\Http\Controllers\Tenant\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [\App\Http\Controllers\Tenant\NotificationController::class, 'sendCustom'])->name('notifications.send');
    Route::post('/notifications/{notification}/retry', [\App\Http\Controllers\Tenant\NotificationController::class, 'retry'])->name('notifications.retry');
    Route::get('/notifications/templates', [\App\Http\Controllers\Tenant\NotificationController::class, 'templates'])->name('notifications.templates');
    Route::put('/notifications/templates/{template}', [\App\Http\Controllers\Tenant\NotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
    Route::get('/notifications/settings', [\App\Http\Controllers\Tenant\NotificationController::class, 'settings'])->name('notifications.settings');
    Route::post('/notifications/settings', [\App\Http\Controllers\Tenant\NotificationController::class, 'updateSettings'])->name('notifications.settings.update');

    // Reporting & Analytics Engine (Phase 16)
    Route::get('/analytics/dashboard', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('/analytics/aging', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'aging'])->name('analytics.aging');
    Route::get('/analytics/collections', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'collections'])->name('analytics.collections');
    Route::get('/analytics/branches', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'branches'])->name('analytics.branches');
    Route::get('/analytics/products', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'products'])->name('analytics.products');
    Route::get('/analytics/export/{type}', [\App\Http\Controllers\Tenant\AnalyticsController::class, 'export'])->name('analytics.export');

    // Multi-Branch Inventory Transfers & Gate Passes (Phase 17)
    Route::get('/transfers', [\App\Http\Controllers\Tenant\TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [\App\Http\Controllers\Tenant\TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [\App\Http\Controllers\Tenant\TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [\App\Http\Controllers\Tenant\TransferController::class, 'show'])->name('transfers.show');
    Route::post('/transfers/{transfer}/approve', [\App\Http\Controllers\Tenant\TransferController::class, 'approve'])->name('transfers.approve');
    Route::post('/transfers/{transfer}/dispatch', [\App\Http\Controllers\Tenant\TransferController::class, 'dispatchOrder'])->name('transfers.dispatch');
    Route::post('/transfers/{transfer}/receive', [\App\Http\Controllers\Tenant\TransferController::class, 'receiveOrder'])->name('transfers.receive');
    Route::post('/transfers/{transfer}/cancel', [\App\Http\Controllers\Tenant\TransferController::class, 'cancel'])->name('transfers.cancel');
    Route::get('/transfers/{transfer}/gate-pass', [\App\Http\Controllers\Tenant\TransferController::class, 'gatePass'])->name('transfers.gate-pass');
});



