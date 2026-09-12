<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\DeleteExpiredUploads;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Models\Document;
use Rehla\Documents\Models\UploadSession;

beforeEach(function (): void {
    Storage::fake('private');
});

it('cleanup job never deletes attached or claimed documents even if session expires', function (): void {
    $owner = (string) Str::uuid();

    // Create session expiring in the past
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::Passport,
    ));

    $validPdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('passport.pdf', $validPdf);
    $document = app(StoreUpload::class)->handle($session->id, $file);

    app(ScanDocument::class)->handle($document->id);
    app(AttachDocument::class)->handle($document->id, $owner);

    // Fast forward time past expiration
    UploadSession::where('id', $session->id)->update([
        'expires_at' => CarbonImmutable::now()->subHour(),
    ]);

    // Run cleanup
    $deletedCount = app(DeleteExpiredUploads::class)->handle();

    // The attached document must still exist
    $doc = Document::find($document->id);
    expect($doc)->not->toBeNull()
        ->and($doc->status)->toBe(DocumentStatus::Attached);
});

it('cleanup job deletes unclaimed expired upload sessions and orphaned files', function (): void {
    $owner = (string) Str::uuid();

    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::Passport,
    ));

    $validPdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('orphan.pdf', $validPdf);
    $document = app(StoreUpload::class)->handle($session->id, $file);

    // Expire session without claiming or attaching
    UploadSession::where('id', $session->id)->update([
        'expires_at' => CarbonImmutable::now()->subHour(),
    ]);

    $deletedCount = app(DeleteExpiredUploads::class)->handle();
    expect($deletedCount)->toBeGreaterThanOrEqual(1);

    expect(Document::find($document->id))->toBeNull()
        ->and(UploadSession::find($session->id))->toBeNull()
        ->and(Storage::disk('private')->exists($document->storage_key))->toBeFalse();
});
