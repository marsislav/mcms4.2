@extends('admin.layout')
@section('title', __('Сравнение на версии'))
@section('actions')<a class="btn" href="{{ route($t->content->type === 'post' ? 'admin.posts.edit' : 'admin.pages.edit', $t->content) }}">{{ __('← Към редактора') }}</a>@endsection

@section('content')
<section class="card">
    <form method="get" class="grid-form">
        @foreach (['a' => $a, 'b' => $b] as $name => $cur)
            <label>{{ $name === 'a' ? __('Стара версия') : __('Нова версия') }}
                <select name="{{ $name }}">
                    <option value="current" @selected($cur['id'] === 'current')>{{ __('Текуща версия') }}</option>
                    @foreach ($revs as $r)<option value="{{ $r->id }}" @selected($cur['id'] === (string) $r->id)>{{ $r->created_at?->format('d.m.Y H:i') }}{{ $r->user ? ' · '.$r->user->name : '' }}</option>@endforeach
                </select>
            </label>
        @endforeach
        <div><button class="btn primary">{{ __('Сравни') }}</button></div>
    </form>
    <p class="muted">{{ __(':a добавени, :d премахнати, :c променени реда', ['a' => $stats['add'], 'd' => $stats['del'], 'c' => $stats['chg']]) }}</p>
    @foreach ([$a, $b] as $side)
        @if($side['rev'])
            <form method="post" action="{{ route('admin.revisions.restore', $side['rev']) }}" data-confirm="{{ __('Текущото съдържание ще бъде заменено (и запазено като версия). Продължавате ли?') }}" style="display:inline">@csrf
                <button class="btn sm">{{ __('Възстанови версията от :d', ['d' => $side['label']]) }}</button></form>
        @endif
    @endforeach
</section>

<div class="diff" role="table">
    @foreach ($ops as $o)
        @if($o['t'] === 'eq')
            <div class="diff-row eq"><div>{{ $o['a'] }}</div><div>{{ $o['b'] }}</div></div>
        @elseif($o['t'] === 'chg')
            @php [$ho, $hn] = \App\Support\Diff::words($o['a'], $o['b']); @endphp
            <div class="diff-row chg"><div>{!! $ho !!}</div><div>{!! $hn !!}</div></div>
        @elseif($o['t'] === 'del')
            <div class="diff-row del"><div>{{ $o['a'] }}</div><div></div></div>
        @else
            <div class="diff-row add"><div></div><div>{{ $o['b'] }}</div></div>
        @endif
    @endforeach
</div>
@endsection
