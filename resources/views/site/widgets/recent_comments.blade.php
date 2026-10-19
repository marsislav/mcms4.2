@if(\App\Support\Discussion::enabled() && \App\Support\Discussion::ready())
@php
    $rows = \App\Models\Comment::approved()->with(['content.translations'])->orderByDesc('id')->limit((int) ($w['count'] ?? 5))->get()
        ->filter(fn ($c) => $c->content && $c->content->type === 'post' && $c->content->isVisible() && $c->content->tr());
@endphp
@if($rows->isNotEmpty())
<ul class="widget-list">
    @foreach ($rows as $c)
        @php $pt = $c->content->tr(); @endphp
        @php $pt->setRelation('content', $c->content); @endphp
        <li>{{ $c->author_name }} — <a href="{{ $pt->url() }}#comment-{{ $c->id }}">{{ $pt->title }}</a></li>
    @endforeach
</ul>
@endif
@endif
