@extends('admin.layout')
@section('title', __('Миграции'))

@section('content')
<section class="card">
    <h2>{{ __('Миграции на базата данни') }}</h2>
    <p class="muted">{{ __('Миграциите създават или променят таблици. Изпълнявайте ги след качване на нова версия. Изпълнението е безопасно: вече приложените миграции се пропускат и данните не се губят.') }}</p>
    @if($pending > 0)
        <div class="flash err">{{ __('Чакащи миграции: :n', ['n' => $pending]) }}</div>
        <form method="post" action="{{ route('admin.system.migrate') }}" data-confirm="{{ __('Да изпълня ли чакащите миграции? Препоръчително е първо да направите резервно копие.') }}">@csrf
            <button class="btn primary">{{ __('Изпълни чакащите миграции') }}</button>
        </form>
    @else
        <p><span class="tag ok">{{ __('Базата е актуална') }}</span></p>
    @endif
    @if($output)<pre class="code">{{ $output }}</pre>@endif
</section>

<section class="card">
    <table class="table">
        <thead><tr><th>{{ __('Миграция') }}</th><th>{{ __('Състояние') }}</th><th>{{ __('Партида') }}</th></tr></thead>
        <tbody>
        @foreach ($rows as $r)
            <tr>
                <td><code>{{ $r['name'] }}</code></td>
                <td>@if($r['ran'])<span class="tag ok">{{ __('Приложена') }}</span>@else<span class="tag warn">{{ __('Чака') }}</span>@endif</td>
                <td>{{ $r['batch'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endsection
