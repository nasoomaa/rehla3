<?php

declare(strict_types=1);

namespace Rehla\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Rehla\TopUps\Actions\CreateBankAccount;
use Rehla\TopUps\Actions\DeactivateBankAccount;
use Rehla\TopUps\Actions\UpdateBankAccount;
use Rehla\TopUps\Data\CreateBankAccountData;
use Rehla\TopUps\Queries\ListAllBankAccounts;

final class BankAccountController extends Controller
{
    public function index(ListAllBankAccounts $listAllBankAccounts): View
    {
        $bankAccounts = $listAllBankAccounts->execute();

        return view('rehla-admin::bank-accounts.index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function store(Request $request, CreateBankAccount $createBankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name_en' => ['required', 'string', 'max:255'],
            'bank_name_ar' => ['required', 'string', 'max:255'],
            'beneficiary_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo_document_id' => ['nullable', 'uuid'],
        ]);

        $actorId = (string) Auth::guard('admin')->id();

        $createBankAccount->execute(new CreateBankAccountData(
            bankNameEn: $validated['bank_name_en'],
            bankNameAr: $validated['bank_name_ar'],
            beneficiaryName: $validated['beneficiary_name'],
            accountNumber: $validated['account_number'],
            logoDocumentId: $validated['logo_document_id'] ?? null,
            sortOrder: (int) ($validated['sort_order'] ?? 0),
        ), actorId: $actorId);

        return redirect('/admin/bank-accounts')->with('success', 'Bank account created successfully.');
    }

    public function update(string $id, Request $request, UpdateBankAccount $updateBankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name_en' => ['required', 'string', 'max:255'],
            'bank_name_ar' => ['required', 'string', 'max:255'],
            'beneficiary_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo_document_id' => ['nullable', 'uuid'],
        ]);

        $actorId = (string) Auth::guard('admin')->id();

        $updateBankAccount->execute(
            bankAccountId: $id,
            data: new CreateBankAccountData(
                bankNameEn: $validated['bank_name_en'],
                bankNameAr: $validated['bank_name_ar'],
                beneficiaryName: $validated['beneficiary_name'],
                accountNumber: $validated['account_number'],
                logoDocumentId: $request->has('logo_document_id') ? $validated['logo_document_id'] : null,
                sortOrder: (int) ($validated['sort_order'] ?? 0),
            ),
            actorId: $actorId,
        );

        return redirect('/admin/bank-accounts')->with('success', 'Bank account updated successfully.');
    }

    public function deactivate(string $id, DeactivateBankAccount $deactivateBankAccount): RedirectResponse
    {
        $actorId = (string) Auth::guard('admin')->id();
        $deactivateBankAccount->execute($id, actorId: $actorId);

        return redirect('/admin/bank-accounts')->with('success', 'Bank account deactivated successfully.');
    }
}
