@extends('admin.layout')
@section('title', 'Languages')

@section('content')
<section class="card">
    <h2>Add language</h2>
    <form method="post" action="{{ route('admin.languages.store') }}" class="grid-form">
        @csrf
        <label>Code (ISO) <input name="code" required maxlength="5" pattern="[a-z]{2}(-[A-Z]{2})?" placeholder="de"></label>
        <label>Name <input name="name" required maxlength="60" placeholder="German"></label>
        <label>Native name <input name="native_name" maxlength="60" placeholder="Deutsch"></label>
        <div><button class="btn primary">Add</button></div>
    </form>
    <p class="muted">Languages are stored entirely in the database. After adding one, translate the interface under “Translations” and the content in the page forms.</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>Code</th><th>Name</th><th>Native name</th><th>#</th><th>Active</th><th>Default</th><th></th></tr></thead>
    <tbody>
    @foreach ($languages as $l)
        <tr>
            <td><form method="post" action="{{ route('admin.languages.update', $l) }}" id="lang-{{ $l->id }}">@csrf @method('PUT')</form><code>{{ $l->code }}</code></td>
            <td><input form="lang-{{ $l->id }}" name="name" value="{{ $l->name }}" required maxlength="60"></td>
            <td><input form="lang-{{ $l->id }}" name="native_name" value="{{ $l->native_name }}" maxlength="60"></td>
            <td><input form="lang-{{ $l->id }}" type="number" name="sort" value="{{ $l->sort }}" class="narrow" min="0" max="1000"></td>
            <td><input form="lang-{{ $l->id }}" type="checkbox" name="is_active" value="1" @checked($l->is_active) @disabled($l->is_default)></td>
            <td><input form="lang-{{ $l->id }}" type="radio" name="is_default" value="1" @checked($l->is_default) @disabled($l->is_default)>
                @if($l->is_default)<span class="tag ok">default</span>@endif</td>
            <td class="row-actions">
                <button class="btn sm" form="lang-{{ $l->id }}">Save</button>
                @unless($l->is_default)
                    <form method="post" action="{{ route('admin.languages.destroy', $l) }}" data-confirm="ALL translations and content in this language will be deleted. Are you sure?">
                        @csrf @method('DELETE')<button class="btn sm danger">Delete</button>
                    </form>
                @endunless
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<p class="muted">To change the default language, select the radio button of the desired language and click “Save” on its row.</p>
@endsection
