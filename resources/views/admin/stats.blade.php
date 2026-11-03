@extends('admin.layout')
@section('title', __('Statistics'))

@section('content')
@if(! $ready)
    <div class="flash err">{{ __('The table has not been created yet.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Update the database') }}</a></div>
@else
@unless(\App\Support\Options::on('stats_on'))<div class="flash err">{{ __('The counter is switched off (Settings → Statistics).') }}</div>@endunless

@php
    $q = fn (array $extra = []) => array_merge(['range' => $r['preset'], 'from' => $r['from'], 'to' => $r['to']], $extra);
    $fmt = fn ($n) => number_format((int) $n, 0, '.', ' ');
    $ext = ! empty($d['extended']);
    $presets = ['today' => __('Today'), '7' => __('7 days'), '30' => __('30 days'), '90' => __('90 days'), '365' => __('365 days'), 'custom' => __('Custom')];
    $devNames = ['desktop' => __('Desktop'), 'mobile' => __('Mobile'), 'tablet' => __('Tablet')];
    $srcNames = ['direct' => __('Direct'), 'search' => __('Search engines'), 'social' => __('Social networks'), 'ai' => __('AI assistants'), 'email' => __('Email'), 'paid' => __('Paid'), 'referral' => __('Other websites')];
    $dayNames = [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
    $delta = function (?array $x) {
        if (! $x) { return ''; }
        return match ($x['dir']) {
            'up' => '<span class="delta up">▲ '.$x['pct'].'%</span>',
            'down' => '<span class="delta down">▼ '.abs($x['pct']).'%</span>',
            'new' => '<span class="delta up">'.e(__('new')).'</span>',
            default => '<span class="delta">= 0%</span>',
        };
    };
    $dur = fn ($s) => $s >= 60 ? intdiv($s, 60).'m '.($s % 60).'s' : $s.'s';
@endphp

<form method="get" class="stats-range" id="range-form">
    @foreach($presets as $k => $label)
        <button class="btn @if($r['preset'] === $k && $k !== 'custom') on @endif" type="submit" name="range" value="{{ $k }}" formnovalidate>{{ $label }}</button>
    @endforeach
    <span class="muted">{{ __('From') }}</span> <input type="date" name="from" value="{{ $r['from'] }}" max="{{ now()->toDateString() }}">
    <span class="muted">{{ __('to') }}</span> <input type="date" name="to" value="{{ $r['to'] }}" max="{{ now()->toDateString() }}">
    <button class="btn" type="submit" name="range" value="custom">{{ __('Apply') }}</button>
    <span class="muted">{{ $r['from'] }} – {{ $r['to'] }} · {{ __('compared with') }} {{ $r['prev_from'] }} – {{ $r['prev_to'] }}</span>
</form>

<div class="stats">
    <div class="stat"><span class="num">{{ $fmt($s['hits']) }}</span>{{ __('Page views') }} {!! $delta($s['hits_delta']) !!}</div>
    @if($s['uniq'] !== null)<div class="stat"><span class="num">{{ $fmt($s['uniq']) }}</span>{{ __('Unique visitors') }} {!! $delta($s['uniq_delta']) !!}</div>@endif
    <div class="stat"><span class="num">{{ $s['avg_per_day'] }}</span>{{ __('Views per day') }}</div>
    <div class="stat"><span class="num">{{ $fmt($s['today']) }}</span>{{ __('Today') }}</div>
    @if($ext && ! empty($d['paths']['visits']))
        <div class="stat"><span class="num">{{ $d['paths']['avg_pages'] }}</span>{{ __('Pages per visit') }}</div>
        <div class="stat"><span class="num">{{ $d['paths']['bounce'] }}%</span>{{ __('Bounce rate') }}</div>
    @endif
    @if($ext && ! empty($d['engage']['measured']))
        <div class="stat"><span class="num">{{ $dur($d['engage']['avg_seconds']) }}</span>{{ __('Avg. time on page') }}</div>
        <div class="stat"><span class="num">{{ $d['engage']['avg_scroll'] }}%</span>{{ __('Avg. scroll depth') }}</div>
    @endif
</div>

@php
    $series = \App\Support\Stats::buckets($s['daily'], $r['from'], $r['to']);
    $posts = \App\Support\Stats::buckets($x['posts_daily'] ?? [], $r['from'], $r['to']);
    $comments = \App\Support\Stats::buckets($x['comments_daily'] ?? [], $r['from'], $r['to']);
    $max = max(1, max($series ?: [1]));
    $weekly = count($series) !== $r['days'];
@endphp
<section class="card">
    <h2>{{ __('Visits') }} @if($weekly)<small class="muted">({{ __('weekly totals') }})</small>@endif</h2>
    <div class="bars" role="img" aria-label="{{ __('Visits per day') }}">
        @foreach ($series as $day => $n)<span class="bar" style="--h: {{ round($n / $max * 100) }}" title="{{ $day }}: {{ $n }}"></span>@endforeach
    </div>
    @if(array_sum($posts) || array_sum($comments))
        @php $m2 = max(1, max($posts ?: [0]), max($comments ?: [0])); @endphp
        <p class="muted" style="margin:.8rem 0 .2rem">{{ __('Published posts') }}: {{ array_sum($posts) }} · {{ __('Comments') }}: {{ array_sum($comments) }}</p>
        <div class="mini" aria-hidden="true">
            @foreach ($series as $day => $n)<span style="--h: {{ round(($posts[$day] ?? 0) / $m2 * 100) }};background:#f59e0b" title="{{ $day }}: {{ $posts[$day] ?? 0 }} {{ __('posts') }}"></span>@endforeach
        </div>
        <div class="mini" aria-hidden="true" style="margin-top:2px">
            @foreach ($series as $day => $n)<span style="--h: {{ round(($comments[$day] ?? 0) / $m2 * 100) }};background:#0ea5e9" title="{{ $day }}: {{ $comments[$day] ?? 0 }} {{ __('comments') }}"></span>@endforeach
        </div>
    @endif
</section>

<section class="card">
    <h2>{{ __('Most visited') }}
        <small><a href="{{ route('admin.stats.export', $q(['type' => 'pages'])) }}">CSV</a></small></h2>
    <table class="table">
        <thead><tr><th>{{ __('What') }}</th><th>{{ __('Type') }}</th><th>{{ __('Views') }}</th></tr></thead>
        <tbody>
        @forelse ($s['top'] as $t)
            @php $kind = match (true) { str_starts_with($t->view_key, 'c:') => __('Content'), str_starts_with($t->view_key, 'cat:') => __('Category'), str_starts_with($t->view_key, 'tag:') => __('Tag'), default => __('Page') }; @endphp
            <tr><td>{{ $t->label ?: $t->view_key }}</td><td>{{ $kind }}</td><td>{{ $fmt($t->total) }}</td></tr>
        @empty
            <tr><td colspan="3" class="muted center">{{ __('No data yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@if(! empty($d))
<div class="stats">
    <div class="stat"><span class="num">{{ $fmt($d['uniq_today']) }}</span>{{ __('Unique visitors today') }}</div>
    <div class="stat"><span class="num">{{ $fmt($d['uniq_range']) }}</span>{{ __('Unique visitors in range') }}</div>
    <div class="stat"><span class="num">{{ $fmt($d['ips_range']) }}</span>{{ __('Distinct IP addresses') }}</div>
    @if(! empty($d['returning']))
        <div class="stat"><span class="num">{{ $fmt($d['returning']['new']) }}</span>{{ __('New visitors') }}</div>
        <div class="stat"><span class="num">{{ $fmt($d['returning']['returning']) }}</span>{{ __('Returning visitors') }}</div>
    @endif
</div>

@if($ext)
<div class="stats-grid">
    <section class="card">
        <h2>{{ __('Traffic sources') }} <small><a href="{{ route('admin.stats.export', $q(['type' => 'sources'])) }}">CSV</a></small></h2>
        <table class="table"><tbody>
        @forelse($d['source_types'] as $row)<tr><td>{{ $srcNames[$row->name] ?? $row->name }}</td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Sources by name') }}</h2>
        <table class="table"><tbody>
        @forelse($d['source_names'] as $row)<tr><td>{{ $row->name }} <small class="muted">{{ $srcNames[$row->source] ?? $row->source }}</small></td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('UTM campaigns') }}</h2>
        <table class="table"><tbody>
        @forelse($d['campaigns'] as $row)<tr><td>{{ $row->utm_campaign ?: '—' }} <small class="muted">{{ $row->utm_source }}{{ $row->utm_medium ? ' / '.$row->utm_medium : '' }}</small></td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No tagged links yet. Add ?utm_source=…&utm_medium=…&utm_campaign=… to a link.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
</div>

<div class="stats-grid">
    <section class="card">
        <h2>{{ __('Peak hours') }}</h2>
        @php $hm = max(1, max($d['peaks']['hours'])); @endphp
        <div class="mini" role="img" aria-label="{{ __('Visits by hour') }}">@foreach($d['peaks']['hours'] as $h => $n)<span style="--h: {{ round($n / $hm * 100) }}" title="{{ sprintf('%02d:00', $h) }}: {{ $n }}"></span>@endforeach</div>
        <div class="mini-labels">@foreach($d['peaks']['hours'] as $h => $n)<span>{{ $h % 3 === 0 ? $h : '' }}</span>@endforeach</div>
    </section>
    <section class="card">
        <h2>{{ __('Busiest weekdays') }}</h2>
        @php $dm = max(1, max($d['peaks']['days'])); @endphp
        <div class="mini" role="img" aria-label="{{ __('Visits by weekday') }}">@foreach($d['peaks']['days'] as $n => $c)<span style="--h: {{ round($c / $dm * 100) }}" title="{{ $dayNames[$n] }}: {{ $c }}"></span>@endforeach</div>
        <div class="mini-labels">@foreach($d['peaks']['days'] as $n => $c)<span>{{ $dayNames[$n] }}</span>@endforeach</div>
    </section>
    <section class="card">
        <h2>{{ __('Entry pages') }}</h2>
        <table class="table"><tbody>
        @forelse(array_slice($d['paths']['entry'], 0, 8, true) as $p => $n)<tr><td>{{ $p }}</td><td style="text-align:right">{{ $fmt($n) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Exit pages') }}</h2>
        <table class="table"><tbody>
        @forelse(array_slice($d['paths']['exit'], 0, 8, true) as $p => $n)<tr><td>{{ $p }}</td><td style="text-align:right">{{ $fmt($n) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
</div>

<div class="stats-grid">
    <section class="card">
        <h2>{{ __('Most read by category') }}</h2>
        <table class="table"><tbody>
        @forelse($x['by_category'] as $row)<tr><td>{{ $row->name }}</td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Most read by author') }}</h2>
        <table class="table"><tbody>
        @forelse($x['by_author'] as $row)<tr><td>{{ $row->name }}</td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Search terms') }} <small><a href="{{ route('admin.stats.export', $q(['type' => 'search'])) }}">CSV</a></small></h2>
        <table class="table"><thead><tr><th>{{ __('Term') }}</th><th>{{ __('Searches') }}</th></tr></thead><tbody>
        @forelse($x['terms'] as $row)<tr><td>{{ $row->term }}</td><td>{{ $fmt($row->searches) }}</td></tr>
        @empty<tr><td colspan="2" class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Searches with no results') }}</h2>
        <table class="table"><thead><tr><th>{{ __('Term') }}</th><th>{{ __('Times') }}</th></tr></thead><tbody>
        @forelse($x['zero_terms'] as $row)<tr><td>{{ $row->term }}</td><td>{{ $fmt($row->zero) }}</td></tr>
        @empty<tr><td colspan="2" class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Tracked clicks') }} <small><a href="{{ route('admin.stats.export', $q(['type' => 'clicks'])) }}">CSV</a></small></h2>
        <table class="table"><tbody>
        @forelse($x['clicks'] as $row)<tr><td>{{ $row->label }} <small class="muted">{{ $row->kind }}</small></td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ __('No clicks yet. Add data-track="Name" to any link or button, or use mailto:, tel:, download and outbound links.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="card">
        <h2>{{ __('Time on page') }}</h2>
        <table class="table"><thead><tr><th>{{ __('Page') }}</th><th>{{ __('Time') }}</th><th>{{ __('Scroll') }}</th></tr></thead><tbody>
        @forelse($d['engage']['pages'] as $row)<tr><td>{{ $row->label }}</td><td>{{ $dur((int) round($row->avg_seconds)) }}</td><td>{{ (int) round($row->avg_scroll) }}%</td></tr>
        @empty<tr><td colspan="3" class="muted center">{{ __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
</div>

<section class="card">
    <h2>{{ __('Bots and crawlers') }}</h2>
    <p class="muted">{{ __('Humans') }}: {{ $fmt($d['human_total']) }} · {{ __('Bots') }}: {{ $fmt($d['bot_total']) }}. {{ __('Bots are logged separately and never counted in the visitor numbers above.') }}</p>
    <table class="table"><thead><tr><th>{{ __('Bot') }}</th><th>{{ __('Requests') }}</th><th>{{ __('Last seen') }}</th></tr></thead><tbody>
    @forelse($d['bots'] as $row)<tr><td>{{ $row->name }}</td><td>{{ $fmt($row->total) }}</td><td>{{ $row->last_seen }}</td></tr>
    @empty<tr><td colspan="3" class="muted center">{{ __('No bot visits logged. Turn on "Log bots separately" in Settings → Statistics.') }}</td></tr>@endforelse
    </tbody></table>
</section>
@endif

@php
    $blocks = [
        __('Referrers') => [$d['referrers'], null],
        __('Browsers') => [$d['browsers'], null],
        __('Operating systems') => [$d['systems'], null],
        __('Devices') => [$d['devices'], $devNames],
        __('Countries') => [$d['countries'], null],
    ];
    if ($ext) { $blocks[__('Cities')] = [$d['cities'], null]; }
    $blocks[__('Browser languages')] = [$d['langs'], null];
@endphp
<div class="stats-grid">
@foreach($blocks as $title => [$rows, $map])
    <section class="card">
        <h2>{{ $title }}</h2>
        <table class="table"><tbody>
        @forelse($rows as $row)<tr><td>{{ $map[$row->name] ?? $row->name }}</td><td style="text-align:right">{{ $fmt($row->total) }}</td></tr>
        @empty<tr><td class="muted center">{{ $title === __('Cities') ? __('No data. City needs a CDN header (Cloudflare "IP Geolocation" + managed transform).') : __('No data yet.') }}</td></tr>@endforelse
        </tbody></table>
    </section>
@endforeach
</div>

<section class="card">
    <h2>{{ __('Most active IP addresses') }}</h2>
    <table class="table">
        <thead><tr><th>IP</th><th>{{ __('Country') }}</th><th>{{ __('Visits') }}</th><th>{{ __('Last seen') }}</th></tr></thead>
        <tbody>
        @forelse($d['ips'] as $row)
            <tr><td><a href="{{ route('admin.stats.index', $q(['ip' => $row->ip])) }}#recent">{{ $row->ip }}</a></td><td>{{ $row->country ?: '—' }}</td><td>{{ $row->total }}</td><td>{{ $row->last_seen }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted center">{{ __('No data yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card" id="recent">
    <h2>{{ __('Latest visits') }}@if($d['filter']) — {{ $d['filter'] }} <a href="{{ route('admin.stats.index', $q()) }}" class="muted">({{ __('show all') }})</a>@endif
        @if(auth()->user()->isAdmin())<small><a href="{{ route('admin.stats.export', $q(['type' => 'visits'])) }}">CSV</a></small>@endif</h2>
    <form method="get" style="margin-bottom:.8rem">
        <input type="hidden" name="range" value="{{ $r['preset'] }}"><input type="hidden" name="from" value="{{ $r['from'] }}"><input type="hidden" name="to" value="{{ $r['to'] }}">
        <input type="text" name="ip" value="{{ $d['filter'] }}" placeholder="{{ __('Filter by IP address') }}" maxlength="45"> <button class="btn" type="submit">{{ __('Search') }}</button>
    </form>
    <div style="overflow-x:auto">
    <table class="table">
        <thead><tr><th>{{ __('Time') }}</th><th>IP</th><th>{{ __('Page') }}</th><th>{{ __('Source') }}</th><th>{{ __('Browser') }}</th><th>{{ __('System') }}</th><th>{{ __('Device') }}</th><th>{{ __('Country') }}</th></tr></thead>
        <tbody>
        @forelse($d['recent'] as $v)
            <tr title="{{ $v->user_agent }}">
                <td>{{ $v->created_at }}</td>
                <td>@if($v->ip)<a href="{{ route('admin.stats.index', $q(['ip' => $v->ip])) }}#recent">{{ $v->ip }}</a>@else — @endif</td>
                <td>{{ $v->label ?: $v->path }}<br><small class="muted">{{ $v->path }}</small></td>
                <td>{{ $v->ref_host ?: '—' }}</td>
                <td>{{ $v->browser }}@if($v->is_bot) <small class="muted">({{ $v->bot ?? 'bot' }})</small>@endif</td>
                <td>{{ $v->os }}</td>
                <td>{{ $devNames[$v->device] ?? $v->device }}</td>
                <td>{{ $v->country ?: '—' }}@if(! empty($v->city)) <small class="muted">{{ $v->city }}</small>@endif @if($v->lang)<small class="muted">/ {{ $v->lang }}</small>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted center">{{ __('No data yet.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@if(! $ext)
<div class="flash err">{{ __('Extended statistics need a database update.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Update the database') }}</a></div>
@endif
@elseif(\App\Support\Options::on('stats_log'))
<div class="flash err">{{ __('The detailed log has not been created yet.') }} <a href="{{ route('admin.system.migrations') }}">{{ __('Update the database') }}</a></div>
@endif
@endif
@endsection
