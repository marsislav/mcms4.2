<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Two-factor verification — {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ config('mcms.version') }}">
</head>
<body class="auth">
<form class="auth-card" method="post" action="{{ route('admin.challenge.post') }}">
    @csrf
    <div class="brand big">m<b>CMS</b></div>
    <h2>Two-factor verification</h2>
    <p class="muted">Enter the 6-digit code from your authenticator app, or a recovery code.</p>

    @error('code')<div class="flash err">{{ $message }}</div>@enderror

    <label>Code
        <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="text" maxlength="32" placeholder="123456">
    </label>
    <button class="btn primary block">Verify</button>
    <p class="center"><a href="{{ route('admin.login') }}">← Back to sign in</a></p>
</form>
</body>
</html>
