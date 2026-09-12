@extends('rehla-admin::layout')

@section('title', 'Staff Sign In')

@section('content')
<div style="max-width: 400px; margin: 4rem auto;">
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 1.5rem;">Staff Access Portal</h2>

        <form method="POST" action="/admin/login">
            @csrf
            <div class="form-group">
                <label for="email">Staff Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="staff@rehla.sd">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" id="remember" name="remember" style="width: auto;">
                <label for="remember" style="margin-bottom: 0;">Remember this device</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </form>
    </div>
</div>
@endsection
