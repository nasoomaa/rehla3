<?php

declare(strict_types=1);

namespace Rehla\Documents\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Exceptions\DocumentNotClean;
use Rehla\Documents\Models\Document;
use Rehla\Documents\Models\UploadSession;

final class AttachDocument
{
    public function handle(string $documentId, string $ownerId): Document
    {
        return DB::transaction(function () use ($documentId, $ownerId): Document {
            /** @var Document $document */
            $document = Document::where('id', $documentId)->lockForUpdate()->firstOrFail();

            if ($document->owner_id !== $ownerId) {
                throw new DocumentAccessDenied("Document does not belong to owner {$ownerId}");
            }

            if ($document->status !== DocumentStatus::Clean) {
                throw new DocumentNotClean("Document is not in clean status (current: {$document->status->value})");
            }

            $now = CarbonImmutable::now();
            $document->status = DocumentStatus::Attached;
            $document->attached_at = $now;
            $document->save();

            if ($document->upload_session_id !== null) {
                UploadSession::where('id', $document->upload_session_id)
                    ->whereNull('claimed_at')
                    ->update(['claimed_at' => $now]);
            }

            return $document;
        });
    }
}
