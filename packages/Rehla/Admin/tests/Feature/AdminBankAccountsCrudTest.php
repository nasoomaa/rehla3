<?php

declare(strict_types=1);

namespace Rehla\Admin\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Rehla\TopUps\Queries\ListAllBankAccounts;
use Tests\Support\StaffTestHelper;

it('manages bank accounts with full CRUD and active status toggle', function (): void {
    $staff = StaffTestHelper::createStaff(abilities: ['bank_accounts.manage']);
    $user = Auth::guard('admin')->getProvider()->retrieveById($staff['id']);

    // 1. Create bank account
    $this->actingAs($user, 'admin')->post('/admin/bank-accounts', [
        'bank_name_en' => 'Omdurman National Bank',
        'bank_name_ar' => 'بنك أمدرمان الوطني',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla Travel Co.',
        'sort_order' => 1,
    ])->assertRedirect('/admin/bank-accounts');

    $accounts = app(ListAllBankAccounts::class)->execute();
    $account = collect($accounts)->firstWhere('accountNumber', '1234567890');
    expect($account)->not->toBeNull();

    // 2. Update bank account
    $this->actingAs($user, 'admin')->put("/admin/bank-accounts/{$account->id}", [
        'bank_name_en' => 'ONB Updated',
        'bank_name_ar' => 'بنك أمدرمان المحدث',
        'account_number' => '1234567890',
        'beneficiary_name' => 'Rehla Travel Services',
        'sort_order' => 2,
    ])->assertRedirect('/admin/bank-accounts');

    $updatedAccounts = app(ListAllBankAccounts::class)->execute();
    $updated = collect($updatedAccounts)->firstWhere('id', $account->id);
    expect($updated)->not->toBeNull()
        ->and($updated->bankNameEn)->toBe('ONB Updated')
        ->and($updated->beneficiaryName)->toBe('Rehla Travel Services');

    // 3. Deactivate bank account
    $this->actingAs($user, 'admin')->post("/admin/bank-accounts/{$account->id}/deactivate")
        ->assertRedirect('/admin/bank-accounts');

    $deactivatedAccounts = app(ListAllBankAccounts::class)->execute();
    $deactivated = collect($deactivatedAccounts)->firstWhere('id', $account->id);
    expect($deactivated)->not->toBeNull()
        ->and($deactivated->active)->toBeFalse();
});
