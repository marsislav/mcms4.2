@php
    $D = \App\Support\Discussion::class;
    $threaded = $D::on('threaded');
    $max = max(2, min(5, (int) $D::get('thread_depth')));
    $rows = \App\Models\Comment::where('content_id', $content->id)->where('status', 'approved')->orderBy('created_at')->orderBy('id')->get();
    $total = $rows->count();
    $tree = $D::tree($rows, $threaded, $max);
    if ($D::get('order') === 'desc') { $tree = array_reverse($tree); }
    $per = (int) $D::get('per_page');
    $pages = $per > 0 ? (int) ceil(count($tree) / $per) : 1;
    $page = min(max(1, (int) request('cpage', 1)), max(1, $pages));
    if ($per > 0) { $tree = array_slice($tree, ($page - 1) * $per, $per); }
    $open = $D::isOpen($content);
    $replyTo = ($open && $threaded) ? $rows->firstWhere('id', (int) request('reply')) : null;
    $ts = \Illuminate\Support\Facades\Crypt::encryptString((string) time());
    $me = auth()->user();
@endphp
<section id="comments" class="comments">
    <h2>{{ $total ? t('comments.n', __('Коментари: :n'), ['n' => $total]) : t('comments.title', __('Коментари')) }}</h2>

    @if(session('comment_ok'))<div class="notice ok" role="status">{{ session('comment_ok') }}</div>@endif

    @if($tree)
        <ol class="comment-list">
            @foreach ($tree as $node)
                @include('site.partials.comment', ['node' => $node, 'open' => $open, 'threaded' => $threaded, 'max' => $max])
            @endforeach
        </ol>
        @if($pages > 1)
            <nav class="pager" aria-label="{{ __(':p1', ['p1' => t('comments.title', __('Коментари'))]) }}">
                @if($page > 1)<a href="{{ request()->fullUrlWithQuery(['cpage' => $page - 1, 'reply' => null]) }}#comments">← {{ t('comments.prev', __('По-стари коментари')) }}</a>@else<span class="disabled"></span>@endif
                <span class="muted">{{ $page }} / {{ $pages }}</span>
                @if($page < $pages)<a href="{{ request()->fullUrlWithQuery(['cpage' => $page + 1, 'reply' => null]) }}#comments">{{ t('comments.next', __('По-нови коментари')) }} →</a>@else<span class="disabled"></span>@endif
            </nav>
        @endif
    @elseif($open)
        <p class="muted">{{ t('comments.none', __('Все още няма коментари.')) }}</p>
    @endif

    @if($open)
        <div id="respond" class="respond">
            <h3>{{ $replyTo ? t('comments.reply_to', __('Отговор до :name'), ['name' => $replyTo->author_name]) : t('comments.leave', __('Остави коментар')) }}
                @if($replyTo)<small><a href="{{ request()->fullUrlWithQuery(['reply' => null]) }}#respond" rel="nofollow">{{ t('comments.cancel', __('Откажи отговора')) }}</a></small>@endif
            </h3>
            @if(session('comment_error') || $errors->any())
                <div class="notice err" role="alert">{{ session('comment_error') ?: t('comments.error', __('Моля, проверете полетата и опитайте отново.')) }}</div>
            @endif
            <form class="comment-form" method="post" action="{{ lroute('comment') }}">
                @csrf
                <input type="hidden" name="content_id" value="{{ $content->id }}">
                <input type="hidden" name="parent_id" value="{{ $replyTo?->id }}">
                <input type="hidden" name="ts" value="{{ $ts }}">
                <div class="hp" aria-hidden="true"><label>Website <input type="text" name="hp_site" tabindex="-1" autocomplete="off"></label></div>

                <label>{{ t('comments.text', __('Коментар')) }}
                    <textarea name="comment" rows="6" required minlength="2" maxlength="5000">{{ old('comment') }}</textarea>
                </label>
                <div class="comment-fields">
                    <label>{{ t('comments.name', __('Име')) }}@if(\App\Support\Discussion::on('require_name_email')) *@endif
                        <input name="author" value="{{ old('author', $me?->name) }}" maxlength="100" autocomplete="name" @required(\App\Support\Discussion::on('require_name_email'))>
                    </label>
                    <label>{{ t('comments.email', __('Имейл (няма да бъде публикуван)')) }}@if(\App\Support\Discussion::on('require_name_email')) *@endif
                        <input type="email" name="email" value="{{ old('email', $me?->email) }}" maxlength="190" autocomplete="email" @required(\App\Support\Discussion::on('require_name_email'))>
                    </label>
                    @if(\App\Support\Discussion::on('allow_url'))
                    <label>{{ t('comments.url', __('Уебсайт')) }}
                        <input type="url" name="url" value="{{ old('url') }}" maxlength="200" autocomplete="url" placeholder="https://">
                    </label>
                    @endif
                </div>
                <button class="btn">{{ t('comments.submit', __('Публикувай коментара')) }}</button>
            </form>
        </div>
    @else
        <p class="muted">{{ t('comments.closed', __('Коментарите са затворени.')) }}</p>
    @endif
</section>
