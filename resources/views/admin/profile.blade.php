@extends('admin.layout')
@section('title', 'My profile')

@section('content')
@if ($recovery)
<section class="card warn-card">
    <h2>Recovery codes</h2>
    <p>Store them somewhere safe. Each code works <strong>only once</strong> and they will not be shown again.</p>
    <pre class="codes">{{ implode("\n", $recovery) }}</pre>
</section>
@endif

<div class="cols">
    <section class="card">
        <h2>Details</h2>
        <form method="post" action="{{ route('admin.profile.info') }}" class="stack">
            @csrf
            <label>Name <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100"></label>
            <label>Email <input value="{{ $user->email }}" disabled></label>
            <label>Current password (for confirmation) <input type="password" name="current_password" required autocomplete="current-password"></label>
            <button class="btn primary">Save</button>
        </form>
    </section>

    <section class="card">
        <h2>Change password</h2>
        <form method="post" action="{{ route('admin.profile.password') }}" class="stack">
            @csrf
            <label>Current password <input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>New password <input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <label>Repeat <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            <small class="muted">At least 12 characters, with lowercase and uppercase letters and a digit. Your other sessions will be signed out.</small>
            <button class="btn primary">Change password</button>
        </form>
    </section>

    <section class="card">
        <h2>Two-factor authentication (2FA)</h2>
        @if ($user->totp_enabled)
            <p class="ok-text">✓ Active. Signing in requires a code from an authenticator app (Google Authenticator, Aegis, 2FAS…).</p>
            <form method="post" action="{{ route('admin.profile.2fa.recovery') }}" class="stack" data-confirm="The old codes will stop working. Continue?">
                @csrf
                <label>Password <input type="password" name="current_password" required autocomplete="current-password"></label>
                <button class="btn">New recovery codes</button>
            </form>
            <hr>
            <form method="post" action="{{ route('admin.profile.2fa.disable') }}" class="stack" data-confirm="Disabling 2FA reduces security. Are you sure?">
                @csrf
                <label>Password <input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Current 6-digit code <input name="code" required inputmode="numeric" maxlength="10" autocomplete="one-time-code"></label>
                <button class="btn danger">Disable 2FA</button>
            </form>
        @elseif ($secret)
            <p>1. In your app choose “Add account → enter a key” and enter:</p>
            <pre class="codes">{{ trim(chunk_split($secret, 4, ' ')) }}</pre>
            <p class="muted">Type: time-based (TOTP), 6 digits, 30 s. On a mobile device: <a href="{{ $otpauth }}">open in the app</a>.</p>
            <p>2. Confirm with the current code:</p>
            <form method="post" action="{{ route('admin.profile.2fa.confirm') }}" class="stack">
                @csrf
                <label>6-digit code <input name="code" required inputmode="numeric" maxlength="10" autocomplete="one-time-code"></label>
                <label>Password <input type="password" name="current_password" required autocomplete="current-password"></label>
                <button class="btn primary">Enable 2FA</button>
            </form>
        @else
            <p>Adds a second factor at sign-in — even a leaked password will not be enough to get in.</p>
            <form method="post" action="{{ route('admin.profile.2fa.start') }}">@csrf<button class="btn primary">Set up 2FA</button></form>
        @endif
    </section>

    <section class="card">
        <h2>Session</h2>
        <p class="muted">Last sign-in: {{ $user->last_login_at?->format('d.m.Y H:i') ?? '—' }} from {{ $user->last_login_ip ?? '—' }}</p>
        <p class="muted">Automatic sign-out after {{ config('mcms.idle_minutes') }} min of inactivity.</p>
    </section>
</div>
@endsection
