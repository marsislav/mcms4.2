@extends('site.layout')

@section('content')
@include('site.partials.breadcrumbs')
<article class="entry ls-show">
    @if($preview)
        <div class="notice err" role="status">{{ t('listings.preview', 'Тази обява не е публична (:s).', ['s' => $l->stateLabel()]) }}</div>
    @endif
    <header>
        @if($l->category)<a class="kicker" href="{{ lroute('listings') }}?cat={{ $l->category->slug }}">{{ $l->category->name }}</a>@endif
        <h1>{{ $l->title }}</h1>
        <p class="ls-price">{{ $l->priceLabel() }}</p>
        <p class="meta">@if($l->city){{ $l->city }} · @endif{{ mcms_date($l->published_at ?? $l->created_at) }}@if($l->expires_at) · {{ t('listings.valid_until', 'валидна до') }} {{ mcms_date($l->expires_at) }}@endif</p>
    </header>

    @if($images->isNotEmpty())
    <div class="ls-gallery">
        @foreach ($images as $m)
            <a href="{{ $m->url() }}" target="_blank" rel="noopener"><img src="{{ $m->thumbUrl() }}" alt="{{ $l->title }}" loading="lazy"></a>
        @endforeach
    </div>
    @endif

    <div class="ls-body">{!! nl2br(e($l->body)) !!}</div>

    <section class="ls-contact" aria-label="{{ t('listings.contact', 'Контакт') }}">
        <h2>{{ t('listings.contact', 'Контакт') }}</h2>
        <p><strong>{{ $l->contact_name }}</strong></p>
        @if($l->contact_phone)<p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $l->contact_phone) }}">{{ $l->contact_phone }}</a></p>@endif
        @if($l->contact_email)<p><a href="mailto:{{ $l->contact_email }}">{{ $l->contact_email }}</a></p>@endif
    </section>

    @if($mine)
        <p class="ls-actions"><a class="btn" href="{{ lroute('listings.edit', ['id' => $l->id]) }}">{{ t('listings.edit', 'Редакция на обява') }}</a></p>
    @endif
    <p><a href="{{ lroute('listings') }}">← {{ t('listings.title', 'Обяви') }}</a></p>
</article>
@endsection
