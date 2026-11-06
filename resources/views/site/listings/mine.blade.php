@extends('site.layout')

@section('content')
@include('site.partials.breadcrumbs')
<header class="page-head">
    <h1>{{ t('listings.mine', 'Моите обяви') }}</h1>
    <p class="ls-actions"><a class="btn" href="{{ lroute('listings.create') }}">{{ t('listings.add', 'Подай обява') }}</a></p>
</header>
@if(session('ls_ok'))<div class="notice ok" role="status">{{ session('ls_ok') }}</div>@endif
@if(session('ls_error'))<div class="notice err" role="alert">{{ session('ls_error') }}</div>@endif

@if($items->isEmpty())
    <p class="muted">{{ t('listings.none_mine', 'Все още нямате обяви.') }}</p>
@else
<div class="ls-list">
    @foreach ($items as $l)
    <div class="ls-item">
        @if(isset($thumbs[$l->id]))<img src="{{ $thumbs[$l->id]->thumbUrl() }}" alt="" loading="lazy">@endif
        <div class="ls-item-body">
            <h2><a href="{{ $l->url() }}">{{ $l->title }}</a></h2>
            <p class="meta"><span class="ls-state ls-{{ $l->state() }}">{{ $l->stateLabel() }}</span> · {{ $l->priceLabel() }}@if($l->expires_at) · {{ t('listings.valid_until', 'валидна до') }} {{ mcms_date($l->expires_at) }}@endif · {{ (int) $l->views }} {{ t('listings.views', 'прегледа') }}</p>
            <div class="ls-actions">
                <a class="btn sm" href="{{ lroute('listings.edit', ['id' => $l->id]) }}">{{ t('listings.edit_short', 'Редактирай') }}</a>
                @if($l->status === 'published')
                <form method="post" action="{{ lroute('listings.renew', ['id' => $l->id]) }}">@csrf<button class="btn sm">{{ t('listings.renew', 'Удължи с :n дни', ['n' => $l->days]) }}</button></form>
                @endif
                <form method="post" action="{{ lroute('listings.destroy', ['id' => $l->id]) }}" data-confirm="{{ t('listings.confirm_delete', 'Да изтрия ли обявата?') }}">@csrf<button class="btn sm danger">{{ t('listings.delete', 'Изтрий') }}</button></form>
            </div>
        </div>
    </div>
    @endforeach
</div>
{{ $items->links() }}
@endif
@endsection
