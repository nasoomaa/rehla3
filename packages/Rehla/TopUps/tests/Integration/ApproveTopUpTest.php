<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Enums\ActorType;
use Rehla\TopUps\Actions\ApproveTopUp;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\RejectTopUp;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\ApproveTopUpData;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Data\RejectTopUpData;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\InvalidTopUpTransitionException;
use Rehla\TopUps\Exceptions\TopUpAccessDeniedException;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createCleanReceiptForApproval(string $ownerId): string
{
    $session = app(BeginUpload::class)->handle(new BeginUploadData(
        ownerId: $ownerId,
        purpose: DocumentPurpose::BankReceipt,
    ));

    $validPdfContent = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('receipt.pdf', $validPdfContent);
    $doc = app(StoreUpload::class)->handle($session->id, $file);
    $scanned = app(ScanDocument::class)->handle($doc->id);

    return $scanned->id;
}

it('credits wallet, updates request to approved, audits and enqueues outbox atomically', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '112233',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForApproval($ownerId);

    $submitAction = app(SubmitTopUp::class);
    $topUp = $submitAction->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 25_000_00,
        transactionReference: 'TXN-APPROVE-1',
        receiptDocumentId: $receiptId,
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    $approveAction = app(ApproveTopUp::class);
    $approvedResult = $approveAction->execute(new ApproveTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
    ));

    expect($approvedResult->status)->toBe(TopUpStatus::Approved)
        ->and($approvedResult->reviewedBy)->toBe($staffActor->id)
        ->and($approvedResult->decidedAt)->not->toBeNull()
        ->and($approvedResult->creditLedgerEntryId)->not->toBeNull();

    // Verify wallet balance
    $walletReader = app(WalletReader::class);
    expect($walletReader->getBalance($wallet->id)->minor)->toBe(25_000_00);

    // Verify audit log
    $auditExists = DB::table('audit_entries')
        ->where('subject_type', 'topup_request')
        ->where('subject_id', $topUp->id)
        ->where('action', 'topup.approved')
        ->exists();
    expect($auditExists)->toBeTrue();

    // Verify outbox message
    $outboxExists = DB::table('outbox_messages')
        ->where('aggregate_type', 'topup_request')
        ->where('aggregate_id', $topUp->id)
        ->where('event_name', 'top_up.approved')
        ->exists();
    expect($outboxExists)->toBeTrue();

    // Replay idempotency: repeating approval returns same result without crediting again
    $replayed = $approveAction->execute(new ApproveTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
    ));

    expect($replayed->status)->toBe(TopUpStatus::Approved)
        ->and($walletReader->getBalance($wallet->id)->minor)->toBe(25_000_00);
});

it('denies approval if actor lacks TopUpsReview ability', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '112234',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForApproval($ownerId);

    $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 10_000_00,
        transactionReference: 'TXN-NO-ABILITY',
        receiptDocumentId: $receiptId,
    ));

    $unauthorizedActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::CustomersView->value],
    );

    expect(fn () => app(ApproveTopUp::class)->execute(new ApproveTopUpData(
        topUpId: $topUp->id,
        actor: $unauthorizedActor,
    )))->toThrow(TopUpAccessDeniedException::class);
});

it('denies approval if actor MFA confirmation is expired', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '112235',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForApproval($ownerId);

    $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 10_000_00,
        transactionReference: 'TXN-EXPIRED-MFA',
        receiptDocumentId: $receiptId,
    ));

    $expiredMfaActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: CarbonImmutable::now()->subHours(13)->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    expect(fn () => app(ApproveTopUp::class)->execute(new ApproveTopUpData(
        topUpId: $topUp->id,
        actor: $expiredMfaActor,
    )))->toThrow(TopUpAccessDeniedException::class);
});

it('cannot approve an already rejected top-up', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '112236',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForApproval($ownerId);

    $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 10_000_00,
        transactionReference: 'TXN-ALREADY-REJECTED',
        receiptDocumentId: $receiptId,
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    app(RejectTopUp::class)->execute(new RejectTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
        rejectionReason: 'Invalid transaction receipt',
    ));

    expect(fn () => app(ApproveTopUp::class)->execute(new ApproveTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
    )))->toThrow(InvalidTopUpTransitionException::class);
});
