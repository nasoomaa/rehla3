<?php

declare(strict_types=1);

use Rehla\Core\Errors\ErrorCode;

it('defines all required RFC 7807 problem detail error codes', function (): void {
    expect(ErrorCode::INSUFFICIENT_BALANCE->value)->toBe('INSUFFICIENT_BALANCE')
        ->and(ErrorCode::SERVICE_UNAVAILABLE->value)->toBe('SERVICE_UNAVAILABLE')
        ->and(ErrorCode::PRICE_CHANGED->value)->toBe('PRICE_CHANGED')
        ->and(ErrorCode::FORM_VERSION_CHANGED->value)->toBe('FORM_VERSION_CHANGED')
        ->and(ErrorCode::DUPLICATE_PASSPORT->value)->toBe('DUPLICATE_PASSPORT')
        ->and(ErrorCode::TRANSACTION_REFERENCE_USED->value)->toBe('TRANSACTION_REFERENCE_USED')
        ->and(ErrorCode::DOCUMENT_NOT_CLEAN->value)->toBe('DOCUMENT_NOT_CLEAN');
});
