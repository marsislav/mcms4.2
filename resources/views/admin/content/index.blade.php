@extends('admin.layout')
@php($isPost = $type === 'post')
@section('title', $isPost ? __('Публикации') : __('Страници'))

@section('actions')
    {!! __('<a class="btn primary" href=":p1">+ Нова :p2</a>', ['p1' => e(route($isPost ? 'admin.posts.create' : 'admin.pages.create')), 'p2' => e($isPost ? __('публикация') : __('страница'))]) !!}
@endsection

@section('content')
@php($route = $isPost ? 'admin.posts' : 'admin.pages')
@if(! is_null($trashCount))
<nav class="subtabs">
    {!! __('<a href=":p1" @class([\'active\' => ! $trash])>Активни</a> <a href=":p2" @class([\'active\' => $trash])>Кошче', ['p1' => e(route($route.'.index')), 'p2' => e(route($route.'.index', ['trash' => 1]))]) !!} @if($trashCount)<span class="tag">{{ $trashCount }}</span>@endif</a>
    @if($trash && $trashCount)
        <form method="post" action="{{ route($route.'.trash.empty') }}" data-confirm="{{ __('Всички записи в кошчето ще бъдат изтрити ЗАВИНАГИ. Продължавате ли?') }}" class="inline-form" style="margin-left:auto">
            @csrf @method('DELETE')<button class="btn sm danger">{{ __('Изпразни кошчето') }}</button>
        </form>
    @endif
</nav>
@endif
<form class="filters" method="get">
    @if($trash)<input type="hidden" name="trash" value="1">@endif
    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Търсене по заглавие…') }}" maxlength="100">
    @unless($trash)
    <select name="status">
        <option value="">{{ __('Всички статуси') }}</option>
        <option value="published" @selected(request('status') === 'published')>{{ __('Публикувани') }}</option>
        <option value="pending" @selected(request('status') === 'pending')>{{ __('Чакат одобрение') }}</option>
        <option value="draft" @selected(request('status') === 'draft')>{{ __('Чернови') }}</option>
    </select>
    @endunless
    <button class="btn">{{ __('Филтрирай') }}</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Заглавие') }}</th>@if($isPost)<th>{{ __('Категория') }}</th>@endif<th>{{ __('Езици') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Автор') }}</th><th>{{ $trash ? __('Изтрито') : __('Дата') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $c)
        <tr>
            <td>@if($trash)<strong>{{ $c->adminTitle() }}</strong>@else<a href="{{ route($route.'.edit', $c) }}"><strong>{{ $c->adminTitle() }}</strong></a>@endif
                @if($c->is_home){!! __('<span class="tag">Начална</span>') !!}@endif
                @if($c->noindex)<span class="tag muted">noindex</span>@endif</td>
            @if($isPost)<td>{{ $c->category?->adminName() ?? '—' }}</td>@endif
            <td>@foreach($c->translations as $t)<span class="tag">{{ strtoupper($t->locale) }}</span>@endforeach</td>
            <td>
                @if($c->status === 'draft'){!! __('<span class="tag">Чернова</span>') !!}
                @elseif($c->status === 'pending'){!! __('<span class="tag warn">Чака одобрение</span>') !!}
                @elseif($c->publish_at && $c->publish_at->isFuture()){!! __('<span class="tag warn">Насрочена</span>') !!}
                @else{!! __('<span class="tag ok">Публикувана</span>') !!}@endif
            </td>
            <td>{{ $c->author?->name ?? '—' }}</td>
            <td class="muted">{{ $trash ? $c->deleted_at->format('d.m.Y H:i') : ($c->publish_at ?? $c->created_at)->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                @if($trash)
                    <form method="post" action="{{ route($route.'.untrash', $c->id) }}">@csrf<button class="btn sm">{{ __('Възстанови') }}</button></form>
                    <form method="post" action="{{ route($route.'.purge', $c->id) }}" data-confirm="{{ __('„:p1“ ще бъде изтрито ЗАВИНАГИ. Продължавате ли?', ['p1' => $c->adminTitle()]) }}">
                        @csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий завинаги') }}</button>
                    </form>
                @else
                    {!! __('<a class="btn sm" href=":p1">Редакция</a>', ['p1' => e(route($route.'.edit', $c))]) !!}
                    <form method="post" action="{{ route($route.'.destroy', $c) }}" data-confirm="{{ __(':p1 „:p2“?', ['p1' => is_null($trashCount) ? __('Да изтрия ли') : __('Да преместя ли в кошчето'), 'p2' => $c->adminTitle()]) }}">
                        @csrf @method('DELETE')<button class="btn sm danger">{{ is_null($trashCount) ? __('Изтрий') : __('В кошчето') }}</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted center">{{ $trash ? __('Кошчето е празно.') : __('Няма записи.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links() }}
@endsection
