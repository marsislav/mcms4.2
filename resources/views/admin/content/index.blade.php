@extends('admin.layout')
@php($isPost = $type === 'post')
@section('title', $isPost ? 'Posts' : 'Pages')

@section('actions')
    <a class="btn primary" href="{{ route($isPost ? 'admin.posts.create' : 'admin.pages.create') }}">+ New {{ $isPost ? 'post' : 'page' }}</a>
@endsection

@section('content')
<form class="filters" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by title…" maxlength="100">
    <select name="status">
        <option value="">All statuses</option>
        <option value="published" @selected(request('status') === 'published')>Published</option>
        <option value="draft" @selected(request('status') === 'draft')>Drafts</option>
    </select>
    <button class="btn">Filter</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Title</th>@if($isPost)<th>Category</th>@endif<th>Languages</th><th>Status</th><th>Author</th><th>Date</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $c)
        @php($route = $isPost ? 'admin.posts' : 'admin.pages')
        <tr>
            <td><a href="{{ route($route.'.edit', $c) }}"><strong>{{ $c->adminTitle() }}</strong></a>
                @if($c->is_home)<span class="tag">Home</span>@endif
                @if($c->noindex)<span class="tag muted">noindex</span>@endif</td>
            @if($isPost)<td>{{ $c->category?->adminName() ?? '—' }}</td>@endif
            <td>@foreach($c->translations as $t)<span class="tag">{{ strtoupper($t->locale) }}</span>@endforeach</td>
            <td>
                @if($c->status === 'draft')<span class="tag">Draft</span>
                @elseif($c->publish_at && $c->publish_at->isFuture())<span class="tag warn">Scheduled</span>
                @else<span class="tag ok">Published</span>@endif
            </td>
            <td>{{ $c->author?->name ?? '—' }}</td>
            <td class="muted">{{ ($c->publish_at ?? $c->created_at)->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route($route.'.edit', $c) }}">Edit</a>
                <form method="post" action="{{ route($route.'.destroy', $c) }}" data-confirm="Delete “{{ $c->adminTitle() }}”?">
                    @csrf @method('DELETE')<button class="btn sm danger">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted center">No records.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links() }}
@endsection
