<?php

declare(strict_types=1);

namespace Rehla\Purchasing\Support;

final class CanonicalPurchaseFingerprint
{
    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    public static function from(mixed $payload): string
    {
        $canonical = self::canonicalize($payload);
        $json = json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $json);
    }

    private static function canonicalize(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map([self::class, 'canonicalize'], $data);
        }

        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = self::canonicalize($value);
        }

        ksort($result, SORT_STRING);

        return $result;
    }
}
