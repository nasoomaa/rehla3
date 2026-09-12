<?php

declare(strict_types=1);

namespace Rehla\Documents\Contracts;

interface DocumentScanner
{
    /**
     * @return array{clean: bool, rejection_code: ?string}
     */
    public function scan(string $storageKey, string $disk = 'private'): array;
}
