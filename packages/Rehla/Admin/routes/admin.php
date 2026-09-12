<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Rehla\Admin\Http\Controllers\AuditLogController;
use Rehla\Admin\Http\Controllers\Auth\LoginController;
use Rehla\Admin\Http\Controllers\Auth\LogoutController;
use Rehla\Admin\Http\Controllers\Auth\MfaController;
use Rehla\Admin\Http\Controllers\BankAccountController;
use Rehla\Admin\Http\Controllers\ContentController;
use Rehla\Admin\Http\Controllers\CustomerController;
use Rehla\Admin\Http\Controllers\ExecutionController;
use Rehla\Admin\Http\Controllers\FormController;
use Rehla\Admin\Http\Controllers\NotificationController;
use Rehla\Admin\Http\Controllers\OrderController;
use Rehla\Admin\Http\Controllers\OverviewController;
use Rehla\Admin\Http\Controllers\RolePermissionController;
use Rehla\Admin\Http\Controllers\ServiceController;
use Rehla\Admin\Http\Controllers\TopUpController;
use Rehla\Admin\Http\Controllers\TravelerController;
use Rehla\Admin\Http\Controllers\WalletController;

Route::middleware(['web'])->prefix('admin')->group(function (): void {
    // Guest auth routes
    Route::get('/login', [LoginController::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginController::class, 'login']);

    // Authenticated admin routes
    Route::middleware(['admin.auth'])->group(function (): void {
        Route::post('/logout', [LogoutController::class, 'logout'])->name('admin.logout');
        Route::get('/mfa', [MfaController::class, 'show'])->name('admin.mfa');
        Route::post('/mfa', [MfaController::class, 'confirm']);

        // 1. Overview (reporting.view)
        Route::middleware(['admin.ability:reporting.view'])->group(function (): void {
            Route::get('/', [OverviewController::class, 'index']);
            Route::get('/overview', [OverviewController::class, 'index'])->name('admin.overview');
        });

        // 2. Services (services.manage)
        Route::middleware(['admin.ability:services.manage'])->group(function (): void {
            Route::get('/services', [ServiceController::class, 'index'])->name('admin.services');
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{id}/content', [ServiceController::class, 'updateContent']);
            Route::post('/services/{id}/price', [ServiceController::class, 'changePrice']);
            Route::post('/services/{id}/publish', [ServiceController::class, 'publish']);
            Route::post('/services/{id}/deactivate', [ServiceController::class, 'deactivate']);
        });

        // 3. Application Forms (forms.manage)
        Route::middleware(['admin.ability:forms.manage'])->group(function (): void {
            Route::get('/application-forms', [FormController::class, 'index'])->name('admin.forms');
            Route::post('/application-forms/{serviceId}/versions', [FormController::class, 'storeVersion']);
            Route::post('/application-forms/{serviceId}/draft', [FormController::class, 'storeVersion']);
            Route::put('/application-forms/{id}/draft', [FormController::class, 'updateDraft']);
            Route::post('/application-forms/{id}/publish', [FormController::class, 'publishVersion']);
        });

        // 4. Customers (customers.view)
        Route::middleware(['admin.ability:customers.view'])->group(function (): void {
            Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers');
        });

        // 5. Travelers (travelers.view)
        Route::middleware(['admin.ability:travelers.view'])->group(function (): void {
            Route::get('/travelers', [TravelerController::class, 'index'])->name('admin.travelers');
        });

        // 6. Wallets (wallets.view)
        Route::middleware(['admin.ability:wallets.view'])->group(function (): void {
            Route::get('/wallets', [WalletController::class, 'index'])->name('admin.wallets');
        });

        // 7. Bank Accounts (bank_accounts.manage)
        Route::middleware(['admin.ability:bank_accounts.manage'])->group(function (): void {
            Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('admin.bank-accounts');
            Route::post('/bank-accounts', [BankAccountController::class, 'store']);
            Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update']);
            Route::post('/bank-accounts/{id}/deactivate', [BankAccountController::class, 'deactivate']);
        });

        // 8. Top-up Requests (topups.review - sensitive, requires MFA)
        Route::middleware(['admin.ability:topups.review'])->group(function (): void {
            Route::get('/top-up-requests', [TopUpController::class, 'index'])->name('admin.topups');
            Route::post('/top-up-requests/{id}/approve', [TopUpController::class, 'approve']);
            Route::post('/top-up-requests/{id}/reject', [TopUpController::class, 'reject']);
        });

        // 9. Orders (orders.view)
        Route::middleware(['admin.ability:orders.view'])->group(function (): void {
            Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
        });

        // 10. Service Executions (executions.manage)
        Route::middleware(['admin.ability:executions.manage'])->group(function (): void {
            Route::get('/service-executions', [ExecutionController::class, 'index'])->name('admin.executions');
            Route::get('/service-executions/{id}', [ExecutionController::class, 'show']);
            Route::post('/service-executions/{id}/transition', [ExecutionController::class, 'transition']);
            Route::post('/service-executions/{id}/customer-action', [ExecutionController::class, 'requestCustomerAction']);
            Route::post('/service-executions/{id}/note', [ExecutionController::class, 'addNote']);
        });

        // 11. Content (content.manage)
        Route::middleware(['admin.ability:content.manage'])->group(function (): void {
            Route::get('/content', [ContentController::class, 'index'])->name('admin.content');
            Route::post('/content', [ContentController::class, 'store']);
        });

        // 12. Notifications (notifications.manage)
        Route::middleware(['admin.ability:notifications.manage'])->group(function (): void {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
        });

        // 13. Roles & Permissions (roles.manage - sensitive, requires MFA)
        Route::middleware(['admin.ability:roles.manage'])->group(function (): void {
            Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->name('admin.roles');
            Route::post('/roles-permissions/assign', [RolePermissionController::class, 'assign']);
            Route::post('/roles-permissions/revoke', [RolePermissionController::class, 'revoke']);
        });

        // 14. Audit Log (audit.view - sensitive, requires MFA)
        Route::middleware(['admin.ability:audit.view'])->group(function (): void {
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('admin.audit');
        });
    });
});
