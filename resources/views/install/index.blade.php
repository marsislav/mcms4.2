<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('Инсталация — mCMS') }} {{ config('mcms.version') }}</title>
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin.css') }}">
</head>
<body class="auth wide">
<div class="auth-card wide">
    <div class="brand big">m<b>CMS</b> <small>{{ config('mcms.version') }}</small></div>
    <p class="center">@include('admin.partials.lang-switch')</p>
    <h2>{{ __('Инсталация') }}</h2>

    <section>
        <h3>{{ __('1. Изисквания') }}</h3>
        <ul class="health">
            @foreach ($checks as $c)
                <li class="{{ $c['ok'] ? 'ok' : ($c['required'] ? 'warn' : 'info') }}">
                    <span class="dot" aria-hidden="true">{{ $c['ok'] ? '✓' : ($c['required'] ? '✕' : '–') }}</span>
                    <span>{{ $c['label'] }}{{ ! $c['required'] ? __(' (препоръчително)') : '' }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($errors->any())
        <div class="flash err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="post" action="{{ url('/install') }}" class="stack" autocomplete="off">
        @csrf

        <h3>{{ __('2. Защита на инсталацията') }}</h3>
        <label>{{ __('Инсталационен ключ') }}
            <input name="install_key" required maxlength="100" value="{{ old('install_key') }}" placeholder="{{ __('32-символен ключ') }}">
        </label>
        <p class="muted">
            @if($keyConfigured)
                {!! __('Отворете файла <code>.env</code> на сървъра (File Manager/FTP) и копирайте стойността на <code>MCMS_INSTALL_KEY</code>. Това гарантира, че само вие можете да инсталирате сайта. След инсталация ключът се изтрива.') !!}
            @else
                {!! __('В <code>.env</code> липсва <code>MCMS_INSTALL_KEY</code>. Добавете ред <code>MCMS_INSTALL_KEY=нещо-дълго-и-случайно</code> и презаредете.') !!}
            @endif
        </p>

        <h3>{{ __('3. База данни (MySQL / MariaDB)') }}</h3>
        <div class="grid-form">
            <label>{{ __('Хост') }} <input name="db_host" value="{{ old('db_host', 'localhost') }}" required maxlength="190"></label>
            <label>{{ __('Порт') }} <input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></label>
            <label>{{ __('База данни') }} <input name="db_name" value="{{ old('db_name') }}" required maxlength="64"></label>
            <label>{{ __('Потребител') }} <input name="db_user" value="{{ old('db_user') }}" required maxlength="80"></label>
            <label>{{ __('Парола') }} <input type="password" name="db_pass" maxlength="200" autocomplete="new-password"></label>
        </div>

        <h3>{{ __('4. Сайт') }}</h3>
        <div class="grid-form">
            <label>{{ __('Име на сайта') }} <input name="site_name" value="{{ old('site_name') }}" required maxlength="100"></label>
            <label>{{ __('Основен език') }}
                <select name="default_lang">
                    <option value="bg" @selected(old('default_lang', 'bg') === 'bg')>{{ __('Български') }}</option>
                    <option value="en" @selected(old('default_lang') === 'en')>English</option>
                </select>
            </label>
        </div>

        <h3>{{ __('5. Администратор') }}</h3>
        <div class="grid-form">
            <label>{{ __('Име') }} <input name="admin_name" value="{{ old('admin_name') }}" required maxlength="100"></label>
            <label>{{ __('Имейл') }} <input type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="190"></label>
            <label>{{ __('Парола') }} <input type="password" name="admin_password" required minlength="12" autocomplete="new-password"></label>
            <label>{{ __('Повторете паролата') }} <input type="password" name="admin_password_confirmation" required minlength="12" autocomplete="new-password"></label>
        </div>
        {!! __('<small class="muted">Минимум 12 знака, малки и главни букви, цифра.</small>') !!}

        <button class="btn primary block">{{ __('Инсталирай') }}</button>
    </form>
</div>
</body>
</html>
