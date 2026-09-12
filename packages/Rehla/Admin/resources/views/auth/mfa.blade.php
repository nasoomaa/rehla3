@extends('rehla-admin::layout')

@section('title', 'TOTP MFA Verification')

@section('content')
<div style="max-width: 420px; margin: 4rem auto;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 0.5rem;">Multi-Factor Authentication</h2>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
            Enter your 6-digit TOTP code from your authenticator app to unlock sensitive administrative capabilities for 12 hours.
        </p>

        <form method="POST" action="/admin/mfa">
            @csrf
            <div class="form-group">
                <label for="totp_code">One-Time Security Code (TOTP)</label>
                <input type="text" id="totp_code" name="totp_code" required autofocus placeholder="123456" maxlength="8" style="text-align: center; font-size: 1.5rem; letter-spacing: 0.25rem;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Verify and Confirm Session</button>
        </form>
    </div>
</div>
@endsection
