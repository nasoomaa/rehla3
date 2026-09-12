<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Rehla\Api\Http\Controllers\V1\Auth\LoginController;
use Rehla\Api\Http\Controllers\V1\Auth\LogoutController;
use Rehla\Api\Http\Controllers\V1\Auth\RegisterController;
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

        // Authenticated Customer routes
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/auth/logout', LogoutController::class);

            Route::post('/order-submissions', function () {
                return response()->json(['status' => 'pending']);
            });
        });
    });
