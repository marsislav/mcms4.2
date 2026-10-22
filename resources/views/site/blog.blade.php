@extends('site.layout')

@section('has_sidebar', '1')

@section('kind', 'list')

@section('content')
@include('site.partials.breadcrumbs')
<header class="page-head">
    <h1>{{ $heading }}</h1>
    @if($category && $category->description)<p class="muted">{{ $category->description }}</p>@endif
</header>

@if($posts->isEmpty())
    <p class="muted">{{ t('site.no_posts') }}</p>
@else
    <div class="cards">
        @foreach ($posts as $post)
            @include('site.partials.card', ['post' => $post])
        @endforeach
    </div>
    {{ $posts->links() }}
@endif
@endsection
