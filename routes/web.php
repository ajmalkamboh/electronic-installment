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
});


