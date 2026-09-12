<?php

declare(strict_types=1);

use Rehla\Purchasing\Support\CanonicalPurchaseFingerprint;

it('produces one fingerprint for semantically identical payloads regardless of object key order', function (): void {
    $a = [
        'service_id' => 's1',
        'answers' => ['b' => 2, 'a' => 1],
        'documents' => ['d2', 'd1'],
    ];
    $b = [
        'documents' => ['d2', 'd1'],
        'answers' => ['a' => 1, 'b' => 2],
        'service_id' => 's1',
    ];

    expect(CanonicalPurchaseFingerprint::from($a))
        ->toBe(CanonicalPurchaseFingerprint::from($b))
        ->toHaveLength(64);
});

it('produces different fingerprints for different indexed array ordering', function (): void {
    $a = ['documents' => ['d1', 'd2']];
    $b = ['documents' => ['d2', 'd1']];

    expect(CanonicalPurchaseFingerprint::from($a))
        ->not->toBe(CanonicalPurchaseFingerprint::from($b));
});

it('produces different fingerprints for different values', function (): void {
    $a = ['service_id' => 's1', 'price_minor' => 1000];
    $b = ['service_id' => 's1', 'price_minor' => 2000];

    expect(CanonicalPurchaseFingerprint::from($a))
        ->not->toBe(CanonicalPurchaseFingerprint::from($b));
});
