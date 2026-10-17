@php
    $tags = \App\Models\Tag::with('translations')
        ->withCount(['contents as n' => fn ($q) => $q->where('type', 'post')->visible()])
        ->get()->filter(fn ($t) => $t->n > 0)->sortByDesc('n')->take((int) ($w['limit'] ?? 30))->sortBy(fn ($t) => mb_strtolower($t->display()?->name ?? ''));
    $list = ($w['style'] ?? 'cloud') === 'list';
@endphp
@if($tags->isNotEmpty())
<ul @class(['widget-list' => $list, 'tag-cloud' => ! $list])>
    @foreach ($tags as $t)
        @if($d = $t->display())
            <li><a href="{{ $d->url() }}" rel="tag">{{ $d->name }}</a>@if(! empty($w['show_count'])) <span class="muted">({{ $t->n }})</span>@endif</li>
        @endif
    @endforeach
</ul>
@endif
