@extends('admin.layout')
@section('title', __('Съобщение'))

@section('actions'){!! __('<a class="btn" href=":p1">← Всички</a>', ['p1' => e(route('admin.messages.index'))]) !!}@endsection

@section('content')
<section class="card">
    <dl class="meta">
        <dt>{{ __('От') }}</dt><dd>{{ $message->name }} &lt;<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>&gt;</dd>
        <dt>{{ __('Тема') }}</dt><dd>{{ $message->subject ?: '—' }}</dd>
        <dt>{{ __('Получено') }}</dt><dd>{{ $message->created_at->format('d.m.Y H:i') }} {{ __('· IP :p1 · език', ['p1' => $message->ip]) }} {{ $message->locale }}</dd>
    </dl>
    <div class="message-body">{!! nl2br(e($message->body)) !!}</div>
    <p>
        {!! __('<a class="btn primary" href="mailto::p1?subject=:p2">Отговори</a>', ['p1' => e($message->email), 'p2' => e(rawurlencode('Re: '.($message->subject ?: '')))]) !!}
    </p>
</section>
<form method="post" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="{{ __('Да изтрия ли съобщението?') }}">@csrf @method('DELETE')<button class="btn danger">{{ __('Изтрий') }}</button></form>
@endsection
