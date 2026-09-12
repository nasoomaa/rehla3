<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Actions\AttachDocument;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Contracts\OwnedDocuments;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Documents\Enums\DocumentStatus;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Exceptions\DocumentNotClean;
use Rehla\Documents\Exceptions\InvalidFileException;
use Rehla\Documents\Queries\AuthorizeDocumentDownload;
use Rehla\Identity\Data\ActorData;

beforeEach(function (): void {
    Storage::fake('private');
});

it('allows attachment only for a clean document owned by the account', function (): void {
    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    // 1. Begin upload session
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $ownerA,
        purpose: DocumentPurpose::Passport,
    ));

    // Valid 1-page PDF
    $validPdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('passport.pdf', $validPdfContent);

    // 2. Store upload
    $document = app(StoreUpload::class)->handle($session->id, $file);

    expect($document->status)->toBe(DocumentStatus::PendingScan);

    // Owner B cannot claim owner A's document
    expect(fn () => app(OwnedDocuments::class)->assertCleanOwned([$document->id], $ownerB, DocumentPurpose::Passport))
        ->toThrow(DocumentAccessDenied::class);

    // Cannot attach while not clean
    expect(fn () => app(AttachDocument::class)->handle($document->id, $ownerA))
        ->toThrow(DocumentNotClean::class);

    // 3. Scan document as clean
    $scanned = app(ScanDocument::class)->handle($document->id);
    expect($scanned->status)->toBe(DocumentStatus::Clean);

    // Assert clean owned succeeds for owner A
    $refs = app(OwnedDocuments::class)->assertCleanOwned([$document->id], $ownerA, DocumentPurpose::Passport);
    expect($refs)->toHaveCount(1)
        ->and($refs[0]->id)->toBe($document->id);

    // 4. Attach document
    $attached = app(AttachDocument::class)->handle($document->id, $ownerA);
    expect($attached->status)->toBe(DocumentStatus::Attached)
        ->and($attached->attached_at)->not->toBeNull();
});

it('rejects upload when declared MIME contradicts magic bytes', function (): void {
    $owner = (string) Str::uuid();
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::Passport,
    ));

    // Declared PDF but contents are an executable ELF header
    $fakeFile = UploadedFile::fake()->createWithContent('fake.pdf', "\x7fELF\x01\x01\x01\x00malicious_code");

    expect(fn () => app(StoreUpload::class)->handle($session->id, $fakeFile))
        ->toThrow(InvalidFileException::class);
});

it('rejects a file exceeding the maximum size limit', function (): void {
    $owner = (string) Str::uuid();
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::Passport,
    ));

    // Exceeding 10MB limit (11MB)
    $largeContent = "%PDF-1.4\n".str_repeat('A', 11 * 1024 * 1024)."\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('large.pdf', $largeContent);

    expect(fn () => app(StoreUpload::class)->handle($session->id, $file))
        ->toThrow(InvalidFileException::class);
});

it('rejects malware infected documents and prevents transition to clean', function (): void {
    $owner = (string) Str::uuid();
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::BankReceipt,
    ));

    // EICAR standard antivirus test string embedded in PDF
    $eicarPdf = "%PDF-1.4\n".'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*'."\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('eicar.pdf', $eicarPdf);

    $document = app(StoreUpload::class)->handle($session->id, $file);
    $scanned = app(ScanDocument::class)->handle($document->id);

    expect($scanned->status)->toBe(DocumentStatus::Rejected)
        ->and($scanned->rejection_code)->not->toBeNull();

    // Re-scanning rejected document must NOT turn it into clean
    $reScanned = app(ScanDocument::class)->handle($document->id);
    expect($reScanned->status)->toBe(DocumentStatus::Rejected);
});

it('authorizes private download with security headers and no storage leak', function (): void {
    $owner = (string) Str::uuid();
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $owner,
        purpose: DocumentPurpose::Passport,
    ));

    $validPdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('client_passport.pdf', $validPdf);
    $doc = app(StoreUpload::class)->handle($session->id, $file);
    app(ScanDocument::class)->handle($doc->id);

    $ownerActor = new ActorData(
        id: $owner,
        type: 'customer',
        mfaConfirmedAt: null,
        abilities: [],
    );

    $response = app(AuthorizeDocumentDownload::class)->handle($doc->id, $ownerActor);

    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Cache-Control'))->toContain('private')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');

    // Storage path should not be in the response content or headers
    expect($response->getContent())->not->toContain($doc->storage_key);
});
