@extends('rehla-admin::layout')

@section('title', 'Top-up Requests Review Queue')

@section('content')
<div class="card">
    <h2>Bank Top-Up Verification Queue</h2>
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Amount (SDG)</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($topUps as $topUp)
                <tr>
                    <td>
                        <strong style="font-family: monospace;">{{ $topUp->transactionReference }}</strong><br>
                        <span style="color: var(--text-muted); font-size: 0.75rem;">ID: {{ $topUp->id }}</span>
                    </td>
                    <td><strong style="color: #34d399;">{{ number_format($topUp->amountMinor / 100, 2) }} SDG</strong></td>
                    <td>
                        <span class="badge {{ $topUp->status->value === 'approved' ? 'badge-success' : ($topUp->status->value === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                            {{ $topUp->status->value }}
                        </span>
                        @if($topUp->rejectionReason)
                            <div style="color: #f87171; font-size: 0.75rem; margin-top: 0.25rem;">Reason: {{ $topUp->rejectionReason }}</div>
                        @endif
                    </td>
                    <td>{{ $topUp->submittedAt->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($topUp->status->value === 'under_review')
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="/admin/top-up-requests/{{ $topUp->id }}/approve" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="notes" value="Approved by staff reviewer">
                                    <button type="submit" class="btn btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Approve</button>
                                </form>
                                <form method="POST" action="/admin/top-up-requests/{{ $topUp->id }}/reject" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="reason" value="Invalid transaction reference or unverified deposit">
                                    <button type="submit" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Reject</button>
                                </form>
                            </div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.8rem;">Resolved</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="color: var(--text-muted); text-align: center;">No top-up requests in queue.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
