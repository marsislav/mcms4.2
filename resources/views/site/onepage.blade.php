@extends('site.layout-page')

@section('kind', 'page')

@section('content')
@php
    $d = fn ($k) => \App\Support\Design::get($k);
    $kids = $d('op_children') === '1';
@endphp
@if($preview)<div class="preview-banner">{{ __('Преглед — тази страница още не е публично видима.') }}</div>@endif
<div class="op" data-op data-title="{{ $d('op_title') }}" data-kids="{{ $kids ? 1 : 0 }}" data-parallax="{{ $d('op_parallax') }}" data-snap="{{ $d('op_snap') }}" data-full="{{ $d('op_full') }}">
    @foreach($sections as $i => $s)
        @php($c = $s['content'])
        @php($t = $s['tr'])
        <section id="{{ $s['id'] }}" class="op-sec" data-op-anim="{{ $s['anim'] }}" aria-label="{{ $t->title }}">
            <header class="op-head op-a">
                @if($i === 0)
                    <h1 @if(($hl = \App\Support\Design::h1Word($c->id, $t->locale)) !== '') data-hl="{{ $hl }}" @endif>{{ $t->title }}</h1>
                @else
                    <h2 class="op-title">{{ $t->title }}</h2>
                @endif
            </header>
            @if($c->featured)
                <div class="op-hero op-a"><img src="{{ $c->featured->url() }}" alt="{{ $c->featured->alt ?: $t->title }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" @if($c->featured->width) width="{{ $c->featured->width }}" height="{{ $c->featured->height }}" @endif></div>
            @endif
            <div @class(['prose', 'op-prose', 'op-a' => ! $kids])>{!! mcms_body($t->body, $c) !!}</div>
        </section>
    @endforeach
</div>

@if($d('op_dots') === '1' && count($sections) > 1)
    <nav class="op-dots" aria-label="{{ __('Секции') }}">
        @foreach($sections as $s)
            <a href="#{{ $s['id'] }}" data-for="{{ $s['id'] }}"><span>{{ $s['label'] }}</span></a>
        @endforeach
    </nav>
@endif
@endsection
