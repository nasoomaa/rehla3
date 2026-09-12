<?php

declare(strict_types=1);

use App\Http\Controllers\LivenessController;
use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/up', LivenessController::class)->name('health.liveness');
Route::get('/ready', ReadinessController::class)->name('health.readiness');
