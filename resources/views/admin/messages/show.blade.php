@extends('admin.layout')
@section('title', 'Message')

@section('actions')<a class="btn" href="{{ route('admin.messages.index') }}">← All</a>@endsection

@section('content')
<section class="card">
    <dl class="meta">
        <dt>From</dt><dd>{{ $message->name }} &lt;<a href="mailto:{{ $message->email }}">{{ $message->email }}</a>&gt;</dd>
        <dt>Subject</dt><dd>{{ $message->subject ?: '—' }}</dd>
        <dt>Received</dt><dd>{{ $message->created_at->format('d.m.Y H:i') }} · IP {{ $message->ip }} · language {{ $message->locale }}</dd>
    </dl>
    <div class="message-body">{!! nl2br(e($message->body)) !!}</div>
    <p>
        <a class="btn primary" href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: '')) }}">Reply</a>
    </p>
</section>
<form method="post" action="{{ route('admin.messages.destroy', $message) }}" data-confirm="Delete this message?">@csrf @method('DELETE')<button class="btn danger">Delete</button></form>
@endsection
