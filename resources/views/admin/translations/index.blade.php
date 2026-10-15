@extends('admin.layout')
@section('title', 'Interface translations')

@section('content')
<form class="filters" method="get">
    <input type="search" name="q" value="{{ $q }}" placeholder="Search key or text…" maxlength="80">
    <button class="btn">Search</button>
    @if($q !== '')<a class="btn" href="{{ route('admin.translations.index') }}">Show all</a>@endif
</form>

<form method="post" action="{{ route('admin.translations.update') }}">
    @csrf @method('PUT')

    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>Key</th>@foreach ($languages as $l)<th>{{ strtoupper($l->code) }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @foreach ($keys as $key)
            <tr>
                <td><code>{{ $key }}</code></td>
                @foreach ($languages as $l)
                    @php($v = optional($rows[$key]->firstWhere('locale', $l->code))->value)
                    <td><textarea name="tr[{{ $key }}][{{ $l->code }}]" rows="1" maxlength="2000" class="autogrow">{{ $v }}</textarea></td>
                @endforeach
            </tr>
        @endforeach
        <tr class="new-row">
            <td><input name="new_key" placeholder="new.key (e.g. footer.phone)" pattern="[a-z0-9_]+(\.[a-z0-9_]+)+" maxlength="150"></td>
            @foreach ($languages as $l)
                <td><input name="new_value[{{ $l->code }}]" maxlength="2000" placeholder="Value ({{ $l->code }})"></td>
            @endforeach
        </tr>
        </tbody>
    </table>
    </div>
    <p class="muted">An empty value deletes the translation for this language (the default language is then used). Use <code>t('key')</code> in the theme and plugins. You can use <code>:q</code> and other placeholders.</p>
    <button class="btn primary">Save translations</button>
</form>
@endsection
