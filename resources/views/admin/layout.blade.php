<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mcms-admin" content="{{ admin_url() }}">
    <title>@yield('title', __('Табло')) — {{ setting('site_name', 'mCMS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/css/all.min.css') }}?v=7.3.1">
    <link rel="stylesheet" href="{{ asset_v('assets/css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin">
@php
    $me = auth()->user();
    $unread = $me->allows('messages') ? \App\Models\Message::whereNull('read_at')->count() : 0;
    $pendingComments = ($me->allows('comments') && \App\Support\Discussion::ready()) ? \App\Models\Comment::where('status', 'pending')->count() : 0;
    $nav = [
        ['admin.dashboard', 'Табло', null, null],
        ['—', 'Съдържание'],
        ['admin.pages.index', 'Страници', 'pages', null],
        ['admin.posts.index', 'Публикации', 'content', null],
        ['admin.categories.index', 'Категории', 'categories', null],
        ['admin.tags.index', 'Тагове', 'categories', null],
        ['admin.media.index', 'Медия', 'media', null],
        ['admin.menus.index', 'Менюта', 'menus', null],
        ['admin.comments.index', 'Коментари', 'comments', $pendingComments],
        ['admin.messages.index', 'Съобщения', 'messages', $unread],
        ['—', 'Сайт'],
        ['admin.translations.index', 'Преводи', 'translations', null],
        ['admin.languages.index', 'Езици', 'languages', null],
        ['admin.redirects.index', 'Пренасочвания', 'redirects', null],
        ['admin.notfound.index', 'Журнал 404', 'notfound', null],
        ['admin.blocks.index', 'Блокове и шаблони', 'blocks', null],
        ['admin.stats.index', 'Статистика', 'stats', null],
        ['admin.subscribers.index', 'Абонати', 'subscribers', null],
        ['admin.settings', 'Настройки', 'settings', null],
        ['—', 'Система'],
        ['admin.users.index', 'Потребители', 'users', null],
        ['admin.audit', 'Журнал', 'audit', null],
        ['admin.errors.index', 'Журнал на грешките', 'errors', null],
        ['admin.system.migrations', 'Миграции', 'settings', null],
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
                <div class="nav-group">{{ __($item[1]) }}</div>
            @elseif (! $item[2] || $me->allows($item[2]))
                @php $active = request()->routeIs(substr_count($item[0], '.') >= 2 ? Str::beforeLast($item[0], '.').'.*' : $item[0].'*'); @endphp
                <a href="{{ route($item[0]) }}" @class(['active' => $active])>
                    {{ __($item[1]) }}
                    @if (! empty($item[3]))<span class="badge">{{ $item[3] }}</span>@endif
                </a>
            @endif
        @endforeach
    </nav>
    <div class="sidebar-foot">
        {!! __('<a href=":p1">:p2</a> <small>:p3</small> <a href=":p4" target="_blank" rel="noopener">Виж сайта ↗</a>', ['p1' => e(route('admin.profile')), 'p2' => e($me->name), 'p3' => e($me->roleLabel()), 'p4' => e(lroute('home'))]) !!}
        @include('admin.partials.lang-switch')
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="link">{{ __('Изход') }}</button></form>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <label for="nav-toggle" class="burger" aria-label="{{ __('Меню') }}">☰</label>
        <h1>@yield('title', __('Табло'))</h1>
        <div class="topbar-actions">
            @yield('actions')
            @if($me->allows('content') || $me->allows('pages') || $me->allows('categories'))
            <details class="quick-new">
                <summary class="btn sm">＋ {{ __('Ново') }}</summary>
                <div class="quick-menu">
                    @if($me->allows('content'))<a href="{{ route('admin.posts.create') }}">{{ __('Статия') }}</a>@endif
                    @if($me->allows('pages'))<a href="{{ route('admin.pages.create') }}">{{ __('Страница') }}</a>@endif
                    @if($me->allows('categories'))<a href="{{ route('admin.categories.index') }}#new">{{ __('Категория') }}</a>@endif
                    @if($me->allows('blocks') && \App\Support\Tables::has('blocks'))<a href="{{ route('admin.blocks.create') }}">{{ __('Блок') }}</a>@endif
                </div>
            </details>
            @endif
        </div>
    </header>

    <div class="content">
        @if ($me->allows('settings') && ! request()->routeIs('admin.system.*') && collect(\App\Support\Tables::OPTIONAL)->contains(fn ($t) => ! \App\Support\Tables::has($t)))
            <div class="flash err" role="alert">{{ __('Базата данни се нуждае от обновяване за новите функции.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Обнови') }}</a></div>
        @endif
        @if (session('status'))<div class="flash ok" role="status">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="flash err" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="flash err" role="alert">
                {!! __('<strong>Моля, коригирайте:</strong>') !!}
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>
</main>

@include('admin.partials.i18n')
<script src="{{ asset_v('assets/js/admin.js') }}" nonce="{{ csp_nonce() }}" defer></script>
@stack('scripts')
</body>
</html>
