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
use Rehla\Identity\Data\ActorData;
use Rehla\Identity\Enums\AbilityName;
use Rehla\Identity\Enums\ActorType;
use Rehla\TopUps\Actions\ApproveTopUp;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\ApproveTopUpData;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
    Storage::fake('private');
});

function createCleanReceiptForConcurrent(string $ownerId): string
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

it('prevents concurrent double approval and credits wallet exactly once', function (): void {
    $bank = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Sudanese French Bank',
        bankNameAr: 'البنك السوداني الفرنسي',
        beneficiaryName: 'Rehla Travel',
        accountNumber: '778899',
    ));

    $ownerId = (string) Str::uuid();
    $wallet = app(OpenWallet::class)->execute($ownerId);
    $receiptId = createCleanReceiptForConcurrent($ownerId);

    $submitAction = app(SubmitTopUp::class);
    $topUp = $submitAction->execute(new SubmitTopUpData(
        accountId: $ownerId,
        walletId: $wallet->id,
        bankAccountId: $bank->id,
        amountMinor: 50_000_00,
        transactionReference: 'TXN-CONCURRENT-1',
        receiptDocumentId: $receiptId,
    ));

    $staff1 = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    $staff2 = new ActorData(
        id: (string) Str::uuid(),
        type: ActorType::Staff,
        mfaConfirmedAt: now()->toIso8601String(),
        abilities: [AbilityName::TopUpsReview->value],
    );

    $approveAction = app(ApproveTopUp::class);

    // Call 1
    $res1 = $approveAction->execute(new ApproveTopUpData($topUp->id, $staff1));
    // Call 2 (competing)
    $res2 = $approveAction->execute(new ApproveTopUpData($topUp->id, $staff2));

    expect($res1->status)->toBe(TopUpStatus::Approved)
        ->and($res2->status)->toBe(TopUpStatus::Approved);

    // Wallet must be credited exactly once with 50,000 SDG
    $walletReader = app(WalletReader::class);
    expect($walletReader->getBalance($wallet->id)->minor)->toBe(50_000_00)
        ->and($walletReader->listEntries($wallet->id))->toHaveCount(1);
});
