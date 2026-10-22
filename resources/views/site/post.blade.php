@extends('site.layout')

@section('has_sidebar', '1')

@section('kind', 'post')

@section('content')
@include('site.partials.breadcrumbs')
<article class="entry">
    @if($preview)<div class="preview-banner">{{ __('Преглед — тази публикация още не е публично видима.') }}</div>@endif
    <header>
        @if($content->category && ($ct = $content->category->tr()))
            <a class="kicker" href="{{ $ct->url() }}">{{ $ct->name }}</a>
        @endif
        <h1>{{ $tr->title }}</h1>
        <p class="meta"><time datetime="{{ ($content->publish_at ?? $content->created_at)->toAtomString() }}">{{ mcms_date(($content->publish_at ?? $content->created_at)) }}</time>@if(\App\Support\Options::on('show_author') && $content->author && ($an = $content->author->name)) · {{ t('site.by', __('от')) }} @if($au = \App\Support\Members::authorUrl($content->author))<a href="{{ $au }}" rel="author">{{ $an }}</a>@else{{ $an }}@endif @endif @if(\App\Support\Discussion::enabled() && ($cn = \App\Support\Discussion::approvedCount($content))) · <a href="#comments">{{ t('comments.n', __('Коментари: :n'), ['n' => $cn]) }}</a>@endif @if(\App\Support\Options::on('reading_time')) · {{ t('site.read_time', __(':n мин четене'), ['n' => \App\Support\Options::readingMinutes($tr->body)]) }}@endif @if(\App\Support\Options::on('show_updated') && $content->updated_at && $content->updated_at->diffInHours($content->publish_at ?? $content->created_at) >= 24) · {{ t('site.updated', __('Обновено')) }} <time datetime="{{ $content->updated_at->toAtomString() }}">{{ mcms_date($content->updated_at) }}</time>@endif</p>
    </header>
    @if($content->featured)
        <img class="hero" src="{{ $content->featured->url() }}" alt="{{ $content->featured->alt ?: $tr->title }}" @if($content->featured->width) width="{{ $content->featured->width }}" height="{{ $content->featured->height }}" @endif>
    @endif
    <div class="prose">{!! mcms_body($tr->body, $content) !!}</div>
    @if(\App\Support\Options::on('show_author') && $content->author && trim((string) $content->author->bio) !== '' && \App\Support\Members::ready())
    <aside class="author-box" aria-label="{{ __(':p1', ['p1' => t('author.about', __('За автора'))]) }}">
        <strong>@if($au = \App\Support\Members::authorUrl($content->author))<a href="{{ $au }}" rel="author">{{ $content->author->name }}</a>@else{{ $content->author->name }}@endif</strong>
        <div>{{ excerpt($content->author->bio, 220) }}</div>
    </aside>
    @endif
    @if(\App\Support\Options::on('share_buttons'))
    @php $su = urlencode(url()->current()); @endphp @php $st = urlencode($tr->title); @endphp
    <nav class="share" aria-label="{{ t('site.share', __('Сподели')) }}">
        <strong>{{ t('site.share', __('Сподели')) }}:</strong>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $su }}" target="_blank" rel="noopener noreferrer">Facebook</a>
        <a href="https://twitter.com/intent/tweet?url={{ $su }}&amp;text={{ $st }}" target="_blank" rel="noopener noreferrer">X</a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $su }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>
        <a href="mailto:?subject={{ $st }}&amp;body={{ $su }}">{{ __('Имейл') }}</a>
    </nav>
    @endif
    @if(($tags ?? collect())->isNotEmpty())
    <ul class="tags" aria-label="{{ __(':p1', ['p1' => t('site.tags', __('Тагове'))]) }}">
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
    <h2>{{ t('site.related', __('Свързани публикации')) }}</h2>
    <div class="cards">
        @foreach ($related as $p)
            @include('site.partials.card', ['post' => $p])
        @endforeach
    </div>
</section>
@endif
@endsection
