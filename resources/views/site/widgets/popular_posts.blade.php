@php
    $days = ($w['days'] ?? -1) < 0 ? null : (int) $w['days'];
    $ranked = \App\Support\Stats::popularIds((int) ($w['count'] ?? 5), $days);
    $posts = $ranked ? \App\Models\Content::with(['translations', 'featured'])->where('type', 'post')->visible()->whereIn('id', array_keys($ranked))->get()
        ->sortByDesc(fn ($p) => $ranked[$p->id] ?? 0)->take((int) ($w['count'] ?? 5)) : collect();
@endphp
@if($posts->isNotEmpty())
<ul class="widget-list widget-posts">
    @foreach ($posts as $p)
        @if($pt = $p->tr())
            @php $pt->setRelation('content', $p); @endphp
            <li>
                @if(! empty($w['show_thumb']) && $p->featured)<a class="wp-thumb" href="{{ $pt->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $p->featured->thumbUrl() }}" alt="" width="56" height="56" loading="lazy"></a>@endif
                <span class="wp-text"><a href="{{ $pt->url() }}">{{ $pt->title }}</a>
                @if(! empty($w['show_views']))<small class="muted">{{ number_format((int) $ranked[$p->id], 0, '.', ' ') }} {{ __('прочитания') }}</small>@endif</span>
            </li>
        @endif
    @endforeach
</ul>
@endif
