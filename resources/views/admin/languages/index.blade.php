@extends('admin.layout')
@section('title', 'Езици')

@section('content')
<section class="card">
    <h2>Добавяне на език</h2>
    <form method="post" action="{{ route('admin.languages.store') }}" class="grid-form">
        @csrf
        <label>Код (ISO) <input name="code" required maxlength="5" pattern="[a-z]{2}(-[A-Z]{2})?" placeholder="de"></label>
        <label>Име <input name="name" required maxlength="60" placeholder="German"></label>
        <label>Роден език <input name="native_name" maxlength="60" placeholder="Deutsch"></label>
        <div><button class="btn primary">Добави</button></div>
    </form>
    <p class="muted">Езиците са изцяло в базата данни. След добавяне преведете интерфейса от „Преводи“ и съдържанието от формите на страниците.</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Код</th><th>Име</th><th>Роден език</th><th>№</th><th>Активен</th><th>Основен</th><th></th></tr></thead>
    <tbody>
    @foreach ($languages as $l)
        <tr>
            <td><form method="post" action="{{ route('admin.languages.update', $l) }}" id="lang-{{ $l->id }}">@csrf @method('PUT')</form><code>{{ $l->code }}</code></td>
            <td><input form="lang-{{ $l->id }}" name="name" value="{{ $l->name }}" required maxlength="60"></td>
            <td><input form="lang-{{ $l->id }}" name="native_name" value="{{ $l->native_name }}" maxlength="60"></td>
            <td><input form="lang-{{ $l->id }}" type="number" name="sort" value="{{ $l->sort }}" class="narrow" min="0" max="1000"></td>
            <td><input form="lang-{{ $l->id }}" type="checkbox" name="is_active" value="1" @checked($l->is_active) @disabled($l->is_default)></td>
            <td><input form="lang-{{ $l->id }}" type="radio" name="is_default" value="1" @checked($l->is_default) @disabled($l->is_default)>
                @if($l->is_default)<span class="tag ok">основен</span>@endif</td>
            <td class="row-actions">
                <button class="btn sm" form="lang-{{ $l->id }}">Запази</button>
                @unless($l->is_default)
                    <form method="post" action="{{ route('admin.languages.destroy', $l) }}" data-confirm="Ще се изтрият ВСИЧКИ преводи и съдържание на този език. Сигурни ли сте?">
                        @csrf @method('DELETE')<button class="btn sm danger">Изтрий</button>
                    </form>
                @endunless
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<p class="muted">За да смените основния език, отметнете радио бутона на желания и натиснете „Запази“ на неговия ред.</p>
@endsection
