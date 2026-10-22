@extends('site.layout')

@section('has_sidebar', '1')

@section('kind', 'list')

@section('content')
@include('site.partials.breadcrumbs')
<header class="page-head author-head">
    <h1>{{ $author->name }}</h1>
    @if($author->website)<p class="muted"><a href="{{ $author->website }}" rel="nofollow noopener noreferrer me" target="_blank">{{ preg_replace('~^https?://(www\.)?~i', '', rtrim($author->website, '/')) }}</a></p>@endif
</header>

@if(trim((string) $author->bio) !== '')
    <section class="prose author-bio" aria-label="{{ __(':p1', ['p1' => t('author.resume', __('Резюме'))]) }}">{!! $author->bio !!}</section>
@endif

<h2 class="author-posts-title">{{ t('author.posts', __('Статии от :name'), ['name' => $author->name]) }}</h2>
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
