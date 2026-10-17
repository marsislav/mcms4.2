@extends('site.layout')

@section('content')
<article class="entry">
    @if($preview)<div class="preview-banner">Преглед — тази публикация още не е публично видима.</div>@endif
    <header>
        @if($content->category && ($ct = $content->category->tr()))
            <a class="kicker" href="{{ $ct->url() }}">{{ $ct->name }}</a>
        @endif
        <h1>{{ $tr->title }}</h1>
        <p class="meta"><time datetime="{{ ($content->publish_at ?? $content->created_at)->toAtomString() }}">{{ ($content->publish_at ?? $content->created_at)->translatedFormat('j F Y') }}</time>@if(\App\Support\Discussion::enabled() && ($cn = \App\Support\Discussion::approvedCount($content))) · <a href="#comments">{{ t('comments.n', 'Коментари: :n', ['n' => $cn]) }}</a>@endif</p>
    </header>
    @if($content->featured)
        <img class="hero" src="{{ $content->featured->url() }}" alt="{{ $content->featured->alt ?: $tr->title }}" @if($content->featured->width) width="{{ $content->featured->width }}" height="{{ $content->featured->height }}" @endif>
    @endif
    <div class="prose">{!! mcms_body($tr->body, $content) !!}</div>
    @if(($tags ?? collect())->isNotEmpty())
    <ul class="tags" aria-label="{{ t('site.tags', 'Тагове') }}">
        @foreach ($tags as $tg)
            @if($d = $tg->display())<li><a href="{{ $d->url() }}" rel="tag">{{ $d->name }}</a></li>@endif
        @endforeach
    </ul>
    @endif
</article>

@if(\App\Support\Discussion::visible($content))
    @include('site.partials.comments', ['content' => $content])
@endif

@if($related->isNotEmpty())
<section class="related">
    <h2>{{ t('site.related', 'Свързани публикации') }}</h2>
    <div class="cards">
        @foreach ($related as $p)
            @include('site.partials.card', ['post' => $p])
        @endforeach
    </div>
</section>
@endif
@endsection
