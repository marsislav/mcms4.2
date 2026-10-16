@extends('admin.layout')
@php($isPost = $type === 'post')
@section('title', $isPost ? 'Публикации' : 'Страници')

@section('actions')
    <a class="btn primary" href="{{ route($isPost ? 'admin.posts.create' : 'admin.pages.create') }}">+ Нова {{ $isPost ? 'публикация' : 'страница' }}</a>
@endsection

@section('content')
@php($route = $isPost ? 'admin.posts' : 'admin.pages')
@if(! is_null($trashCount))
<nav class="subtabs">
    <a href="{{ route($route.'.index') }}" @class(['active' => ! $trash])>Активни</a>
    <a href="{{ route($route.'.index', ['trash' => 1]) }}" @class(['active' => $trash])>Кошче @if($trashCount)<span class="tag">{{ $trashCount }}</span>@endif</a>
    @if($trash && $trashCount)
        <form method="post" action="{{ route($route.'.trash.empty') }}" data-confirm="Всички записи в кошчето ще бъдат изтрити ЗАВИНАГИ. Продължавате ли?" class="inline-form" style="margin-left:auto">
            @csrf @method('DELETE')<button class="btn sm danger">Изпразни кошчето</button>
        </form>
    @endif
</nav>
@endif
<form class="filters" method="get">
    @if($trash)<input type="hidden" name="trash" value="1">@endif
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Търсене по заглавие…" maxlength="100">
    @unless($trash)
    <select name="status">
        <option value="">Всички статуси</option>
        <option value="published" @selected(request('status') === 'published')>Публикувани</option>
        <option value="draft" @selected(request('status') === 'draft')>Чернови</option>
    </select>
    @endunless
    <button class="btn">Филтрирай</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Заглавие</th>@if($isPost)<th>Категория</th>@endif<th>Езици</th><th>Статус</th><th>Автор</th><th>{{ $trash ? 'Изтрито' : 'Дата' }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $c)
        <tr>
            <td>@if($trash)<strong>{{ $c->adminTitle() }}</strong>@else<a href="{{ route($route.'.edit', $c) }}"><strong>{{ $c->adminTitle() }}</strong></a>@endif
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
            <td class="muted">{{ $trash ? $c->deleted_at->format('d.m.Y H:i') : ($c->publish_at ?? $c->created_at)->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                @if($trash)
                    <form method="post" action="{{ route($route.'.untrash', $c->id) }}">@csrf<button class="btn sm">Възстанови</button></form>
                    <form method="post" action="{{ route($route.'.purge', $c->id) }}" data-confirm="„{{ $c->adminTitle() }}“ ще бъде изтрито ЗАВИНАГИ. Продължавате ли?">
                        @csrf @method('DELETE')<button class="btn sm danger">Изтрий завинаги</button>
                    </form>
                @else
                    <a class="btn sm" href="{{ route($route.'.edit', $c) }}">Редакция</a>
                    <form method="post" action="{{ route($route.'.destroy', $c) }}" data-confirm="{{ is_null($trashCount) ? 'Да изтрия ли' : 'Да преместя ли в кошчето' }} „{{ $c->adminTitle() }}“?">
                        @csrf @method('DELETE')<button class="btn sm danger">{{ is_null($trashCount) ? 'Изтрий' : 'В кошчето' }}</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted center">{{ $trash ? 'Кошчето е празно.' : 'Няма записи.' }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links() }}
@endsection
