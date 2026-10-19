<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Вход —') }} {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin.css') }}">
</head>
<body class="auth">
<form class="auth-card" method="post" action="{{ route('admin.login.post') }}" autocomplete="on">
    @csrf
    <div class="brand big">m<b>CMS</b></div>
    <p class="muted">{{ setting('site_name', '') }}</p>
    <p class="center">@include('admin.partials.lang-switch')</p>

    @if (session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="flash err">{{ session('error') }}</div>@endif
    @error('email')<div class="flash err">{{ $message }}</div>@enderror

    <label>{{ __('Имейл') }}
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" maxlength="190">
    </label>
    <label>{{ __('Парола') }}
        <input type="password" name="password" required autocomplete="current-password" maxlength="200">
    </label>
    <button class="btn primary block">{{ __('Вход') }}</button>
    @if(\App\Support\Members::registrationOpen())<p class="muted center">{!! __('<a href=":p1">Нямате профил? Регистрация</a>', ['p1' => e(route('register'))]) !!}</p>@endif
</form>
@include('admin.partials.i18n')
<script src="{{ asset_v('assets/js/admin.js') }}" nonce="{{ csp_nonce() }}" defer></script>
</body>
</html>
