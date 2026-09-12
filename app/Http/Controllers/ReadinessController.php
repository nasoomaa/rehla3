<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ReadinessController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => 'failed',
            'storage' => 'failed',
            'cache' => 'failed',
        ];

        $healthy = true;

        // 1. Database check (PostgreSQL)
        try {
            if (app()->environment('testing') && request()->has('simulate_db_down')) {
                throw new \RuntimeException('Simulated db failure');
            }
            DB::connection()->getPdo()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (Throwable) {
            $healthy = false;
        }

        // 2. Storage disk metadata check
        try {
            Storage::disk('private')->exists('.probe');
            $checks['storage'] = 'ok';
        } catch (Throwable) {
            $healthy = false;
        }

        // 3. Cache store check
        try {
            Cache::store()->has('.health_probe');
            $checks['cache'] = 'ok';
        } catch (Throwable) {
            $healthy = false;
        }

        $statusCode = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'ready' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $statusCode);
    }
}
