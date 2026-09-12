<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rehla\Wallet\Actions\CreditWallet;
use Rehla\Wallet\Actions\DebitWallet;
use Rehla\Wallet\Actions\OpenWallet;
use Rehla\Wallet\Actions\ReverseWalletEntry;
use Rehla\Wallet\Contracts\WalletReader;
use Rehla\Wallet\Data\CreditWalletData;
use Rehla\Wallet\Data\DebitWalletData;
use Rehla\Wallet\Enums\LedgerEntryType;
use Rehla\Wallet\Exceptions\InsufficientBalanceException;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('derives every balance change from append-only ledger entries and protects ledger against SQL mutations', function (): void {
    $openAction = app(OpenWallet::class);
    $creditAction = app(CreditWallet::class);
    $debitAction = app(DebitWallet::class);
    $reader = app(WalletReader::class);

    $accountId = (string) Str::uuid();
    $wallet = $openAction->execute($accountId);

    expect($wallet->balanceMinor)->toBe(0)
        ->and($wallet->currency)->toBe('SDG');

    // Credit 5,000 SDG
    $creditResult = $creditAction->execute(new CreditWalletData(
        walletId: $wallet->id,
        amountMinor: 5_000_00,
        referenceType: 'top_up',
        referenceId: 'topup-1',
        idempotencyKey: 'idem-credit-1',
    ));

    expect($creditResult->balanceAfterMinor)->toBe(5_000_00)
        ->and($creditResult->isReplay)->toBeFalse();

    // Debit 2,500 SDG
    $debitResult = $debitAction->execute(new DebitWalletData(
        walletId: $wallet->id,
        amountMinor: 2_500_00,
        referenceType: 'order',
        referenceId: 'order-1',
        idempotencyKey: 'idem-debit-1',
    ));

    expect($debitResult->balanceAfterMinor)->toBe(2_500_00)
        ->and($debitResult->isReplay)->toBeFalse();

    // Check balance via Reader
    $balance = $reader->getBalance($wallet->id);
    expect($balance->minor)->toBe(2_500_00);

    // Check entries count
    $entries = $reader->listEntries($wallet->id);
    expect($entries)->toHaveCount(2);

    $types = array_map(fn ($e) => $e->type, $entries);
    expect($types)->toContain(LedgerEntryType::Credit)
        ->and($types)->toContain(LedgerEntryType::Debit);

    // Check ledger immutability via PostgreSQL trigger
    $entryId = $entries[0]->id;
    expect(function () use ($entryId): void {
        DB::table('ledger_entries')
            ->where('id', $entryId)
            ->update(['amount_minor' => 999]);
    })->toThrow(QueryException::class);

    expect(function () use ($entryId): void {
        DB::table('ledger_entries')
            ->where('id', $entryId)
            ->delete();
    })->toThrow(QueryException::class);
});

it('enforces idempotency on repeated credits and debits', function (): void {
    $openAction = app(OpenWallet::class);
    $creditAction = app(CreditWallet::class);
    $debitAction = app(DebitWallet::class);
    $reader = app(WalletReader::class);

    $accountId = (string) Str::uuid();
    $wallet = $openAction->execute($accountId);

    $creditData = new CreditWalletData(
        walletId: $wallet->id,
        amountMinor: 10_000_00,
        referenceType: 'top_up',
        referenceId: 'topup-replay',
        idempotencyKey: 'same-credit-key',
    );

    $r1 = $creditAction->execute($creditData);
    expect($r1->isReplay)->toBeFalse()
        ->and($r1->balanceAfterMinor)->toBe(10_000_00);

    $r2 = $creditAction->execute($creditData);
    expect($r2->isReplay)->toBeTrue()
        ->and($r2->entryId)->toBe($r1->entryId)
        ->and($r2->balanceAfterMinor)->toBe(10_000_00);

    expect($reader->listEntries($wallet->id))->toHaveCount(1);

    // Debit replay
    $debitData = new DebitWalletData(
        walletId: $wallet->id,
        amountMinor: 3_000_00,
        referenceType: 'order',
        referenceId: 'order-replay',
        idempotencyKey: 'same-debit-key',
    );

    $d1 = $debitAction->execute($debitData);
    expect($d1->isReplay)->toBeFalse()
        ->and($d1->balanceAfterMinor)->toBe(7_000_00);

    $d2 = $debitAction->execute($debitData);
    expect($d2->isReplay)->toBeTrue()
        ->and($d2->entryId)->toBe($d1->entryId)
        ->and($d2->balanceAfterMinor)->toBe(7_000_00);

    expect($reader->listEntries($wallet->id))->toHaveCount(2);
});

it('rejects debits that exceed balance and supports entry reversals', function (): void {
    $openAction = app(OpenWallet::class);
    $creditAction = app(CreditWallet::class);
    $debitAction = app(DebitWallet::class);
    $reverseAction = app(ReverseWalletEntry::class);
    $reader = app(WalletReader::class);

    $accountId = (string) Str::uuid();
    $wallet = $openAction->execute($accountId);

    $credit = $creditAction->execute(new CreditWalletData(
        walletId: $wallet->id,
        amountMinor: 2_000_00,
        referenceType: 'top_up',
        referenceId: 'topup-exceed',
        idempotencyKey: 'credit-exceed',
    ));

    // Attempt to debit 3,000 SDG when balance is 2,000 SDG
    expect(fn () => $debitAction->execute(new DebitWalletData(
        walletId: $wallet->id,
        amountMinor: 3_000_00,
        referenceType: 'order',
        referenceId: 'order-fail',
        idempotencyKey: 'debit-fail',
    )))->toThrow(InsufficientBalanceException::class);

    expect($reader->getBalance($wallet->id)->minor)->toBe(2_000_00);

    // Reversal of initial credit
    $reversal = $reverseAction->execute(
        walletId: $wallet->id,
        entryIdToReverse: $credit->entryId,
        reason: 'Erroneous top-up correction',
        idempotencyKey: 'reversal-key-1'
    );

    expect($reversal->type)->toBe(LedgerEntryType::Reversal)
        ->and($reversal->reversesEntryId)->toBe($credit->entryId)
        ->and($reader->getBalance($wallet->id)->minor)->toBe(0);
});
