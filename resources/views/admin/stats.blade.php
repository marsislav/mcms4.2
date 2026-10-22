@extends('admin.layout')
@section('title', __('Статистика'))

@section('content')
@if(! $ready)
    <div class="flash err">{{ __('Таблицата още не е създадена.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Обновете базата данни') }}</a></div>
@else
<div class="stats">
    <div class="stat"><span class="num">{{ number_format($s['today'], 0, '.', ' ') }}</span>{{ __('Днес') }}</div>
    <div class="stat"><span class="num">{{ number_format($s['week'], 0, '.', ' ') }}</span>{{ __('Последните 7 дни') }}</div>
    <div class="stat"><span class="num">{{ number_format($s['month'], 0, '.', ' ') }}</span>{{ __('Последните 30 дни') }}</div>
</div>
@unless(\App\Support\Options::on('stats_on'))<div class="flash err">{{ __('Броячът е изключен (Настройки → Статистика).') }}</div>@endunless

@php
    $daily = [];
    for ($i = 29; $i >= 0; $i--) { $d = now()->subDays($i)->toDateString(); $daily[$d] = (int) ($s['daily'][$d] ?? 0); }
    $max = max(1, max($daily));
@endphp
<section class="card">
    <h2>{{ __('Посещения по дни (30 дни)') }}</h2>
    <div class="bars" role="img" aria-label="{{ __('Посещения по дни') }}">
        @foreach ($daily as $d => $n)<span class="bar" style="--h: {{ round($n / $max * 100) }}" title="{{ $d }}: {{ $n }}"></span>@endforeach
    </div>
</section>

<section class="card">
    <h2>{{ __('Най-посещавани (30 дни)') }}</h2>
    <table class="table">
        <thead><tr><th>{{ __('Какво') }}</th><th>{{ __('Вид') }}</th><th>{{ __('Посещения') }}</th></tr></thead>
        <tbody>
        @forelse ($s['top'] as $t)
            @php
                $kind = match (true) { str_starts_with($t->view_key, 'c:') => __('Съдържание'), str_starts_with($t->view_key, 'cat:') => __('Категория'), str_starts_with($t->view_key, 'tag:') => __('Таг'), default => __('Страница') };
            @endphp
            <tr><td>{{ $t->label ?: $t->view_key }}</td><td>{{ $kind }}</td><td>{{ number_format($t->total, 0, '.', ' ') }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted center">{{ __('Още няма данни.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endif
@endsection
