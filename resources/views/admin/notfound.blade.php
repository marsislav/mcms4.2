@extends('admin.layout')
@section('title', __('Журнал 404'))

@section('content')
@if(! $ready)
    <div class="flash err">{{ __('Таблицата още не е създадена.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Обновете базата данни') }}</a></div>
@else
<section class="card">
    <p class="muted">{{ __('Адреси, които посетителите са търсили, но не съществуват. Подредени по брой посещения — създайте пренасочване с един клик.') }}
        @unless(\App\Support\Options::on('notfound_log'))<strong> {{ __('Записването е изключено (Настройки → SEO и навигация).') }}</strong>@endunless</p>
    @if($rows->total())
    <form method="post" action="{{ route('admin.notfound.clear') }}" data-confirm="{{ __('Да изчистя ли целия журнал?') }}">@csrf<button class="btn sm danger">{{ __('Изчисти журнала') }}</button></form>
    @endif
</section>
<div class="table-wrap">
<table class="table">
    <thead><tr><th>{{ __('Адрес') }}</th><th>{{ __('Посещения') }}</th><th>{{ __('Последно') }}</th><th>{{ __('Пренасочи към') }}</th><th></th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
        <tr>
            <td><code>{{ $r->path }}</code>@if($r->referrer)<br><small class="muted">← {{ \Illuminate\Support\Str::limit($r->referrer, 70) }}</small>@endif</td>
            <td>{{ $r->hits }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($r->last_seen)->format('d.m.Y H:i') }}</td>
            <td>
                <form method="post" action="{{ route('admin.notfound.redirect', $r->id) }}" class="inline-field">@csrf
                    <input name="to_url" required maxlength="500" placeholder="/ {{ __('или') }} https://…" aria-label="{{ __('Към') }}">
                    <select name="code" aria-label="{{ __('Код') }}"><option value="301">301</option><option value="302">302</option></select>
                    <button class="btn sm primary">{{ __('Пренасочи') }}</button>
                </form>
            </td>
            <td class="row-actions"><form method="post" action="{{ route('admin.notfound.destroy', $r->id) }}">@csrf @method('DELETE')<button class="btn sm" title="{{ __('Игнорирай') }}">✕</button></form></td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted center">{{ __('Няма записи.') }}</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $rows->links() }}
@error('to_url')<div class="flash err">{{ $message }}</div>@enderror
@endif
@endsection
