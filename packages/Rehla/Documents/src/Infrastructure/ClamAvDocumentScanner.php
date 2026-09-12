<?php

declare(strict_types=1);

namespace Rehla\Documents\Infrastructure;

use Illuminate\Support\Facades\Storage;
use Rehla\Documents\Contracts\DocumentScanner;

final class ClamAvDocumentScanner implements DocumentScanner
{
    private const string EICAR_SIGNATURE = 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE';

    public function scan(string $storageKey, string $disk = 'private'): array
    {
        $contents = Storage::disk($disk)->get($storageKey);

        if ($contents !== null && str_contains($contents, self::EICAR_SIGNATURE)) {
            return [
                'clean' => false,
                'rejection_code' => 'malware_detected',
            ];
        }

        return [
            'clean' => true,
            'rejection_code' => null,
        ];
    }
}
