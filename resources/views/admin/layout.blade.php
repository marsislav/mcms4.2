<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mcms-admin" content="{{ admin_url() }}">
    <title>@yield('title', 'Табло') — {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v={{ config('mcms.version') }}">
    @stack('styles')
</head>
<body class="admin">
@php
    $me = auth()->user();
    $unread = $me->allows('messages') ? \App\Models\Message::whereNull('read_at')->count() : 0;
    $nav = [
        ['admin.dashboard', 'Табло', null, null],
        ['—', 'Съдържание'],
        ['admin.pages.index', 'Страници', 'content', null],
        ['admin.posts.index', 'Публикации', 'content', null],
        ['admin.categories.index', 'Категории', 'categories', null],
        ['admin.media.index', 'Медия', 'media', null],
        ['admin.menus.index', 'Менюта', 'menus', null],
        ['admin.messages.index', 'Съобщения', 'messages', $unread],
        ['—', 'Сайт'],
        ['admin.translations.index', 'Преводи', 'translations', null],
        ['admin.languages.index', 'Езици', 'languages', null],
        ['admin.redirects.index', 'Пренасочвания', 'redirects', null],
        ['admin.settings', 'Настройки', 'settings', null],
        ['—', 'Система'],
        ['admin.users.index', 'Потребители', 'users', null],
        ['admin.audit', 'Журнал', 'audit', null],
        ['admin.backups.index', 'Архиви', 'backups', null],
        ['admin.plugins.index', 'Плъгини', 'plugins', null],
    ];
    $nav = apply_filters('admin.menu', $nav);
@endphp

<input type="checkbox" id="nav-toggle" class="nav-toggle" hidden>
<aside class="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}">m<b>CMS</b> <small>{{ config('mcms.version') }}</small></a>
    <nav>
        @foreach ($nav as $item)
            @if ($item[0] === '—')
                <div class="nav-group">{{ $item[1] }}</div>
            @elseif (! $item[2] || $me->allows($item[2]))
                @php($active = request()->routeIs(substr_count($item[0], '.') >= 2 ? Str::beforeLast($item[0], '.').'.*' : $item[0].'*'))
                <a href="{{ route($item[0]) }}" @class(['active' => $active])>
                    {{ $item[1] }}
                    @if (! empty($item[3]))<span class="badge">{{ $item[3] }}</span>@endif
                </a>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-foot">
        <a href="{{ route('admin.profile') }}">{{ $me->name }}</a>
        <small>{{ $me->roleLabel() }}</small>
        <a href="{{ lroute('home') }}" target="_blank" rel="noopener">Виж сайта ↗</a>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="link">Изход</button></form>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <label for="nav-toggle" class="burger" aria-label="Меню">☰</label>
        <h1>@yield('title', 'Табло')</h1>
        <div class="topbar-actions">@yield('actions')</div>
    </header>

    <div class="content">
        @if (session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="flash err" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="flash err" role="alert">
                <strong>Моля, коригирайте:</strong>
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<script src="{{ asset('assets/js/admin.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
@stack('scripts')
</body>
</html>
