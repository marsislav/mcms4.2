@extends('admin.layout')
@section('title', 'Messages')

@section('content')
<div class="table-wrap">
<table class="table">
    <thead><tr><th></th><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
    <tbody>
    @forelse ($messages as $m)
        <tr @class(['unread' => ! $m->read_at])>
            <td>{{ $m->read_at ? '' : '●' }}</td>
            <td><a href="{{ route('admin.messages.show', $m) }}"><strong>{{ $m->name }}</strong></a> <small class="muted">{{ $m->email }}</small></td>
            <td>{{ $m->subject ?: \Illuminate\Support\Str::limit($m->body, 60) }}</td>
            <td class="muted">{{ $m->created_at->format('d.m.Y H:i') }}</td>
            <td class="row-actions">
                <a class="btn sm" href="{{ route('admin.messages.show', $m) }}">Open</a>
                <form method="post" action="{{ route('admin.messages.destroy', $m) }}" data-confirm="Delete this message?">@csrf @method('DELETE')<button class="btn sm danger">Delete</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">No messages.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $messages->links() }}
@endsection
