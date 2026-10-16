@extends('admin.layout')
@section('title', 'Тагове')

@section('content')
@unless($ready)
    <section class="card warn-card">
        <h2>Нужно е обновяване на базата</h2>
        <p>Таговете и свързаните публикации изискват нови таблици. Натиснете бутона (или изпълнете <code>database/upgrade/4.2-upgrade.sql</code> в phpMyAdmin).</p>
        <form method="post" action="{{ route('admin.system.migrate') }}">@csrf <button class="btn primary">Обнови базата</button></form>
    </section>
@else
<section class="card">
    <h2>Нов таг</h2>
    <p class="muted">Таговете се добавят и директно от формата на публикацията (поле „Тагове“). Тук можете да ги превеждате, преименувате и обединявате.</p>
    <form method="post" action="{{ route('admin.tags.store') }}" class="grid-form">
        @csrf
        @foreach ($languages as $lang)
            <label>Име ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][name]" maxlength="80" @if($lang->is_default) required @endif></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" maxlength="120" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <div><button class="btn primary">Добави</button></div>
    </form>
</section>

@forelse ($tags as $tag)
<section class="card">
    <h3>{{ $tag->adminName() }} <small class="muted">· {{ $tag->contents_count }} публикации</small></h3>
    <form method="post" action="{{ route('admin.tags.update', $tag) }}" class="grid-form">
        @csrf @method('PUT')
        @foreach ($languages as $lang)
            @php($t = $tag->tr($lang->code))
            <label>Име ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][name]" value="{{ $t?->name }}" maxlength="80"></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" value="{{ $t?->slug }}" maxlength="120" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <div><button class="btn">Запази</button></div>
    </form>
    <div class="row-actions">
        @if($tags->count() > 1)
        <form method="post" action="{{ route('admin.tags.merge', $tag) }}" data-confirm="Всички публикации ще получат избрания таг, а този ще бъде изтрит. Продължавате ли?" class="inline-form">
            @csrf
            <select name="into" required>
                <option value="">Обедини с…</option>
                @foreach ($tags as $other)@if($other->id !== $tag->id)<option value="{{ $other->id }}">{{ $other->adminName() }}</option>@endif @endforeach
            </select>
            <button class="btn sm">Обедини</button>
        </form>
        @endif
        <form method="post" action="{{ route('admin.tags.destroy', $tag) }}" data-confirm="Тагът ще бъде изтрит (публикациите остават).">
            @csrf @method('DELETE')<button class="btn sm danger">Изтрий</button>
        </form>
    </div>
</section>
@empty
    <p class="muted">Все още няма тагове.</p>
@endforelse
@endunless
@endsection
