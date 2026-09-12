<?php

declare(strict_types=1);

namespace Rehla\Web\Livewire\Account;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Rehla\Catalog\Contracts\ServiceCatalog;
use Rehla\Catalog\Queries\GetServiceDetails;
use Rehla\Forms\Queries\GetPublishedForm;
use Rehla\Purchasing\Actions\SubmitOrder;
use Rehla\Purchasing\Data\SubmitOrderData;
use Rehla\Travelers\Queries\ListOwnedTravelers;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Exceptions\InsufficientWalletBalanceException;

final class OrderCheckout extends Component
{
    public string $slug = '';

    public string $serviceId = '';

    public string $serviceNameEn = '';

    public string $serviceNameAr = '';

    public int $priceMinor = 0;

    public string $step = 'traveler'; // 'traveler', 'form', 'review'

    public ?string $travelerId = null;

    /**
     * @var array<string, mixed>
     */
    public array $formData = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $formFields = [];

    public ?int $acceptedPriceMinor = null;

    public ?int $acceptedPriceVersion = null;

    public ?string $formVersionId = null;

    public ?string $idempotencyKey = null;

    public function mount(string $slug): void
    {
        $service = app(GetServiceDetails::class)->executeBySlug($slug);
        abort_unless($service !== null, 404);

        $this->slug = $slug;
        $this->serviceId = $service->id;
        $this->serviceNameEn = $service->nameEn;
        $this->serviceNameAr = $service->nameAr;
        $this->priceMinor = $service->currentPriceMinor;

        $publishedForm = app(GetPublishedForm::class)->handle($service->id);
        if ($publishedForm !== null) {
            $this->formVersionId = $publishedForm->id;
            $this->formFields = array_map(function ($f): array {
                return [
                    'key' => $f->key,
                    'type' => $f->type instanceof \BackedEnum ? $f->type->value : (string) $f->type,
                    'labelEn' => $f->labelEn,
                    'labelAr' => $f->labelAr,
                    'required' => $f->required,
                    'options' => $f->options ?? [],
                    'validationRules' => $f->validationRules ?? [],
                ];
            }, $publishedForm->fields);
        }
    }

    public function nextStep(): void
    {
        $this->resetErrorBag();

        if ($this->step === 'traveler') {
            if (empty($this->travelerId)) {
                $this->addError('travelerId', __('Please select a traveler.'));

                return;
            }

            $accountId = (string) Auth::id();
            $ownedTravelers = app(ListOwnedTravelers::class)->handle($accountId);
            $isOwned = collect($ownedTravelers)->contains(fn ($t): bool => $t->id === $this->travelerId);

            if (! $isOwned) {
                $this->addError('travelerId', __('Selected traveler is invalid or does not belong to your account.'));

                return;
            }

            $this->step = 'form';

            return;
        }

        if ($this->step === 'form') {
            foreach ($this->formFields as $field) {
                $key = (string) $field['key'];
                $required = (bool) ($field['required'] ?? false);
                $val = $this->formData[$key] ?? null;

                if ($required && ($val === null || $val === '')) {
                    $label = app()->getLocale() === 'ar' ? ($field['labelAr'] ?? $key) : ($field['labelEn'] ?? $key);
                    $this->addError("formData.{$key}", __(':field is required.', ['field' => $label]));
                }
            }

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }

            // Price Freeze
            $quote = app(ServiceCatalog::class)->currentQuote($this->serviceId);
            $this->acceptedPriceMinor = $quote->priceMinor;
            $this->acceptedPriceVersion = $quote->quoteVersion;
            $this->idempotencyKey = (string) Str::uuid();
            $this->step = 'review';
        }
    }

    public function previousStep(): void
    {
        if ($this->step === 'review') {
            $this->step = 'form';
        } elseif ($this->step === 'form') {
            $this->step = 'traveler';
        }
    }

    public function submitOrder(): mixed
    {
        $accountId = (string) Auth::id();

        $wallet = app(WalletReader::class)->getWalletByAccount($accountId);
        $requiredMinor = $this->acceptedPriceMinor ?? $this->priceMinor;

        if ($wallet === null || $wallet->balanceMinor < $requiredMinor) {
            $this->addError('wallet', __('Insufficient wallet balance. Please top up your wallet first.'));

            return null;
        }

        try {
            $result = app(SubmitOrder::class)->execute(new SubmitOrderData(
                accountId: $accountId,
                serviceId: $this->serviceId,
                travelerId: (string) $this->travelerId,
                acceptedPriceMinor: $this->acceptedPriceMinor ?? $this->priceMinor,
                acceptedPriceVersion: $this->acceptedPriceVersion ?? 1,
                formVersionId: (string) $this->formVersionId,
                idempotencyKey: $this->idempotencyKey ?? (string) Str::uuid(),
                answers: $this->formData,
                documentIds: [],
            ));

            return $this->redirect('/account/orders/'.$result->orderId, navigate: true);
        } catch (InsufficientWalletBalanceException $e) {
            $this->addError('wallet', __('Insufficient wallet balance. Please top up your wallet first.'));

            return null;
        }
    }

    public function render(): View
    {
        $accountId = (string) Auth::id();
        $travelers = app(ListOwnedTravelers::class)->handle($accountId);
        $wallet = app(WalletReader::class)->getWalletByAccount($accountId);

        $selectedTraveler = collect($travelers)->first(fn ($t): bool => $t->id === $this->travelerId);

        return view('rehla-web::livewire.account.order-checkout', [
            'travelers' => $travelers,
            'selectedTraveler' => $selectedTraveler,
            'walletBalanceMinor' => $wallet?->balanceMinor ?? 0,
        ])->layout('rehla-web::layouts.app', [
            'title' => __('Checkout: :service', ['service' => app()->getLocale() === 'ar' ? $this->serviceNameAr : $this->serviceNameEn]),
        ]);
    }
}
