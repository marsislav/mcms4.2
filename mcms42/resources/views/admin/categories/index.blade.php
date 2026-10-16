@extends('admin.layout')
@section('title', 'Категории')

@section('content')
<section class="card">
    <h2>Нова категория</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="grid-form">
        @csrf
        @foreach ($languages as $lang)
            <label>Име ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][name]" maxlength="150" @if($lang->is_default) required @endif></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" maxlength="150" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <label>Подредба <input type="number" name="sort" value="0" min="0" max="65000"></label>
        <div><button class="btn primary">Добави</button></div>
    </form>
</section>

@foreach ($categories as $cat)
<section class="card">
    <h3>{{ $cat->adminName() }} <small class="muted">· {{ $cat->contents_count }} публикации</small></h3>
    <form method="post" action="{{ route('admin.categories.update', $cat) }}" class="grid-form">
        @csrf @method('PUT')
        @foreach ($languages as $lang)
            @php($t = $cat->tr($lang->code))
            <label>Име ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][name]" value="{{ $t?->name }}" maxlength="150"></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" value="{{ $t?->slug }}" maxlength="150" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <label>Подредба <input type="number" name="sort" value="{{ $cat->sort }}" min="0" max="65000"></label>
        <div><button class="btn">Запази</button></div>
    </form>
    <form method="post" action="{{ route('admin.categories.destroy', $cat) }}" data-confirm="{{ \App\Support\Trash::ready() ? 'Категорията ще бъде преместена в кошчето (публикациите остават).' : 'Категорията ще бъде изтрита (публикациите остават).' }}">
        @csrf @method('DELETE')<button class="btn sm danger">{{ \App\Support\Trash::ready() ? 'В кошчето' : 'Изтрий категорията' }}</button>
    </form>
</section>
@endforeach

@if($trashed->isNotEmpty())
<section class="card">
    <h2>Кошче <small class="muted">· {{ $trashed->count() }}</small></h2>
    @foreach ($trashed as $tc)
        <div class="row-actions" style="justify-content:space-between;border-top:1px solid var(--line);padding:.5rem 0">
            <span>{{ $tc->adminName() }} <small class="muted">· изтрита {{ $tc->deleted_at->format('d.m.Y H:i') }}</small></span>
            <span class="row-actions">
                <form method="post" action="{{ route('admin.categories.untrash', $tc->id) }}">@csrf<button class="btn sm">Възстанови</button></form>
                <form method="post" action="{{ route('admin.categories.purge', $tc->id) }}" data-confirm="Категорията ще бъде изтрита ЗАВИНАГИ (публикациите остават без категория).">
                    @csrf @method('DELETE')<button class="btn sm danger">Изтрий завинаги</button>
                </form>
            </span>
        </div>
    @endforeach
</section>
@endif
@endsection
