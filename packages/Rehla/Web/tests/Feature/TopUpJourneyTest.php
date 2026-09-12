<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Rehla\Identity\Actions\RegisterCustomer;
use Rehla\Identity\Data\RegisterCustomerData;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Enums\TopUpStatus;
use Rehla\Web\Livewire\Account\TopUpCreate;

function createTestCustomerForTopUp(): GenericUser
{
    $email = 'customer_topup_'.Str::random(6).'@example.com';
    $userData = app(RegisterCustomer::class)->handle(new RegisterCustomerData(
        name: 'TopUp Customer',
        email: $email,
        password: 'Password123!',
    ));

    return new GenericUser([
        'id' => $userData->id,
        'name' => $userData->name,
        'email' => $userData->email,
        'remember_token' => null,
    ]);
}

function createActiveBank(): object
{
    $bankData = app(CreateBankAccount::class)->execute(new CreateBankAccountData(
        bankNameEn: 'Bank of Khartoum',
        bankNameAr: 'بنك الخرطوم',
        beneficiaryName: 'Rehla Services Co.',
        accountNumber: 'BOK-'.Str::random(8),
    ));

    return (object) ['id' => $bankData->id, 'bankName' => $bankData->bankNameEn];
}

function latestTopUp(): ?object
{
    $row = DB::table('topup_requests')->orderBy('created_at', 'desc')->first();
    if ($row === null) {
        return null;
    }

    $row->status = TopUpStatus::from($row->status);

    return $row;
}

it('submits a top-up with a private clean receipt', function (): void {
    Storage::fake('private');
    $customer = createTestCustomerForTopUp();
    $bank = createActiveBank();

    Livewire::actingAs($customer)
        ->test(TopUpCreate::class)
        ->set('amount', '5000.00')
        ->set('bankAccountId', $bank->id)
        ->set('reference', 'TRX-1001')
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect('/account/top-ups');

    $latest = latestTopUp();
    expect($latest)->not->toBeNull()
        ->and($latest->status)->toBe(TopUpStatus::UnderReview)
        ->and((int) $latest->amount_minor)->toBe(500_000);
});

it('rejects top-up amounts below the minimum threshold of 5000 SDG', function (): void {
    $customer = createTestCustomerForTopUp();
    $bank = createActiveBank();

    Livewire::actingAs($customer)
        ->test(TopUpCreate::class)
        ->set('amount', '4000.00')
        ->set('bankAccountId', $bank->id)
        ->set('reference', 'TRX-1002')
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg'))
        ->call('submit')
        ->assertHasErrors(['amount']);
});
