@extends('admin.layout')
@php($isPost = $type === 'post')
@section('title', $isPost ? 'Публикации' : 'Страници')

@section('actions')
    <a class="btn primary" href="{{ route($isPost ? 'admin.posts.create' : 'admin.pages.create') }}">+ Нова {{ $isPost ? 'публикация' : 'страница' }}</a>
@endsection

@section('content')
<form class="filters" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Търсене по заглавие…" maxlength="100">
    <select name="status">
        <option value="">Всички статуси</option>
        <option value="published" @selected(request('status') === 'published')>Публикувани</option>
        <option value="draft" @selected(request('status') === 'draft')>Чернови</option>
    </select>
    <button class="btn">Филтрирай</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Заглавие</th>@if($isPost)<th>Категория</th>@endif<th>Езици</th><th>Статус</th><th>Автор</th><th>Дата</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $c)
        @php($route = $isPost ? 'admin.posts' : 'admin.pages')
        <tr>
            <td><a href="{{ route($route.'.edit', $c) }}"><strong>{{ $c->adminTitle() }}</strong></a>
                @if($c->is_home)<span class="tag">Начална</span>@endif
                @if($c->noindex)<span class="tag muted">noindex</span>@endif</td>
            @if($isPost)<td>{{ $c->category?->adminName() ?? '—' }}</td>@endif
            <td>@foreach($c->translations as $t)<span class="tag">{{ strtoupper($t->locale) }}</span>@endforeach</td>
            <td>
                @if($c->status === 'draft')<span class="tag">Чернова</span>
                @elseif($c->publish_at && $c->publish_at->isFuture())<span class="tag warn">Насрочена</span>
                @else<span class="tag ok">Публикувана</span>@endif
            </td>
            <td>{{ $c->author?->name ?? '—' }}</td>
            <td class="muted">{{ ($c->publish_at ?? $c->created_at)->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route($route.'.edit', $c) }}">Редакция</a>
                <form method="post" action="{{ route($route.'.destroy', $c) }}" data-confirm="Да изтрия ли „{{ $c->adminTitle() }}“?">
                    @csrf @method('DELETE')<button class="btn sm danger">Изтрий</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted center">Няма записи.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links() }}
@endsection
