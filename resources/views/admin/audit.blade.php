@extends('admin.layout')
@section('title', 'Audit log')

@section('content')
<form class="filters" method="get">
    <input name="action" value="{{ request('action') }}" placeholder="Action (e.g. login, page.)" maxlength="40">
    <select name="user">
        <option value="">All users</option>
        @foreach ($users as $u)<option value="{{ $u->id }}" @selected((int) request('user') === $u->id)>{{ $u->name }}</option>@endforeach
    </select>
    <button class="btn">Filter</button>
</form>

<div class="table-wrap">
<table class="table compact">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Object</th><th>Details</th><th>IP</th></tr></thead>
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
        <tr><td colspan="6" class="muted center">No records.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $logs->links() }}
<p class="muted">Entries are kept for 180 days.</p>
@endsection
