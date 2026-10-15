@extends('admin.layout')
@section('title', 'Журнал на действията')

@section('content')
<form class="filters" method="get">
    <input name="action" value="{{ request('action') }}" placeholder="Действие (напр. login, page.)" maxlength="40">
    <select name="user">
        <option value="">Всички потребители</option>
        @foreach ($users as $u)<option value="{{ $u->id }}" @selected((int) request('user') === $u->id)>{{ $u->name }}</option>@endforeach
    </select>
    <button class="btn">Филтрирай</button>
</form>

<div class="table-wrap">
<table class="table compact">
    <thead><tr><th>Време</th><th>Потребител</th><th>Действие</th><th>Обект</th><th>Детайли</th><th>IP</th></tr></thead>
    <tbody>
    @forelse ($logs as $l)
        <tr>
            <td class="muted nowrap">{{ $l->created_at->format('d.m.Y H:i:s') }}</td>
            <td>{{ $l->user?->name ?? '—' }}</td>
            <td><code>{{ $l->action }}</code></td>
            <td>{{ $l->subject }}</td>
            <td class="muted" title="{{ $l->user_agent }}">{{ \Illuminate\Support\Str::limit((string) $l->details, 60) }}</td>
            <td class="muted">{{ $l->ip }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted center">Няма записи.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $logs->links() }}
<p class="muted">Записите се пазят 180 дни.</p>
@endsection
