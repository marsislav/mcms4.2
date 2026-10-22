@extends('admin.layout')
@section('title', __('Журнал на грешките'))

@section('content')
@if(! $files)
    <section class="card"><p class="muted">{{ __('Няма лог файлове в storage/logs. Няма записани грешки.') }}</p></section>
@else
<section class="card">
    <form method="get" class="grid-form">
        <label>{{ __('Файл') }}
            <select name="file" data-autosubmit>@foreach ($files as $f)<option value="{{ $f['name'] }}" @selected($f['name'] === $file)>{{ $f['name'] }} ({{ number_format($f['size'] / 1024, 0) }} KB)</option>@endforeach</select>
        </label>
        <label>{{ __('Ниво') }}
            <select name="level" data-autosubmit><option value="">{{ __('Всички') }}</option>@foreach (\App\Support\ErrorLog::LEVELS as $lv)<option value="{{ $lv }}" @selected($lv === $level)>{{ $lv }}</option>@endforeach</select>
        </label>
        <div><button class="btn sm">{{ __('Покажи') }}</button></div>
    </form>
    <div class="row-actions">
        <a class="btn sm" href="{{ route('admin.errors.download', ['file' => $file]) }}">{{ __('Изтегли') }}</a>
        <form method="post" action="{{ route('admin.errors.clear') }}" data-confirm="{{ __('Да изчистя ли този лог файл?') }}">@csrf<input type="hidden" name="file" value="{{ $file }}"><button class="btn sm danger">{{ __('Изчисти') }}</button></form>
    </div>
    @if($truncated)<p class="muted">{{ __('Показан е само краят на файла (Настройки → Администрация).') }}</p>@endif
</section>
@forelse ($entries as $e)
    <details class="card log-entry log-{{ $e['level'] }}">
        <summary><span class="badge">{{ $e['level'] }}</span> <small class="muted">{{ $e['date'] }}</small> {{ $e['message'] }}</summary>
        @if($e['detail'] !== '')<pre class="log-detail">{{ $e['detail'] }}</pre>@endif
    </details>
@empty
    <p class="muted center">{{ __('Няма записи.') }}</p>
@endforelse
@endif
@endsection
