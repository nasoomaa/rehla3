<?php

declare(strict_types=1);

use Rehla\Core\Money\Money;

it('keeps SDG arithmetic in integer minor units', function (): void {
    $balance = Money::sdg(5_000_00);
    $price = Money::sdg(2_500_00);

    expect($balance->subtract($price)->minor())->toBe(2_500_00)
        ->and($balance->currency())->toBe('SDG')
        ->and($price->add($price)->minor())->toBe(5_000_00)
        ->and($price->isLessThan($balance))->toBeTrue()
        ->and($balance->isLessThan($price))->toBeFalse();
});

it('rejects currency mismatch and negative construction', function (): void {
    expect(fn () => Money::sdg(-1))->toThrow(InvalidArgumentException::class);

    $a = Money::sdg(100);
    $b = Money::sdg(200);
    expect(fn () => $a->subtract($b))->toThrow(InvalidArgumentException::class);
});
