<?php

declare(strict_types=1);

namespace Rehla\Web\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Rehla\Documents\Actions\BeginUpload;
use Rehla\Documents\Actions\ScanDocument;
use Rehla\Documents\Actions\StoreUpload;
use Rehla\Documents\Data\BeginUploadData;
use Rehla\Documents\Enums\DocumentPurpose;
use Rehla\TopUps\Actions\SubmitTopUp;
use Rehla\TopUps\Data\SubmitTopUpData;
use Rehla\TopUps\Queries\ListActiveBankAccounts;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;

final class TopUpCreate extends Component
{
    use WithFileUploads;

    public string $amount = '';

    public string $bankAccountId = '';

    public string $reference = '';

    public mixed $receipt = null;

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:5000'],
            'bankAccountId' => ['required', 'string'],
            'reference' => ['required', 'string', 'max:255'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }

    public function mount(): void
    {
        $banks = app(ListActiveBankAccounts::class)->execute();
        if (! empty($banks) && empty($this->bankAccountId)) {
            $this->bankAccountId = $banks[0]->id;
        }
    }

    public function submit(): mixed
    {
        $this->validate();

        $accountId = (string) Auth::id();

        $walletReader = app(WalletReader::class);
        $wallet = $walletReader->getWalletByAccount($accountId);
        $walletId = $wallet?->id ?? app(OpenWallet::class)->execute($accountId)->id;

        $amountMinor = (int) round(((float) $this->amount) * 100);

        // Upload and scan receipt
        $uploadSession = app(BeginUpload::class)->handle(new BeginUploadData(
            ownerId: $accountId,
            purpose: DocumentPurpose::BankReceipt,
        ));

        $storedDoc = app(StoreUpload::class)->handle($uploadSession->id, $this->receipt);
        $scannedDoc = app(ScanDocument::class)->handle($storedDoc->id);

        app(SubmitTopUp::class)->execute(new SubmitTopUpData(
            accountId: $accountId,
            walletId: $walletId,
            bankAccountId: $this->bankAccountId,
            amountMinor: $amountMinor,
            transactionReference: $this->reference,
            receiptDocumentId: $scannedDoc->id,
        ));

        session()->flash('success', __('Top-up request submitted successfully and is under review.'));

        return $this->redirect('/account/top-ups', navigate: true);
    }

    public function render(): View
    {
        $bankAccounts = app(ListActiveBankAccounts::class)->execute();

        return view('rehla-web::livewire.account.top-up-create', [
            'bankAccounts' => $bankAccounts,
        ])->layout('rehla-web::layouts.app', [
            'title' => __('Submit Top-Up Request'),
        ]);
    }
}
