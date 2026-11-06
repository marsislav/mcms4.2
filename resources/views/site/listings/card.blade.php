<article class="card ls-card">
    @if(! empty($thumb))
        <a href="{{ $l->url() }}" tabindex="-1" aria-hidden="true"><img src="{{ $thumb->thumbUrl() }}" alt="" loading="lazy"></a>
    @endif
    <div class="card-body">
        @if($l->category)<a class="kicker" href="{{ lroute('listings') }}?cat={{ $l->category->slug }}">{{ $l->category->name }}</a>@endif
        <p class="ls-price">{{ $l->priceLabel() }}</p>
        <h2><a href="{{ $l->url() }}">{{ $l->title }}</a></h2>
        <p class="meta">@if($l->city){{ $l->city }} · @endif<time datetime="{{ ($l->published_at ?? $l->created_at)->toAtomString() }}">{{ mcms_date($l->published_at ?? $l->created_at) }}</time></p>
        <p>{{ excerpt($l->body, 120) }}</p>
        <a class="more" href="{{ $l->url() }}">{{ t('listings.view', 'Виж обявата') }} →</a>
    </div>
</article>
