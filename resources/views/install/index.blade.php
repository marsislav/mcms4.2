<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Installation — mCMS {{ config('mcms.version') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ config('mcms.version') }}">
</head>
<body class="auth wide">
<div class="auth-card wide">
    <div class="brand big">m<b>CMS</b> <small>{{ config('mcms.version') }}</small></div>
    <h2>Installation</h2>

    <section>
        <h3>1. Requirements</h3>
        <ul class="health">
            @foreach ($checks as $c)
                <li class="{{ $c['ok'] ? 'ok' : ($c['required'] ? 'warn' : 'info') }}">
                    <span class="dot" aria-hidden="true">{{ $c['ok'] ? '✓' : ($c['required'] ? '✕' : '–') }}</span>
                    <span>{{ $c['label'] }}{{ ! $c['required'] ? ' (recommended)' : '' }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($errors->any())
        <div class="flash err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="post" action="{{ url('/install') }}" class="stack" autocomplete="off">
        @csrf

        <h3>2. Installation protection</h3>
        <label>Installation key
            <input name="install_key" required maxlength="100" value="{{ old('install_key') }}" placeholder="32-character key">
        </label>
        <p class="muted">
            @if($keyConfigured)
                Open the <code>.env</code> file on the server (File Manager/FTP) and copy the value of <code>MCMS_INSTALL_KEY</code>. This ensures that only you can install the site. The key is removed after installation.
            @else
                <code>MCMS_INSTALL_KEY</code> is missing from <code>.env</code>. Add the line <code>MCMS_INSTALL_KEY=something-long-and-random</code> and reload.
            @endif
        </p>

        <h3>3. Database (MySQL / MariaDB)</h3>
        <div class="grid-form">
            <label>Host <input name="db_host" value="{{ old('db_host', 'localhost') }}" required maxlength="190"></label>
            <label>Port <input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></label>
            <label>Database <input name="db_name" value="{{ old('db_name') }}" required maxlength="64"></label>
            <label>Username <input name="db_user" value="{{ old('db_user') }}" required maxlength="80"></label>
            <label>Password <input type="password" name="db_pass" maxlength="200" autocomplete="new-password"></label>
        </div>

        <h3>4. Site</h3>
        <div class="grid-form">
            <label>Site name <input name="site_name" value="{{ old('site_name') }}" required maxlength="100"></label>
            <label>Default language
                <select name="default_lang">
                    <option value="en" @selected(old('default_lang', 'en') === 'en')>English</option>
                    <option value="bg" @selected(old('default_lang') === 'bg')>Bulgarian</option>
                </select>
            </label>
        </div>

        <h3>5. Administrator</h3>
        <div class="grid-form">
            <label>Name <input name="admin_name" value="{{ old('admin_name') }}" required maxlength="100"></label>
            <label>Email <input type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="190"></label>
            <label>Password <input type="password" name="admin_password" required minlength="12" autocomplete="new-password"></label>
            <label>Repeat password <input type="password" name="admin_password_confirmation" required minlength="12" autocomplete="new-password"></label>
        </div>
        <small class="muted">At least 12 characters, with lowercase and uppercase letters and a digit.</small>

        <button class="btn primary block">Install</button>
    </form>
</div>
</body>
</html>
