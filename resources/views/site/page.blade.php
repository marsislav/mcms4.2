@extends('site.layout')

@section('content')
<article class="entry">
    @if($preview)<div class="preview-banner">Preview — this page is not publicly visible yet.</div>@endif
    <header>
        <h1>{{ $tr->title }}</h1>
    </header>
    @if($content->featured)
        <img class="hero" src="{{ $content->featured->url() }}" alt="{{ $content->featured->alt ?: $tr->title }}" @if($content->featured->width) width="{{ $content->featured->width }}" height="{{ $content->featured->height }}" @endif>
    @endif
    <div class="prose">{!! apply_filters('content.body', $tr->body, $content) !!}</div>
</article>
@endsection
