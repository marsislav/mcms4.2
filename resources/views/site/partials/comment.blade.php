@php
    $c = $node['c'];
    $av = \App\Support\Discussion::avatar($c->author_email, 56);
@endphp
<li id="comment-{{ $c->id }}" class="comment depth-{{ $node['depth'] }}">
    <article>
        <header class="comment-head">
            @if($av)<img class="avatar" src="{{ $av }}" alt="" width="40" height="40" loading="lazy" referrerpolicy="no-referrer">
            @else<span class="avatar initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($c->author_name, 0, 1)) }}</span>@endif
            <div>
                <strong class="comment-author">
                    @if($c->author_url)<a href="{{ $c->author_url }}" rel="nofollow ugc noopener noreferrer" target="_blank">{{ $c->author_name }}</a>@else {{ $c->author_name }} @endif
                </strong>
                @if($c->user_id)<span class="badge-staff">{{ t('comments.staff', __('Автор')) }}</span>@endif
                <a class="comment-date muted" href="#comment-{{ $c->id }}"><time datetime="{{ $c->created_at->toAtomString() }}">{{ mcms_date($c->created_at, true) }}</time></a>
            </div>
        </header>
        <div class="comment-body">{!! \App\Support\Discussion::format($c->body) !!}</div>
        @if($open && $threaded && $node['depth'] < $max)
            <p class="comment-reply"><a href="{{ request()->fullUrlWithQuery(['reply' => $c->id]) }}#respond" rel="nofollow">{{ t('comments.reply', __('Отговор')) }}</a></p>
        @endif
    </article>
    @if(! empty($node['children']))
        <ol class="children">
            @foreach ($node['children'] as $child)
                @include('site.partials.comment', ['node' => $child, 'open' => $open, 'threaded' => $threaded, 'max' => $max])
            @endforeach
        </ol>
    @endif
</li>
