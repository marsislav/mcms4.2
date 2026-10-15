@extends('admin.layout')
@section('title', 'Users')

@section('actions')<a class="btn primary" href="{{ route('admin.users.create') }}">+ New user</a>@endsection

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>2FA</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $u)
        <tr>
            <td><strong>{{ $u->name }}</strong></td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->roleLabel() }}</td>
            <td>{!! $u->totp_enabled ? '<span class="tag ok">on</span>' : '<span class="tag">off</span>' !!}</td>
            <td>
                @if(! $u->active)<span class="tag warn">deactivated</span>
                @elseif($u->isLocked())<span class="tag warn">locked</span>
                @else<span class="tag ok">active</span>@endif
            </td>
            <td class="muted">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.users.edit', $u) }}">Edit</a>
                @if($u->id !== auth()->id())
                <form method="post" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Delete user {{ $u->email }}?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
