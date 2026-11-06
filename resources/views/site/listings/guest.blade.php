@extends('site.layout')

@section('content')
@include('site.partials.breadcrumbs')
<article class="entry narrow">
    <header><h1>{{ t('listings.add', 'Подай обява') }}</h1></header>
    <p class="lead">{{ t('listings.login_needed', 'За да подадете обява, влезте в профила си.') }}</p>
    <p class="ls-actions">
        <a class="btn" href="{{ route('admin.login') }}">{{ t('listings.login', 'Вход') }}</a>
        @if($canRegister)<a href="{{ route('register') }}">{{ t('listings.register', 'Нямате профил? Регистрация') }}</a>@endif
    </p>
    <p><a href="{{ lroute('listings') }}">← {{ t('listings.title', 'Обяви') }}</a></p>
</article>
@endsection
