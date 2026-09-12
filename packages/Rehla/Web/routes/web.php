<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Rehla\Web\Http\Controllers\Account\NotificationController;
use Rehla\Web\Http\Controllers\Account\OrderController;
use Rehla\Web\Http\Controllers\Account\ProfileController;
use Rehla\Web\Http\Controllers\Account\TopUpController;
use Rehla\Web\Http\Controllers\Account\TravelerController;
use Rehla\Web\Http\Controllers\Account\WalletController;
use Rehla\Web\Http\Controllers\AuthController;
use Rehla\Web\Http\Controllers\HomeController;
use Rehla\Web\Http\Controllers\LocaleController;
use Rehla\Web\Http\Controllers\ServiceController;
use Rehla\Web\Livewire\Account\CustomerActionResponse;
use Rehla\Web\Livewire\Account\OrderCheckout;
use Rehla\Web\Livewire\Account\OrderShow;
use Rehla\Web\Livewire\Account\TopUpCreate;

Route::middleware('web')->group(function (): void {
    // Locale switcher
    Route::get('/locale/{locale}', LocaleController::class)->name('locale');

    // Public catalog
    Route::get('/', HomeController::class)->name('home');
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

    // Guest Auth
    Route::middleware('guest:web')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Authenticated Customer
    Route::middleware('auth:web')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/checkout/{slug}', OrderCheckout::class)->name('checkout');

        Route::prefix('account')->name('account.')->group(function (): void {
            Route::get('/profile', ProfileController::class)->name('profile');

            Route::get('/travelers', [TravelerController::class, 'index'])->name('travelers.index');
            Route::get('/travelers/create', [TravelerController::class, 'create'])->name('travelers.create');
            Route::post('/travelers', [TravelerController::class, 'store'])->name('travelers.store');
            Route::get('/travelers/{id}/edit', [TravelerController::class, 'edit'])->name('travelers.edit');
            Route::put('/travelers/{id}', [TravelerController::class, 'update'])->name('travelers.update');

            Route::get('/wallet', WalletController::class)->name('wallet');

            Route::get('/top-ups', [TopUpController::class, 'index'])->name('top-ups.index');
            Route::get('/top-ups/create', TopUpCreate::class)->name('top-ups.create');

            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{id}', OrderShow::class)->name('orders.show');

            Route::get('/actions/{actionRequestId}', CustomerActionResponse::class)->name('actions.respond');

            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        });
    });
});
