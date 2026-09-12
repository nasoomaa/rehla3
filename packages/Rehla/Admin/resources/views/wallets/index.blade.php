@extends('rehla-admin::layout')

@section('title', 'Customer Wallets (Read-Only)')

@section('content')
<div class="card">
    <h2>Financial Wallets</h2>
    <table>
        <thead>
            <tr>
                <th>Wallet ID</th>
                <th>Account ID</th>
                <th>Currency</th>
                <th>Balance (SDG)</th>
                <th>Lock Version</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wallets as $wallet)
                <tr>
                    <td><code>{{ $wallet->id }}</code></td>
                    <td><code>{{ $wallet->accountId }}</code></td>
                    <td><span class="badge badge-info">{{ $wallet->currency }}</span></td>
                    <td><strong>{{ number_format($wallet->balanceMinor / 100, 2) }} SDG</strong></td>
                    <td>v{{ $wallet->lockVersion }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No wallets found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
