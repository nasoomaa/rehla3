<?php

declare(strict_types=1);

use Tests\Support\AssertsSafeTestingDatabase;

it('rejects an unsafe integration database', function (): void {
    config()->set('database.connections.pgsql.database', 'rehla');
    expect(fn () => AssertsSafeTestingDatabase::check())->toThrow(RuntimeException::class, '_testing');
});

it('accepts a database name ending in _testing', function (): void {
    config()->set('database.connections.pgsql.database', 'rehla_testing');
    config()->set('database.connections.pgsql.driver', 'pgsql');
    config()->set('database.default', 'pgsql');

    expect(fn () => AssertsSafeTestingDatabase::check())->not->toThrow(RuntimeException::class);
});
