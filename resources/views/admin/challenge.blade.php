<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Двуфакторна проверка — {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ config('mcms.version') }}">
</head>
<body class="auth">
<form class="auth-card" method="post" action="{{ route('admin.challenge.post') }}">
    @csrf
    <div class="brand big">m<b>CMS</b></div>
    <h2>Двуфакторна проверка</h2>
    <p class="muted">Въведете 6-цифрения код от приложението за удостоверяване или код за възстановяване.</p>

    @error('code')<div class="flash err">{{ $message }}</div>@enderror

    <label>Код
        <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="text" maxlength="32" placeholder="123456">
    </label>
    <button class="btn primary block">Потвърди</button>
    <p class="center"><a href="{{ route('admin.login') }}">← Обратно към входа</a></p>
</form>
</body>
</html>
