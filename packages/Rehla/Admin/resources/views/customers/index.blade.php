@extends('rehla-admin::layout')

@section('title', 'Registered Customers')

@section('content')
<div class="card">
    <h2>Customer Accounts</h2>
    <table>
        <thead>
            <tr>
                <th>Customer ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td><code>{{ $customer['id'] }}</code></td>
                    <td><strong>{{ $customer['name'] }}</strong></td>
                    <td>{{ $customer['email'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="color: var(--text-muted); text-align: center;">No customers found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
