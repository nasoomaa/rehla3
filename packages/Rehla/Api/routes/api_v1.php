<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Rehla\Api\Http\Controllers\V1\Auth\LoginController;
use Rehla\Api\Http\Controllers\V1\Auth\LogoutController;
use Rehla\Api\Http\Controllers\V1\Auth\RegisterController;
use Rehla\Api\Http\Controllers\V1\BankAccountController;
use Rehla\Api\Http\Controllers\V1\MeController;
use Rehla\Api\Http\Controllers\V1\OrderSubmissionController;
use Rehla\Api\Http\Controllers\V1\ServiceController;
use Rehla\Api\Http\Controllers\V1\TopUpController;
use Rehla\Api\Http\Controllers\V1\TravelerController;
use Rehla\Api\Http\Controllers\V1\UploadController;
use Rehla\Api\Http\Controllers\V1\WalletController;
use Rehla\Api\Http\Middleware\FormatProblemDetails;
use Rehla\Api\Http\Middleware\RequireJson;
use Rehla\Api\Http\Middleware\ResolveApiLocale;

Route::middleware([RequireJson::class, ResolveApiLocale::class, FormatProblemDetails::class])
    ->prefix('api/v1')
    ->group(function (): void {
        // Public Auth routes (rate limited 5/min)
        Route::prefix('auth')->group(function (): void {
            Route::post('/register', RegisterController::class)->middleware('throttle:5,1');
            Route::post('/login', LoginController::class)->middleware('throttle:5,1');
        });

        // Public Catalog routes
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{slug}', [ServiceController::class, 'show']);
        Route::get('/services/{slug}/application-form', [ServiceController::class, 'applicationForm']);

        // Authenticated Customer routes
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/auth/logout', LogoutController::class);

            Route::get('/me', [MeController::class, 'show']);

            Route::get('/travelers', [TravelerController::class, 'index']);
            Route::post('/travelers', [TravelerController::class, 'store']);
            Route::get('/travelers/{id}', [TravelerController::class, 'show']);

            Route::get('/wallet', [WalletController::class, 'show']);
            Route::get('/wallet/entries', [WalletController::class, 'entries']);

            Route::get('/bank-accounts', [BankAccountController::class, 'index']);

            Route::get('/top-ups', [TopUpController::class, 'index']);
            Route::post('/top-ups', [TopUpController::class, 'store'])->middleware('throttle:10,60');

            Route::post('/uploads', [UploadController::class, 'store'])->middleware('throttle:20,60');

            Route::post('/order-submissions', OrderSubmissionController::class)->middleware('throttle:10,1');
        });
    });
