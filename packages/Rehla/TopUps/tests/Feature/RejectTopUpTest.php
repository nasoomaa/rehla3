<?php

declare(strict_types=1);

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
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\RejectTopUp;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Data\RejectTopUpData;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\TopUpAccessDeniedException;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createCleanReceiptForReject(string $ownerId): string
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

it('rejects top-up with required rejection reason, audits and enqueues outbox, leaving wallet balance unchanged', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Al Baraka Bank',
        bankNameAr: 'بنك البركة',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '445566',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForReject($ownerId);

    $submitAction = app(SubmitTopUp::class);
    $topUp = $submitAction->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 20_000_00,
        transactionReference: 'TXN-REJECT-1',
        receiptDocumentId: $receiptId,
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    $rejectAction = app(RejectTopUp::class);
    $rejected = $rejectAction->execute(new RejectTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
        rejectionReason: 'Bank receipt is blurred and unreadable.',
    ));

    expect($rejected->status)->toBe(TopUpStatus::Rejected)
        ->and($rejected->rejectionReason)->toBe('Bank receipt is blurred and unreadable.')
        ->and($rejected->reviewedBy)->toBe($staffActor->id)
        ->and($rejected->decidedAt)->not->toBeNull()
        ->and($rejected->creditLedgerEntryId)->toBeNull();

    // Wallet balance should remain 0
    $walletReader = app(WalletReader::class);
    expect($walletReader->getBalance($wallet->id)->minor)->toBe(0);

    // Audit log
    $auditEntries = DB::table('audit_entries')
        ->where('subject_type', 'topup_request')
        ->where('subject_id', $topUp->id)
        ->where('action', 'topup.rejected')
        ->get();
    expect($auditEntries)->toHaveCount(1);

    // Outbox
    $outboxMessages = DB::table('outbox_messages')
        ->where('aggregate_type', 'topup_request')
        ->where('aggregate_id', $topUp->id)
        ->where('event_name', 'top_up.rejected')
        ->get();
    expect($outboxMessages)->toHaveCount(1);

    // Replay idempotency: repeating rejection returns same result without duplicating entries
    $replayed = $rejectAction->execute(new RejectTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
        rejectionReason: 'Bank receipt is blurred and unreadable.',
    ));

    expect($replayed->status)->toBe(TopUpStatus::Rejected);

    expect(DB::table('audit_entries')->where('subject_type', 'topup_request')->where('subject_id', $topUp->id)->where('action', 'topup.rejected')->count())->toBe(1)
        ->and(DB::table('outbox_messages')->where('aggregate_type', 'topup_request')->where('aggregate_id', $topUp->id)->where('event_name', 'top_up.rejected')->count())->toBe(1);
});

it('fails rejection if rejection reason is empty', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Al Baraka Bank',
        bankNameAr: 'بنك البركة',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '445566-2',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForReject($ownerId);

    $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 20_000_00,
        transactionReference: 'TXN-REJECT-EMPTY',
        receiptDocumentId: $receiptId,
    ));

    $staffActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    expect(fn () => app(RejectTopUp::class)->execute(new RejectTopUpData(
        topUpId: $topUp->id,
        actor: $staffActor,
        rejectionReason: '   ',
    )))->toThrow(InvalidArgumentException::class);
});

it('denies rejection if actor lacks TopUpsReview ability', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Al Baraka Bank',
        bankNameAr: 'بنك البركة',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '445566-3',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForReject($ownerId);

    $topUp = app(SubmitTopUp::class)->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 20_000_00,
        transactionReference: 'TXN-REJECT-UNAUTH',
        receiptDocumentId: $receiptId,
    ));

    $unauthorizedActor = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TravelersView->value],
    );

    expect(fn () => app(RejectTopUp::class)->execute(new RejectTopUpData(
        topUpId: $topUp->id,
        actor: $unauthorizedActor,
        rejectionReason: 'Cannot process request',
    )))->toThrow(TopUpAccessDeniedException::class);
});
