<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\TopUps\Exceptions\DuplicateTransactionReferenceException;
use Rehla\TopUps\Exceptions\InvalidTopUpAmountException;
use Rehla\Wallet\Actions\OpenWallet;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createCleanReceipt(string $ownerId): string
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

it('submits a top-up request with normalized reference and pending review status', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Bank of Khartoum',
        bankNameAr: 'بنك الخرطوم',
        beneficiaryName: 'Rehla Travel Services',
        accountNumber: '11223344',
    ));

    $ownerA = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerA);
    $receiptId = createCleanReceipt($ownerA);

    $submitAction = app(SubmitTopUp::class);

    $topUp = $submitAction->execute(new SubmitTopUpData(
        accountId: $ownerA,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 15_000_00,
        transactionReference: '  b-k 998-11  ',
        receiptDocumentId: $receiptId,
    ));

    expect($topUp->status)->toBe(TopUpStatus::UnderReview)
        ->and($topUp->amountMinor)->toBe(15_000_00)
        ->and($topUp->transactionReference)->toBe('  b-k 998-11  ')
        ->and($topUp->normalizedReference)->toBe('BK99811')
        ->and($topUp->receiptDocumentId)->toBe($receiptId);
});

it('rejects a reused reference for the same bank after normalization without leaking owner info', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Faisal Islamic Bank',
        bankNameAr: 'بنك فيصل الإسلامي',
        beneficiaryName: 'Rehla Travel Services',
        accountNumber: '55667788',
    ));

    $ownerA = (string) Str::uuid();
    $ownerB = (string) Str::uuid();

    $walletA = app(OpenWallet::class)->execute($ownerA);
    $walletB = app(OpenWallet::class)->execute($ownerB);

    $receiptA = createCleanReceipt($ownerA);
    $receiptB = createCleanReceipt($ownerB);

    $submitAction = app(SubmitTopUp::class);

    $submitAction->execute(new SubmitTopUpData(
        accountId: $ownerA,
        walletId: $walletA->id,
        bankAccountId: $bank->id,
        amountMinor: 10_000_00,
        transactionReference: 'REF-123-ABC',
        receiptDocumentId: $receiptA,
    ));

    // Attempt reuse with different formatting: ' ref 123 abc '
    expect(function () use ($submitAction, $ownerB, $walletB, $bank, $receiptB): void {
        $submitAction->execute(new SubmitTopUpData(
            accountId: $ownerB,
            walletId: $walletB->id,
            bankAccountId: $bank->id,
            amountMinor: 10_000_00,
            transactionReference: '  ref 123 abc  ',
            receiptDocumentId: $receiptB,
        ));
    })->toThrow(DuplicateTransactionReferenceException::class);
});

it('rejects top-up submissions below the minimum allowable amount', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Omdurman National Bank',
        bankNameAr: 'بنك أم درمان الوطني',
        beneficiaryName: 'Rehla Travel Services',
        accountNumber: '99887766',
    ));

    $owner = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($owner);
    $receipt = createCleanReceipt($owner);

    $submitAction = app(SubmitTopUp::class);

    // Minimum is 5,000 SDG = 500,000 minor. Try 4,000 SDG = 400,000 minor.
    expect(function () use ($submitAction, $owner, $wallet, $bank, $receipt): void {
        $submitAction->execute(new SubmitTopUpData(
            accountId: $owner,
            walletId: $wallet->id,
            bankAccountId: $bank->id,
            amountMinor: 4_000_00,
            transactionReference: 'VALID-REF-1',
            receiptDocumentId: $receipt,
        ));
    })->toThrow(InvalidTopUpAmountException::class);
});
