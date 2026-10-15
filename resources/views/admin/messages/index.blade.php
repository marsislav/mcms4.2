@extends('admin.layout')
@section('title', 'Съобщения')

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th></th><th>От</th><th>Тема</th><th>Получено</th><th></th></tr></thead>
    <tbody>
    @forelse ($messages as $m)
        <tr @class(['unread' => ! $m->read_at])>
            <td>{{ $m->read_at ? '' : '●' }}</td>
            <td><a href="{{ route('admin.messages.show', $m) }}"><strong>{{ $m->name }}</strong></a> <small class="muted">{{ $m->email }}</small></td>
            <td>{{ $m->subject ?: \Illuminate\Support\Str::limit($m->body, 60) }}</td>
            <td class="muted">{{ $m->created_at->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.messages.show', $m) }}">Отвори</a>
                <form method="post" action="{{ route('admin.messages.destroy', $m) }}" data-confirm="Да изтрия ли съобщението?">@csrf @method('DELETE')<button class="btn sm danger">Изтрий</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">Няма съобщения.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $messages->links() }}
@endsection
