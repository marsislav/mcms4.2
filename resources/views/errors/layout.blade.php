<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/errors.css') }}">
</head>
<body>
<main>
    <p class="code">@yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <p><a href="{{ url('/') }}">{{ t('error.home', 'Back to home') }}</a></p>
</main>
</body>
</html>
