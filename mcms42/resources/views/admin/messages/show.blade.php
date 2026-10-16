@extends('admin.layout')
@section('title', 'Съобщение')

@section('actions')<a class="btn" href="{{ route('admin.messages.index') }}">← Всички</a>@endsection

@section('content')
<section class="card">
    <dl class="meta">
        <dt>От</dt><dd>{{ $message->name }} &lt;<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>&gt;</dd>
        <dt>Тема</dt><dd>{{ $message->subject ?: '—' }}</dd>
        <dt>Получено</dt><dd>{{ $message->created_at->format('d.m.Y H:i') }} · IP {{ $message->ip }} · език {{ $message->locale }}</dd>
    </dl>
    <div class="message-body">{!! nl2br(e($message->body)) !!}</div>
    <p>
        <a class="btn primary" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: '')) }}">Отговори</a>
    </p>
</section>
<form method="post" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Да изтрия ли съобщението?">@csrf @method('DELETE')<button class="btn danger">Изтрий</button></form>
@endsection
