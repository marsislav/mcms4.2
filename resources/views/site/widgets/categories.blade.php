@php
    $cats = \App\Models\Category::with('translations')
        ->withCount(['contents as n' => fn ($q) => $q->where('type', 'post')->visible()])
        ->orderBy('sort')->orderBy('id')->get()
        ->filter(fn ($c) => empty($w['hide_empty']) || $c->n > 0);
@endphp
@if($cats->isNotEmpty())
<ul class="widget-list">
    @foreach ($cats as $c)
        @if($ct = $c->tr() ?? $c->translations->first())
            <li><a href="{{ $ct->url() }}">{{ $ct->name }}</a>@if(! empty($w['show_count'])) <span class="muted">({{ $c->n }})</span>@endif</li>
        @endif
    @endforeach
</ul>
@endif
