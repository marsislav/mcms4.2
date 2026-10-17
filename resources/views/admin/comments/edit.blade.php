@extends('admin.layout')
@section('title', 'Коментар #'.$comment->id)

@section('content')
@php($tr = $comment->content?->translations->first())
<p class="muted">Към: <strong>{{ $tr?->title ?? '—' }}</strong> · {{ $comment->created_at->format('d.m.Y H:i') }} · IP {{ $comment->author_ip }}</p>
@if($comment->parent)<div class="card"><small class="muted">В отговор на {{ $comment->parent->author_name }}:</small><div>{!! \App\Support\Discussion::format(\Illuminate\Support\Str::limit($comment->parent->body, 300)) !!}</div></div>@endif

<div class="cols">
    <section class="card">
        <h2>Редакция</h2>
        <form method="post" action="{{ route('admin.comments.update', $comment) }}" class="stack">
            @csrf @method('PUT')
            <label>Име <input name="author_name" value="{{ old('author_name', $comment->author_name) }}" required maxlength="100"></label>
            <label>Имейл <input type="email" name="author_email" value="{{ old('author_email', $comment->author_email) }}" maxlength="190"></label>
            <label>Уебсайт <input type="url" name="author_url" value="{{ old('author_url', $comment->author_url) }}" maxlength="200"></label>
            <label>Коментар <textarea name="body" rows="7" required maxlength="5000">{{ old('body', $comment->body) }}</textarea></label>
            <label>Статус
                <select name="status">
                    @foreach (\App\Models\Comment::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $comment->status) === $k)>{{ $l }}</option>@endforeach
                </select>
            </label>
            <button class="btn primary">Запази</button>
        </form>
    </section>

    <section class="card" id="reply">
        <h2>Отговор</h2>
        <p class="muted">Публикува се веднага като „{{ auth()->user()->name }}“ (и одобрява този коментар).</p>
        <form method="post" action="{{ route('admin.comments.reply', $comment) }}" class="stack">
            @csrf
            <label>Текст <textarea name="body" rows="6" required maxlength="5000"></textarea></label>
            <button class="btn primary">Публикувай отговора</button>
        </form>
        <p><a href="{{ route('admin.comments.index') }}">← Към всички коментари</a></p>
    </section>
</div>
@endsection
