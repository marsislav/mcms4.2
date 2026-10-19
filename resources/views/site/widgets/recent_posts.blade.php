@php
    $posts = \App\Models\Content::with(['translations', 'featured'])->where('type', 'post')->visible()
        ->orderByDesc('publish_at')->orderByDesc('id')->limit((int) ($w['count'] ?? 5))->get();
@endphp
@if($posts->isNotEmpty())
<ul class="widget-list widget-posts">
    @foreach ($posts as $p)
        @if($pt = $p->tr())
            @php $pt->setRelation('content', $p); @endphp
            <li>
                @if(! empty($w['show_thumb']) && $p->featured)<a class="wp-thumb" href="{{ $pt->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $p->featured->thumbUrl() }}" alt="" width="56" height="56" loading="lazy"></a>@endif
                <span class="wp-text"><a href="{{ $pt->url() }}">{{ $pt->title }}</a>
                @if(! empty($w['show_date']))<small class="muted">{{ mcms_date(($p->publish_at ?? $p->created_at)) }}</small>@endif</span>
            </li>
        @endif
    @endforeach
</ul>
@endif
