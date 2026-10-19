@extends('admin.layout')
@section('title', __('Потребители'))

@section('actions'){!! __('<a class="btn primary" href=":p1">+ Нов потребител</a>', ['p1' => e(route('admin.users.create'))]) !!}@endsection

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Име') }}</th><th>{{ __('Имейл') }}</th><th>{{ __('Роля') }}</th><th>{{ __('Статус') }}</th><th>{{ __('Последен вход') }}</th><th></th></tr></thead>
    <tbody>
    @foreach ($users as $u)
        <tr>
            <td><strong>{{ $u->name }}</strong></td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->roleLabel() }}</td>
            <td>
                @if(! $u->active){!! __('<span class="tag warn">деактивиран</span>') !!}
                @elseif($u->isLocked()){!! __('<span class="tag warn">заключен</span>') !!}
                @else{!! __('<span class="tag ok">активен</span>') !!}@endif
            </td>
            <td class="muted">{{ $u->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
            <td class="row-actions">
                {!! __('<a class="btn sm" href=":p1">Редакция</a>', ['p1' => e(route('admin.users.edit', $u))]) !!}
                @if($u->id !== auth()->id())
                <form method="post" action="{{ route('admin.users.destroy', $u) }}" data-confirm="{{ __('Да изтрия ли потребителя :p1?', ['p1' => $u->email]) }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
