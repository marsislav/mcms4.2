@extends('admin.layout')
@section('title', 'Потребители')

@section('actions')<a class="btn primary" href="{{ route('admin.users.create') }}">+ Нов потребител</a>@endsection

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>Име</th><th>Имейл</th><th>Роля</th><th>2FA</th><th>Статус</th><th>Последен вход</th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $u)
        <tr>
            <td><strong>{{ $u->name }}</strong></td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->roleLabel() }}</td>
            <td>{!! $u->totp_enabled ? '<span class="tag ok">вкл.</span>' : '<span class="tag">изкл.</span>' !!}</td>
            <td>
                @if(! $u->active)<span class="tag warn">деактивиран</span>
                @elseif($u->isLocked())<span class="tag warn">заключен</span>
                @else<span class="tag ok">активен</span>@endif
            </td>
            <td class="muted">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.users.edit', $u) }}">Редакция</a>
                @if($u->id !== auth()->id())
                <form method="post" action="{{ route('admin.users.destroy', $u) }}" data-confirm="Да изтрия ли потребителя {{ $u->email }}?">@csrf @method('DELETE')<button class="btn sm danger">Изтрий</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
