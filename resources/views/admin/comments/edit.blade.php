@extends('admin.layout')
@section('title', __('Коментар #').$comment->id)

@section('content')
@php $tr = $comment->content?->translations->first(); @endphp
<p class="muted">{!! __('Към: <strong>:p1</strong> · :p2 · IP', ['p1' => e($tr?->title ?? '—'), 'p2' => e($comment->created_at->format('d.m.Y H:i'))]) !!} {{ $comment->author_ip }}</p>
@if($comment->parent)<div class="card">{!! __('<small class="muted">В отговор на :p1:</small>', ['p1' => e($comment->parent->author_name)]) !!}<div>{!! \App\Support\Discussion::format(\Illuminate\Support\Str::limit($comment->parent->body, 300)) !!}</div></div>@endif

<div class="cols">
    <section class="card">
        <h2>{{ __('Редакция') }}</h2>
        <form method="post" action="{{ route('admin.comments.update', $comment) }}" class="stack">
            @csrf @method('PUT')
            <label>{{ __('Име') }} <input name="author_name" value="{{ old('author_name', $comment->author_name) }}" required maxlength="100"></label>
            <label>{{ __('Имейл') }} <input type="email" name="author_email" value="{{ old('author_email', $comment->author_email) }}" maxlength="190"></label>
            <label>{{ __('Уебсайт') }} <input type="url" name="author_url" value="{{ old('author_url', $comment->author_url) }}" maxlength="200"></label>
            <label>{{ __('Коментар') }} <textarea name="body" rows="7" required maxlength="5000">{{ old('body', $comment->body) }}</textarea></label>
            <label>{{ __('Статус') }}
                <select name="status">
                    @foreach (\App\Models\Comment::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $comment->status) === $k)>{{ __($l) }}</option>@endforeach
                </select>
            </label>
            <button class="btn primary">{{ __('Запази') }}</button>
        </form>
    </section>

    <section class="card" id="reply">
        <h2>{{ __('Отговор') }}</h2>
        <p class="muted">{{ __('Публикува се веднага като „:p1“ (и одобрява този коментар).', ['p1' => auth()->user()->name]) }}</p>
        <form method="post" action="{{ route('admin.comments.reply', $comment) }}" class="stack">
            @csrf
            <label>{{ __('Текст') }} <textarea name="body" rows="6" required maxlength="5000"></textarea></label>
            <button class="btn primary">{{ __('Публикувай отговора') }}</button>
        </form>
        <p>{!! __('<a href=":p1">← Към всички коментари</a>', ['p1' => e(route('admin.comments.index'))]) !!}</p>
    </section>
</div>
@endsection
