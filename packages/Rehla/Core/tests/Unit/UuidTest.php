<?php

declare(strict_types=1);

use Rehla\Core\Identifiers\Uuid;

it('generates a valid UUID v4 string', function (): void {
    $uuid = Uuid::generate();

    expect((string) $uuid)
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($uuid->value())
        ->toBe((string) $uuid);
});

it('creates a UUID from an existing valid string', function (): void {
    $raw = '550e8400-e29b-41d4-a716-446655440000';
    $uuid = Uuid::fromString($raw);

    expect($uuid->value())->toBe($raw)
        ->and($uuid->equals(Uuid::fromString($raw)))->toBeTrue()
        ->and($uuid->equals(Uuid::generate()))->toBeFalse();
});

it('rejects an invalid UUID string', function (): void {
    expect(fn () => Uuid::fromString('not-a-uuid'))->toThrow(InvalidArgumentException::class);
});
