<?php

declare(strict_types=1);

namespace Rehla\TopUps\Support;

final class NormalizeTransactionReference
{
    public static function normalize(string $reference): string
    {
        // 1. Strip all Unicode whitespace (\p{Z}) and dashes/hyphens
        $cleaned = preg_replace('/[\p{Z}\p{Cc}\p{Cf}\-_]+/u', '', $reference) ?? '';

        // 2. Convert to uppercase
        return mb_strtoupper(trim($cleaned), 'UTF-8');
    }
}
