@extends('site.layout-page')

@section('kind', 'page')

@section('content')
@include('site.partials.breadcrumbs')
<article class="entry-page">
    @if($preview)<div class="preview-banner">{{ __('Преглед — тази страница още не е публично видима.') }}</div>@endif
    <header>
        <h1 @if(($hl = \App\Support\Design::h1Word($content->id, $tr->locale)) !== '') data-hl="{{ $hl }}" @endif>{{ $tr->title }}</h1>
    </header>
    @if($content->featured)
        <img class="hero" src="{{ $content->featured->url() }}" alt="{{ $content->featured->alt ?: $tr->title }}" @if($content->featured->width) width="{{ $content->featured->width }}" height="{{ $content->featured->height }}" @endif>
    @endif
    <div class="prose">{!! mcms_body($tr->body, $content) !!}</div>
</article>

@if(\App\Support\Discussion::visible($content))
    @include('site.partials.comments', ['content' => $content])
@endif
@endsection
