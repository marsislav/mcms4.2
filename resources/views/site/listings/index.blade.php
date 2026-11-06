@extends('site.layout')

@section('kind', 'list')

@section('content')
@include('site.partials.breadcrumbs')
<header class="page-head">
    <h1>{{ $cat ? $cat->name : t('listings.title', 'Обяви') }}</h1>
    <p class="muted">{{ t('listings.intro', 'Малки обяви от нашите читатели.') }}</p>
    <p class="ls-actions">
        <a class="btn" href="{{ lroute('listings.create') }}">{{ t('listings.add', 'Подай обява') }}</a>
        @auth<a href="{{ lroute('listings.mine') }}">{{ t('listings.mine', 'Моите обяви') }}</a>@endauth
    </p>
    @if($cats->isNotEmpty())
    <nav class="ls-cats" aria-label="{{ t('listings.categories', 'Категории') }}">
        <a href="{{ lroute('listings') }}" @class(['on' => ! $cat])>{{ t('listings.all', 'Всички') }}</a>
        @foreach ($cats as $c)<a href="{{ lroute('listings') }}?cat={{ $c->slug }}" @class(['on' => $cat && $cat->id === $c->id])>{{ $c->name }}</a>@endforeach
    </nav>
    @endif
    <form class="ls-search" method="get" action="{{ lroute('listings') }}" role="search">
        @if($cat)<input type="hidden" name="cat" value="{{ $cat->slug }}">@endif
        <input type="search" name="q" value="{{ $q }}" maxlength="80" placeholder="{{ t('listings.search', 'Търсене в обявите…') }}" aria-label="{{ t('listings.search', 'Търсене в обявите…') }}">
        <button class="btn">{{ t('nav.search', 'Търсене') }}</button>
    </form>
</header>

@if(session('ls_ok'))<div class="notice ok" role="status">{{ session('ls_ok') }}</div>@endif

@if($items->isEmpty())
    <p class="muted">{{ t('listings.none', 'Няма обяви.') }}</p>
@else
    <div class="cards">
        @foreach ($items as $l)
            @include('site.listings.card', ['l' => $l, 'thumb' => $thumbs[$l->id] ?? null])
        @endforeach
    </div>
    {{ $items->links() }}
@endif
@endsection
