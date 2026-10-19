@extends('admin.layout')
@section('title', __('Категории'))

@section('content')
<section class="card">
    <h2>{{ __('Нова категория') }}</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="grid-form">
        @csrf
        @foreach ($languages as $lang)
            <label>{{ __('Име (:p1)', ['p1' => strtoupper($lang->code)]) }} <input name="tr[{{ $lang->code }}][name]" maxlength="150" @if($lang->is_default) required @endif></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" maxlength="150" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <label>{{ __('Подредба') }} <input type="number" name="sort" value="0" min="0" max="65000"></label>
        <div><button class="btn primary">{{ __('Добави') }}</button></div>
    </form>
</section>

@foreach ($categories as $cat)
<section class="card">
    <h3>{{ $cat->adminName() }} {!! __('<small class="muted">· :p1 публикации</small>', ['p1' => e($cat->contents_count)]) !!}</h3>
    <form method="post" action="{{ route('admin.categories.update', $cat) }}" class="grid-form">
        @csrf @method('PUT')
        @foreach ($languages as $lang)
            @php($t = $cat->tr($lang->code))
            <label>{{ __('Име (:p1)', ['p1' => strtoupper($lang->code)]) }} <input name="tr[{{ $lang->code }}][name]" value="{{ $t?->name }}" maxlength="150"></label>
            <label>Slug ({{ strtoupper($lang->code) }}) <input name="tr[{{ $lang->code }}][slug]" value="{{ $t?->slug }}" maxlength="150" pattern="[a-z0-9\-]*"></label>
        @endforeach
        <label>{{ __('Подредба') }} <input type="number" name="sort" value="{{ $cat->sort }}" min="0" max="65000"></label>
        <div><button class="btn">{{ __('Запази') }}</button></div>
    </form>
    <form method="post" action="{{ route('admin.categories.destroy', $cat) }}" data-confirm="{{ __(':p1', ['p1' => \App\Support\Trash::ready() ? __('Категорията ще бъде преместена в кошчето (публикациите остават).') : __('Категорията ще бъде изтрита (публикациите остават).')]) }}">
        @csrf @method('DELETE')<button class="btn sm danger">{{ \App\Support\Trash::ready() ? __('В кошчето') : __('Изтрий категорията') }}</button>
    </form>
</section>
@endforeach

@if($trashed->isNotEmpty())
<section class="card">
    <h2>{!! __('Кошче <small class="muted">· :p1</small>', ['p1' => e($trashed->count())]) !!}</h2>
    @foreach ($trashed as $tc)
        <div class="row-actions" style="justify-content:space-between;border-top:1px solid var(--line);padding:.5rem 0">
            {!! __('<span>:p1 <small class="muted">· изтрита :p2</small></span> <span class="row-actions">', ['p1' => e($tc->adminName()), 'p2' => e($tc->deleted_at->format('d.m.Y H:i'))]) !!}
                <form method="post" action="{{ route('admin.categories.untrash', $tc->id) }}">@csrf<button class="btn sm">{{ __('Възстанови') }}</button></form>
                <form method="post" action="{{ route('admin.categories.purge', $tc->id) }}" data-confirm="{{ __('Категорията ще бъде изтрита ЗАВИНАГИ (публикациите остават без категория).') }}">
                    @csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий завинаги') }}</button>
                </form>
            </span>
        </div>
    @endforeach
</section>
@endif
@endsection
