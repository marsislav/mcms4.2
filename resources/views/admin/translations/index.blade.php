@extends('admin.layout')
@section('title', __('Преводи на интерфейса'))

@section('content')
<form class="filters" method="get">
    <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Търси ключ или текст…') }}" maxlength="80">
    <button class="btn">{{ __('Търси') }}</button>
    @if($q !== ''){!! __('<a class="btn" href=":p1">Покажи всички</a>', ['p1' => e(route('admin.translations.index'))]) !!}@endif
</form>

<form method="post" action="{{ route('admin.translations.update') }}">
    @csrf @method('PUT')

    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr><th>{{ __('Ключ') }}</th>@foreach ($languages as $l)<th>{{ strtoupper($l->code) }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @foreach ($keys as $key)
            <tr>
                <td><code>{{ $key }}</code></td>
                @foreach ($languages as $l)
                    @php $v = optional($rows[$key]->firstWhere('locale', $l->code))->value; @endphp
                    <td><textarea name="tr[{{ $key }}][{{ $l->code }}]" rows="1" maxlength="2000" class="autogrow">{{ $v }}</textarea></td>
                @endforeach
            </tr>
        @endforeach
        <tr class="new-row">
            <td><input name="new_key" placeholder="{{ __('нов.ключ (напр. footer.phone)') }}" pattern="[a-z0-9_]+(\.[a-z0-9_]+)+" maxlength="150"></td>
            @foreach ($languages as $l)
                <td><input name="new_value[{{ $l->code }}]" maxlength="2000" placeholder="{{ __('Стойност (:p1)', ['p1' => $l->code]) }}"></td>
            @endforeach
        </tr>
        </tbody>
    </table>
    </div>
    <p class="muted">{!! __('Празна стойност изтрива превода за този език (тогава се ползва основният). Използвайте <code>t(\'ключ\')</code> в темата и плъгините. Можете да ползвате <code>:q</code> и други заместители.') !!}</p>
    <button class="btn primary">{{ __('Запази преводите') }}</button>
</form>
@endsection
