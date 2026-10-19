@extends('site.layout')

@section('content')
<header class="page-head">
    <h1>{{ t('nav.search') }}</h1>
    <form class="search" method="get" action="{{ lroute('search') }}" role="search">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ t('site.search_placeholder') }}" maxlength="80" required minlength="3" aria-label="{{ t('nav.search') }}">
        <button class="btn">{{ t('site.search_button') }}</button>
    </form>
</header>

@if($q !== '' && mb_strlen($q) < 3)
    <p class="muted">{{ t('site.search_min') }}</p>
@elseif($results)
    <h2 class="small">{{ t('site.search_results', null, ['q' => $q]) }}</h2>
    @forelse ($results as $r)
        @php($r->content->loadMissing('featured'))
        <article class="result">
            <h3><a href="{{ $r->url() }}">{{ $r->title }}</a></h3>
            <p>{{ $r->excerpt ?: excerpt($r->body, max(200, \App\Support\Options::excerptLength())) }}</p>
        </article>
    @empty
        <p class="muted">{{ t('site.no_results') }}</p>
    @endforelse
    {{ $results->links() }}
@endif
@endsection
