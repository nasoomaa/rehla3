<?php

declare(strict_types=1);

namespace Rehla\Documents\Actions;

use Carbon\CarbonImmutable;
use Rehla\Documents\Contracts\DocumentScanner;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Models\Document;

final class ScanDocument
{
    public function __construct(private DocumentScanner $scanner) {}

    public function handle(string $documentId): Document
    {
        $document = Document::findOrFail($documentId);

        // If already rejected, cannot be made clean
        if ($document->status === DocumentStatus::Rejected) {
            return $document;
        }

        // Transition to quarantined during scan
        if ($document->status === DocumentStatus::PendingScan) {
            $document->update(['status' => DocumentStatus::Quarantined]);
        }

        $result = $this->scanner->scan($document->storage_key, $document->disk);

        if (! $result['clean']) {
            $document->update([
                'status' => DocumentStatus::Rejected,
                'rejection_code' => $result['rejection_code'] ?? 'scan_failed',
                'scanned_at' => CarbonImmutable::now(),
            ]);
        } else {
            $document->update([
                'status' => DocumentStatus::Clean,
                'rejection_code' => null,
                'scanned_at' => CarbonImmutable::now(),
            ]);
        }

        return $document->fresh();
    }
}
