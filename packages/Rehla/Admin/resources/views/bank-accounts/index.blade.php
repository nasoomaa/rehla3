@extends('rehla-admin::layout')

@section('title', 'Company Bank Accounts')

@section('content')
<div class="card">
    <h2>Add Company Bank Account</h2>
    <form method="POST" action="/admin/bank-accounts">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="bank_name_en">Bank Name (English)</label>
                <input type="text" id="bank_name_en" name="bank_name_en" required placeholder="e.g. Bank of Khartoum">
            </div>
            <div class="form-group">
                <label for="bank_name_ar">Bank Name (Arabic)</label>
                <input type="text" id="bank_name_ar" name="bank_name_ar" required placeholder="e.g. بنك الخرطوم">
            </div>
            <div class="form-group">
                <label for="beneficiary_name">Beneficiary Name</label>
                <input type="text" id="beneficiary_name" name="beneficiary_name" required placeholder="e.g. Rehla Travel Agency">
            </div>
            <div class="form-group">
                <label for="account_number">Account Number</label>
                <input type="text" id="account_number" name="account_number" required placeholder="e.g. 1234567890">
            </div>
            <div class="form-group">
                <label for="sort_order">Display Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" value="1">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">Save Bank Account</button>
    </form>
</div>

<div class="card">
    <h2>Configured Bank Accounts</h2>
    <table>
        <thead>
            <tr>
                <th>Bank Name</th>
                <th>Beneficiary</th>
                <th>Account Number</th>
                <th>Status</th>
                <th>Sort Order</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bankAccounts as $acc)
                <tr>
                    <td>
                        <strong>{{ $acc->bankNameEn }}</strong><br>
                        <span style="color: var(--text-muted); font-size: 0.8rem;">{{ $acc->bankNameAr }}</span>
                    </td>
                    <td>{{ $acc->beneficiaryName }}</td>
                    <td><code style="font-size: 1rem;">{{ $acc->accountNumber }}</code></td>
                    <td>
                        <span class="badge {{ $acc->active ? 'badge-success' : 'badge-danger' }}">
                            {{ $acc->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>{{ $acc->sortOrder }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No company bank accounts configured.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
