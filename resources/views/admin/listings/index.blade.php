@extends('admin.layout')
@section('title', __('Обяви'))

@section('actions'){!! __('<a class="btn" href=":p1">Категории</a>', ['p1' => e(route('admin.listings.categories'))]) !!}@endsection

@section('content')
<p class="filters">
    <a href="{{ route('admin.listings.index') }}" @class(['active' => $status === ''])>{{ __('Всички') }}</a> ·
    <a href="{{ route('admin.listings.index', ['status' => 'pending']) }}" @class(['active' => $status === 'pending'])>{{ __('Чакащи') }} ({{ $counts['pending'] }})</a> ·
    <a href="{{ route('admin.listings.index', ['status' => 'published']) }}" @class(['active' => $status === 'published'])>{{ __('Активни') }} ({{ $counts['published'] }})</a> ·
    <a href="{{ route('admin.listings.index', ['status' => 'expired']) }}" @class(['active' => $status === 'expired'])>{{ __('Изтекли') }} ({{ $counts['expired'] }})</a> ·
    <a href="{{ route('admin.listings.index', ['status' => 'rejected']) }}" @class(['active' => $status === 'rejected'])>{{ __('Отхвърлени') }} ({{ $counts['rejected'] }})</a>
</p>
<form method="get" class="inline-form">
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Търсене…') }}" maxlength="80">
    <button class="btn sm">{{ __('Търси') }}</button>
</form>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Обява') }}</th><th>{{ __('Категория') }}</th><th>{{ __('Автор') }}</th><th>{{ __('Цена') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Валидна до') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($items as $l)
        <tr @class(['unread' => $l->status === 'pending'])>
            <td><a href="{{ route('admin.listings.edit', $l->id) }}"><strong>{{ $l->title }}</strong></a><br><small class="muted">{{ $l->city }}</small></td>
            <td>{{ $l->category?->name }}</td>
            <td>{{ $l->user?->name ?? $l->contact_name }}<br><small class="muted">{{ $l->contact_email ?: $l->contact_phone }}</small></td>
            <td>{{ $l->priceLabel() }}</td>
            <td>{{ $l->stateLabel() }}</td>
            <td class="muted">{{ $l->expires_at?->format('d.m.Y') }}</td>
            <td class="row-actions">
                @if($l->status !== 'published')
                <form method="post" action="{{ route('admin.listings.approve', $l->id) }}">@csrf<button class="btn sm">{{ __('Одобри') }}</button></form>
                @endif
                @if($l->status !== 'rejected')
                <form method="post" action="{{ route('admin.listings.reject', $l->id) }}">@csrf<button class="btn sm">{{ __('Отхвърли') }}</button></form>
                @endif
                <a class="btn sm" href="{{ route('admin.listings.edit', $l->id) }}">{{ __('Редактирай') }}</a>
                <form method="post" action="{{ route('admin.listings.destroy', $l->id) }}" data-confirm="{{ __('Да изтрия ли обявата?') }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted center">{{ __('Няма обяви.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $items->links() }}
@endsection
