<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in — {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ config('mcms.version') }}">
</head>
<body class="auth">
<form class="auth-card" method="post" action="{{ route('admin.login.post') }}" autocomplete="on">
    @csrf
    <div class="brand big">m<b>CMS</b></div>
    <p class="muted">{{ setting('site_name', '') }}</p>

    @if (session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @error('email')<div class="flash err">{{ $message }}</div>@enderror

    <label>Email
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="190">
    </label>
    <label>Password
        <input type="password" name="password" required autocomplete="current-password" maxlength="200">
    </label>
    <button class="btn primary block">Sign in</button>
</form>
<script src="{{ asset('assets/js/admin.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
</body>
</html>
