@extends('admin.layout')
@section('title', __('Абонати'))
@section('actions')@if($ready)<a class="btn sm" href="{{ route('admin.subscribers.export') }}">{{ __('Изтегли CSV (активните)') }}</a>@endif @endsection

@section('content')
@if(! $ready)
    <div class="flash err">{{ __('Таблицата още не е създадена.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Обновете базата данни') }}</a></div>
@else
<div class="tabs">
    <a class="{{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.subscribers.index') }}">{{ __('Всички') }}</a>
    <a class="{{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.subscribers.index', ['f' => 'active']) }}">{{ __('Активни') }} ({{ $counts['active'] }})</a>
    <a class="{{ $filter === 'pending' ? 'active' : '' }}" href="{{ route('admin.subscribers.index', ['f' => 'pending']) }}">{{ __('Чакащи потвърждение') }} ({{ $counts['pending'] }})</a>
    <a class="{{ $filter === 'out' ? 'active' : '' }}" href="{{ route('admin.subscribers.index', ['f' => 'out']) }}">{{ __('Отписани') }} ({{ $counts['out'] }})</a>
</div>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Имейл') }}</th><th>{{ __('Име') }}</th><th>{{ __('Език') }}</th><th>{{ __('Състояние') }}</th><th>{{ __('Дата') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($rows as $s)
        <tr>
            <td>{{ $s->email }}</td><td>{{ $s->name }}</td><td>{{ $s->locale }}</td>
            <td>{{ $s->unsubscribed_at ? __('Отписан') : ($s->confirmed_at ? __('Активен') : __('Чака потвърждение')) }}</td>
            <td>{{ $s->created_at?->format('d.m.Y') }}</td>
            <td class="row-actions"><form method="post" action="{{ route('admin.subscribers.destroy', $s) }}" data-confirm="{{ __('Да изтрия ли абоната?') }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form></td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted center">{{ __('Няма абонати.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $rows->links() }}
@endif
@endsection
