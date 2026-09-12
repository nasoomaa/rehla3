<?php

declare(strict_types=1);

namespace Rehla\Documents\Actions;

use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Exceptions\InvalidFileException;
use Rehla\Documents\Models\Document;
use Rehla\Documents\Models\UploadSession;

final class StoreUpload
{
    private const int MAX_BYTES = 10 * 1024 * 1024; // 10MB

    private const array ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function handle(string $sessionId, UploadedFile $file): Document
    {
        $session = UploadSession::findOrFail($sessionId);

        if ($session->expires_at->isPast()) {
            throw new InvalidFileException('Upload session has expired');
        }

        if ($session->claimed_at !== null) {
            throw new InvalidFileException('Upload session has already been claimed');
        }

        $size = $file->getSize();
        if ($size > self::MAX_BYTES) {
            throw new InvalidFileException("File size ({$size} bytes) exceeds maximum permitted threshold");
        }

        $contents = (string) file_get_contents($file->getRealPath());

        // 1. Detect magic bytes MIME
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->buffer($contents);

        // Disallow executable headers
        if (str_starts_with($contents, "\x7fELF") || str_starts_with($contents, 'MZ')) {
            throw new InvalidFileException('Executable file headers detected in magic bytes');
        }

        // Validate declared PDF matches magic bytes
        $declaredExt = strtolower((string) $file->getClientOriginalExtension());
        if ($declaredExt === 'pdf' || $file->getClientMimeType() === 'application/pdf') {
            if ($detectedMime !== 'application/pdf' || ! str_starts_with($contents, '%PDF-')) {
                throw new InvalidFileException('Declared PDF violates PDF magic byte signature');
            }
        }

        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw new InvalidFileException("MIME type '{$detectedMime}' is not permitted");
        }

        $storageKey = 'documents/'.Str::uuid().'.bin';
        $sha256 = hash('sha256', $contents);

        Storage::disk('private')->put($storageKey, $contents);

        return Document::create([
            'id' => (string) Str::uuid(),
            'upload_session_id' => $session->id,
            'owner_id' => $session->owner_id,
            'purpose' => $session->purpose,
            'disk' => 'private',
            'storage_key' => $storageKey,
            'original_name' => $file->getClientOriginalName(),
            'detected_mime' => $detectedMime,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'status' => DocumentStatus::PendingScan,
        ]);
    }
}
