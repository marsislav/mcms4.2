@extends('admin.layout')
@section('title', __('Категории на обяви'))

@section('actions'){!! __('<a class="btn" href=":p1">← Обяви</a>', ['p1' => e(route('admin.listings.index'))]) !!}@endsection

@section('content')
@if($errors->any())<div class="flash err" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<section class="card narrow-card" id="new">
    <h3>{{ __('Нова категория') }}</h3>
    <form method="post" action="{{ route('admin.listing-categories.store') }}" class="stack">
        @csrf
        <label>{{ __('Име') }} <input name="name" required minlength="2" maxlength="80"></label>
        <label>{{ __('Подредба (по-малко = по-напред)') }} <input type="number" name="sort" min="0" max="9999" value="0"></label>
        <button class="btn primary">{{ __('Добави') }}</button>
    </form>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Име') }}</th><th>{{ __('Подредба') }}</th><th>{{ __('Обяви') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($cats as $c)
        <tr>
            <td colspan="2">
                <form method="post" action="{{ route('admin.listing-categories.update', $c->id) }}" class="inline-form">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $c->name }}" required minlength="2" maxlength="80">
                    <input type="number" name="sort" value="{{ $c->sort }}" min="0" max="9999">
                    <button class="btn sm">{{ __('Запази') }}</button>
                </form>
            </td>
            <td>{{ (int) ($counts[$c->id] ?? 0) }}</td>
            <td class="row-actions">
                <form method="post" action="{{ route('admin.listing-categories.destroy', $c->id) }}" data-confirm="{{ __('Да изтрия ли категорията?') }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted center">{{ __('Още няма категории.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
