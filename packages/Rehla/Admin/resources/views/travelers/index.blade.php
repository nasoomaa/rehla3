@extends('rehla-admin::layout')

@section('title', 'Travelers Directory')

@section('content')
<div class="card">
    <h2>Travelers Directory</h2>
    <table>
        <thead>
            <tr>
                <th>Traveler ID</th>
                <th>Full Name</th>
                <th>Date of Birth</th>
                <th>Gender</th>
                <th>Passport Number</th>
                <th>Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($travelers as $traveler)
                <tr>
                    <td><code>{{ $traveler['id'] }}</code></td>
                    <td><strong>{{ $traveler['fullName'] }}</strong></td>
                    <td>{{ $traveler['dateOfBirth'] }}</td>
                    <td><span class="badge badge-info">{{ $traveler['gender'] }}</span></td>
                    <td><strong style="font-family: monospace; color: #60a5fa;">{{ $traveler['maskedPassport'] }}</strong></td>
                    <td>{{ $traveler['passportExpiresAt'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="color: var(--text-muted); text-align: center;">No travelers recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
