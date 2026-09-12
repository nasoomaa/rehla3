<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Guards against accidentally running integration tests against a non-testing database.
 * Must be called in setUp or a test hook before any schema-mutating operations.
 */
final class AssertsSafeTestingDatabase
{
    public static function check(): void
    {
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();
        $driver = $connection->getDriverName();

        if ($driver !== 'pgsql' || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                'Integration tests require a PostgreSQL database ending in _testing. '.
                "Got: driver={$driver}, database={$database}"
            );
        }
    }
}
