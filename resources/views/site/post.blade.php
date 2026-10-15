@extends('site.layout')

@section('content')
<article class="entry">
    @if($preview)<div class="preview-banner">Преглед — тази публикация още не е публично видима.</div>@endif
    <header>
        @if($content->category && ($ct = $content->category->tr()))
            <a class="kicker" href="{{ $ct->url() }}">{{ $ct->name }}</a>
        @endif
        <h1>{{ $tr->title }}</h1>
        <p class="meta"><time datetime="{{ ($content->publish_at ?? $content->created_at)->toAtomString() }}">{{ ($content->publish_at ?? $content->created_at)->translatedFormat('j F Y') }}</time></p>
    </header>
    @if($content->featured)
        <img class="hero" src="{{ $content->featured->url() }}" alt="{{ $content->featured->alt ?: $tr->title }}" @if($content->featured->width) width="{{ $content->featured->width }}" height="{{ $content->featured->height }}" @endif>
    @endif
    <div class="prose">{!! mcms_body($tr->body, $content) !!}</div>
</article>

@if($related->isNotEmpty())
<section class="related">
    <h2>{{ t('site.latest_posts') }}</h2>
    <div class="cards">
        @foreach ($related as $p)
            @include('site.partials.card', ['post' => $p])
        @endforeach
    </div>
</section>
@endif
@endsection
