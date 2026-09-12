<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\Wallet\Actions\CreditWallet;
use Rehla\Wallet\Actions\DebitWallet;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\CreditWalletData;
use Rehla\Wallet\Data\DebitWalletData;
use Rehla\Wallet\Exceptions\InsufficientBalanceException;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('prevents overdraft under competing debits with strict row locking', function (): void {
    $openAction = app(OpenWallet::class);
    $creditAction = app(CreditWallet::class);
    $debitAction = app(DebitWallet::class);
    $reader = app(WalletReader::class);

    $accountId = (string) Str::uuid();
    $wallet = $openAction->execute($accountId);

    // Seed wallet with 2,500 SDG
    $creditAction->execute(new CreditWalletData(
        walletId: $wallet->id,
        amountMinor: 2_500_00,
        referenceType: 'top_up',
        referenceId: 'seed-concurrency',
        idempotencyKey: 'seed-concurrency-key',
    ));

    // Competing debit 1
    $d1 = $debitAction->execute(new DebitWalletData(
        walletId: $wallet->id,
        amountMinor: 2_500_00,
        referenceType: 'order',
        referenceId: 'order-c1',
        idempotencyKey: 'debit-c1',
    ));

    expect($d1->balanceAfterMinor)->toBe(0);

    // Competing debit 2: must fail because balance is 0
    expect(fn () => $debitAction->execute(new DebitWalletData(
        walletId: $wallet->id,
        amountMinor: 2_500_00,
        referenceType: 'order',
        referenceId: 'order-c2',
        idempotencyKey: 'debit-c2',
    )))->toThrow(InsufficientBalanceException::class);

    $finalBalance = $reader->getBalance($wallet->id);
    expect($finalBalance->minor)->toBe(0);
});
