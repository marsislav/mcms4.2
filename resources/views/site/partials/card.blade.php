@php($pt = $post->tr())
@if($pt)
@php($pt->setRelation('content', $post))
<article class="card">
    @if($post->featured)
        <a href="{{ $pt->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $post->featured->thumbUrl() }}" alt="" loading="lazy" @if($post->featured->width) width="{{ min($post->featured->width, 480) }}" @endif></a>
    @endif
    <div class="card-body">
        @if($post->category && ($ct = $post->category->tr()))<a class="kicker" href="{{ $ct->url() }}">{{ $ct->name }}</a>@endif
        <h2><a href="{{ $pt->url() }}">{{ $pt->title }}</a></h2>
        <p class="meta"><time datetime="{{ ($post->publish_at ?? $post->created_at)->toAtomString() }}">{{ ($post->publish_at ?? $post->created_at)->translatedFormat('j F Y') }}</time></p>
        <p>{{ $pt->excerpt ?: excerpt($pt->body, 160) }}</p>
        <a class="more" href="{{ $pt->url() }}">{{ t('site.read_more') }} →</a>
    </div>
</article>
@endif
