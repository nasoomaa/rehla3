<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Exceptions\DocumentAccessDenied;
use Rehla\Documents\Queries\AuthorizeDocumentDownload;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Data\RegisterCustomerData;

beforeEach(function (): void {
    $this->now = CarbonImmutable::now();
    Storage::fake('private');
});

it('verifies private documents cannot be leaked publicly and require strict authorization', function (): void {
    $custA = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Doc Owner',
        email: 'doc_owner_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $custB = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Doc Attacker',
        email: 'doc_attacker_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $randomUuidKey = 'documents/'.(string) Str::uuid().'.bin';
    Storage::disk('private')->put($randomUuidKey, '%PDF-1.4 test secure document');

    $docId = (string) Str::uuid();
    DB::table('documents')->insert([
        'id' => $docId,
        'owner_id' => $custA->id,
        'purpose' => 'passport_scan',
        'disk' => 'private',
        'storage_key' => $randomUuidKey,
        'original_name' => 'sensitive_passport.pdf',
        'detected_mime' => 'application/pdf',
        'size_bytes' => 30,
        'sha256' => hash('sha256', '%PDF-1.4 test secure document'),
        'status' => 'clean',
        'scanned_at' => $this->now,
        'created_at' => $this->now,
        'updated_at' => $this->now,
    ]);

    // 1. Storage key format never leaks user identity or sensitive filenames
    expect($randomUuidKey)->toMatch('/^documents\/[0-9a-fA-F-]{36}\.bin$/');
    expect($randomUuidKey)->not->toContain('sensitive_passport');
    expect($randomUuidKey)->not->toContain($custA->email);

    // 2. Customer B (attacker) is denied download access
    $actorB = new ActorData(
        id: $custB->id,
        type: 'customer'
    );

    expect(fn () => app(AuthorizeDocumentDownload::class)->handle($docId, $actorB))
        ->toThrow(DocumentAccessDenied::class);

    // 3. Authorized owner can download and response enforces strict security headers
    $actorA = new ActorData(
        id: $custA->id,
        type: 'customer'
    );

    $response = app(AuthorizeDocumentDownload::class)->handle($docId, $actorA);
    expect($response->getStatusCode())->toBe(200);

    // Security headers verification
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('prohibits downloading unscanned or rejected documents', function (): void {
    $cust = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'Quarantine Customer',
        email: 'doc_quarantine_'.Str::random(8).'@example.com',
        password: 'Password123!',
    ));

    $randomUuidKey = 'documents/'.(string) Str::uuid().'.bin';
    Storage::disk('private')->put($randomUuidKey, 'malware binary');

    $docId = (string) Str::uuid();
    DB::table('documents')->insert([
        'id' => $docId,
        'owner_id' => $cust->id,
        'purpose' => 'passport_scan',
        'disk' => 'private',
        'storage_key' => $randomUuidKey,
        'original_name' => 'virus.exe',
        'detected_mime' => 'application/x-dosexec',
        'size_bytes' => 100,
        'sha256' => hash('sha256', 'malware binary'),
        'status' => 'rejected',
        'rejection_code' => 'malware_detected',
        'scanned_at' => $this->now,
        'created_at' => $this->now,
        'updated_at' => $this->now,
    ]);

    $actor = new ActorData(
        id: $cust->id,
        type: 'customer'
    );

    expect(fn () => app(AuthorizeDocumentDownload::class)->handle($docId, $actor))
        ->toThrow(DocumentAccessDenied::class);
});
