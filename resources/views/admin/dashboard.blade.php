@extends('admin.layout')
@section('title', 'Табло')

@section('content')
<div class="stats">
    @foreach ($stats as [$label, $value, $url])
        <a class="stat" href="{{ $url }}"><span class="num">{{ $value }}</span><span>{{ $label }}</span></a>
    @endforeach
</div>

<div class="cols">
    <section class="card">
        <h2>Чернови</h2>
        @forelse ($drafts as $d)
            <p><a href="{{ route($d->type === 'post' ? 'admin.posts.edit' : 'admin.pages.edit', $d) }}">{{ $d->adminTitle() }}</a>
                <small class="muted">· {{ $d->type === 'post' ? 'публикация' : 'страница' }} · {{ $d->updated_at->diffForHumans() }}</small></p>
        @empty
            <p class="muted">Няма чернови.</p>
        @endforelse
    </section>

    @if ($messages->isNotEmpty())
    <section class="card">
        <h2>Последни съобщения</h2>
        @foreach ($messages as $m)
            <p><a href="{{ route('admin.messages.show', $m) }}">{{ $m->read_at ? '' : '● ' }}{{ $m->name }}</a>
                <small class="muted">· {{ $m->created_at->diffForHumans() }}</small></p>
        @endforeach
    </section>
    @endif

    @if (count($health))
    <section class="card">
        <h2>Здраве и сигурност</h2>
        <ul class="health">
            @foreach ($health as [$label, $ok, $hint])
                <li class="{{ $ok ? 'ok' : 'warn' }}">
                    <span class="dot" aria-hidden="true">{{ $ok ? '✓' : '!' }}</span>
                    <span>{{ $label }}@unless($ok)@if($hint)<small class="muted"> — {{ $hint }}</small>@endif @endunless</span>
                </li>
            @endforeach
        </ul>
    </section>
    @endif

    @if ($audit->isNotEmpty())
    <section class="card">
        <h2>Последни действия</h2>
        <table class="table compact">
            @foreach ($audit as $a)
                <tr><td>{{ $a->action }}</td><td class="muted">{{ $a->user?->name ?? '—' }}</td><td class="muted">{{ $a->created_at->diffForHumans() }}</td></tr>
            @endforeach
        </table>
    </section>
    @endif
</div>
@endsection
