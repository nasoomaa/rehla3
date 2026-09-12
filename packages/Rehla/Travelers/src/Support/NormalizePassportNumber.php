<?php

declare(strict_types=1);

namespace Rehla\Travelers\Support;

final class NormalizePassportNumber
{
    public static function normalize(string $passport): string
    {
        $upper = mb_strtoupper($passport, 'UTF-8');

        return (string) preg_replace('/[\p{Z}\s\-]+/u', '', $upper);
    }
}
