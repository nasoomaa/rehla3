<?php

declare(strict_types=1);

namespace Rehla\Documents\Queries;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Models\Document;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;

final class AuthorizeDocumentDownload
{
    public function handle(string $documentId, ActorData $actor): Response
    {
        $document = Document::findOrFail($documentId);

        $isOwner = $document->owner_id === $actor->id;
        $isStaff = $actor->type === 'staff' && in_array(AbilityName::DocumentsView->value, $actor->abilities, true);

        if (! $isOwner && ! $isStaff) {
            throw new DocumentAccessDenied('Access to private document is denied');
        }

        if ($document->status === DocumentStatus::Rejected) {
            throw new DocumentAccessDenied('Rejected documents cannot be downloaded');
        }

        $contents = Storage::disk($document->disk)->get($document->storage_key);
        if ($contents === null) {
            abort(404, 'Document file not found in storage');
        }

        $safeName = basename($document->original_name);

        return response()->make($contents, 200, [
            'Content-Type' => $document->detected_mime,
            'Content-Length' => (string) strlen($contents),
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
