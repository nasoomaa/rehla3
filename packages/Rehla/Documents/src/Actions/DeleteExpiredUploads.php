<?php

declare(strict_types=1);

namespace Rehla\Documents\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Models\Document;
use Rehla\Documents\Models\UploadSession;

final class DeleteExpiredUploads
{
    public function handle(): int
    {
        $deleted = 0;

        $expiredSessionIds = UploadSession::whereNull('claimed_at')
            ->where('expires_at', '<', CarbonImmutable::now())
            ->pluck('id');

        foreach ($expiredSessionIds as $sessionId) {
            DB::transaction(function () use ($sessionId, &$deleted): void {
                /** @var UploadSession|null $session */
                $session = UploadSession::where('id', $sessionId)
                    ->whereNull('claimed_at')
                    ->lockForUpdate()
                    ->first();

                if ($session === null) {
                    return;
                }

                // Never delete attached documents
                $docs = Document::where('upload_session_id', $session->id)
                    ->where('status', '!=', DocumentStatus::Attached)
                    ->whereNull('attached_at')
                    ->get();

                foreach ($docs as $doc) {
                    Storage::disk($doc->disk)->delete($doc->storage_key);
                    $doc->delete();
                }

                $session->delete();
                $deleted++;
            });
        }

        return $deleted;
    }
}
