<?php

declare(strict_types=1);

namespace Rehla\Documents\Services;

use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Documents\Data\DocumentRef;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Exceptions\DocumentNotClean;
use Rehla\Documents\Models\Document;

final class OwnedDocumentsService implements OwnedDocuments
{
    /**
     * @param  list<string>  $documentIds
     * @return list<DocumentRef>
     */
    public function assertCleanOwned(array $documentIds, string $ownerId, DocumentPurpose $purpose): array
    {
        if (empty($documentIds)) {
            return [];
        }

        $documents = Document::whereIn('id', $documentIds)->get();

        if ($documents->count() !== count($documentIds)) {
            throw new DocumentAccessDenied('One or more requested documents were not found');
        }

        $refs = [];
        foreach ($documents as $doc) {
            if ($doc->owner_id !== $ownerId) {
                throw new DocumentAccessDenied("Document {$doc->id} is not owned by account {$ownerId}");
            }

            if ($doc->purpose !== $purpose) {
                throw new DocumentAccessDenied("Document {$doc->id} purpose does not match {$purpose->value}");
            }

            if ($doc->status !== DocumentStatus::Clean && $doc->status !== DocumentStatus::Attached) {
                throw new DocumentNotClean("Document {$doc->id} is not clean (status: {$doc->status->value})");
            }

            $refs[] = $doc->toRef();
        }

        return $refs;
    }
}
