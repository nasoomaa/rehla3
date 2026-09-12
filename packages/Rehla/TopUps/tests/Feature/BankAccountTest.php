<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\DeactivateBankAccount;
use Rehla\TopUps\Actions\UpdateBankAccount;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Queries\ListActiveBankAccounts;
use Tests\Support\AssertsSafeTestingDatabase;

beforeEach(function (): void {
    AssertsSafeTestingDatabase::check();
});

it('manages bank accounts and lists only active ones ordered by sort_order', function (): void {
    $createAction = app(CreateBankAccount::class);
    $updateAction = app(UpdateBankAccount::class);
    $deactivateAction = app(DeactivateBankAccount::class);
    $listQuery = app(ListActiveBankAccounts::class);

    $actorId = (string) Str::uuid();

    $b1 = $createAction->execute(new CreateBankAccountData(
        bankNameEn: 'Bank of Khartoum',
        bankNameAr: 'بنك الخرطوم',
        beneficiaryName: 'Rehla Travel Services',
        accountNumber: '1234567890',
        sortOrder: 1,
    ), $actorId);

    $b2 = $createAction->execute(new CreateBankAccountData(
        bankNameEn: 'Faisal Islamic Bank',
        bankNameAr: 'بنك فيصل الإسلامي',
        beneficiaryName: 'Rehla Travel Services',
        accountNumber: '9876543210',
        sortOrder: 2,
    ), $actorId);

    $active = $listQuery->execute();
    $ids = array_map(fn ($b) => $b->id, $active);

    expect($ids)->toContain($b1->id)
        ->and($ids)->toContain($b2->id);

    // Deactivate b1
    $deactivateAction->execute($b1->id, $actorId);

    $activeAfter = $listQuery->execute();
    $idsAfter = array_map(fn ($b) => $b->id, $activeAfter);

    expect($idsAfter)->not->toContain($b1->id)
        ->and($idsAfter)->toContain($b2->id);
});
