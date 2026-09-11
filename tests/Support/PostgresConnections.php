<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Factory for creating independent PostgreSQL connections for concurrency testing.
 * These connections bypass the test transaction wrapper so concurrent writes are visible.
 */
final class PostgresConnections
{
    /**
     * Open two independent raw PDO connections to the testing database.
     * The caller is responsible for closing them after use.
     *
     * @return array{Connection, Connection}
     */
    public static function independentPair(): array
    {
        AssertsSafeTestingDatabase::check();

        $config = config('database.connections.pgsql');

        return [
            self::makeConnection($config, 'pgsql_conn_a'),
            self::makeConnection($config, 'pgsql_conn_b'),
        ];
    }

    private static function makeConnection(array $config, string $name): Connection
    {
        config(["database.connections.{$name}" => $config]);

        return DB::connection($name);
    }
}
