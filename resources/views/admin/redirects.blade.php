@extends('admin.layout')
@section('title', __('Пренасочвания (301/302)'))

@section('content')
<section class="card">
    <h2>{{ __('Ново пренасочване') }}</h2>
    <form method="post" action="{{ route('admin.redirects.store') }}" class="grid-form">
        @csrf
        <label>{{ __('От път') }} <input name="from_path" required maxlength="190" placeholder="/stara-stranitsa" value="{{ old('from_path') }}"></label>
        <label>{{ __('Към') }} <input name="to_url" required maxlength="500" placeholder="{{ __('/nova-stranitsa или https://…') }}" value="{{ old('to_url') }}"></label>
        <label>{{ __('Код') }}
            <select name="code"><option value="301">{{ __('301 — постоянно') }}</option><option value="302">{{ __('302 — временно') }}</option></select>
        </label>
        <div><button class="btn primary">{{ __('Запази') }}</button></div>
    </form>
    <p class="muted">{{ __('Пренасочванията се прилагат само когато адресът не съществува (404).') }}</p>
</section>

<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('От') }}</th><th>{{ __('Към') }}</th><th>{{ __('Код') }}</th><th>{{ __('Посещения') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($redirects as $r)
        <tr>
            <td><code>{{ $r->from_path }}</code></td>
            <td>{{ $r->to_url }}</td>
            <td>{{ $r->code }}</td>
            <td>{{ $r->hits }}</td>
            <td class="row-actions"><form method="post" action="{{ route('admin.redirects.destroy', $r) }}" data-confirm="{{ __('Да изтрия ли пренасочването?') }}">@csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button></form></td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">{{ __('Няма пренасочвания.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $redirects->links() }}
@endsection
