<label>{{ __('Началната страница показва') }}
    <select name="front_mode">
        <option value="page" @selected($opt['front_mode'] === 'page')>{{ __('Статична страница (отбелязаната като „начална“; ако няма — последните публикации)') }}</option>
        <option value="posts" @selected($opt['front_mode'] === 'posts')>{{ __('Последните публикации') }}</option>
    </select>
</label>
<div class="grid-form">
    <label>{!! __('Публикации на страница <small class="muted">(в „Общи“)</small>') !!} <input value="{{ setting('posts_per_page', 9) }}" disabled></label>
    <label>{{ __('Записи в RSS') }} <input type="number" name="feed_count" min="1" max="50" value="{{ $opt['feed_count'] }}"></label>
    <label>{{ __('Дължина на автоматичния откъс (знаци)') }} <input type="number" name="excerpt_length" min="50" max="600" value="{{ $opt['excerpt_length'] }}"></label>
</div>
<label class="check"><input type="checkbox" name="feed_full" value="1" @checked($opt['feed_full'] === '1')> {{ __('В RSS — пълен текст (иначе само откъс)') }}</label>
<label class="check"><input type="checkbox" name="noindex" value="1" @checked($opt['noindex'] === '1')> {!! __('<strong>Скрий сайта от търсачките</strong> <small class="muted">(meta noindex + robots.txt „Disallow: /“; търсачките решават сами дали да се съобразят)</small>') !!}</label>
<label class="check"><input type="checkbox" name="show_author" value="1" @checked($opt['show_author'] === '1')> {{ __('Показвай автора на статията (с връзка към страницата му)') }}</label>

<label class="check"><input type="checkbox" name="reading_time" value="1" @checked($opt['reading_time'] === '1')> {{ __('Показвай времето за четене под заглавието') }}</label>
<label class="check"><input type="checkbox" name="show_updated" value="1" @checked($opt['show_updated'] === '1')> {{ __('Показвай датата на последна промяна (ако е поне ден след публикуването)') }}</label>
<label class="check"><input type="checkbox" name="share_buttons" value="1" @checked($opt['share_buttons'] === '1')> {{ __('Бутони „Сподели“ под статията (Facebook, X, LinkedIn, имейл — без външни скриптове)') }}</label>
<label class="check"><input type="checkbox" name="back_to_top" value="1" @checked($opt['back_to_top'] === '1')> {{ __('Бутон „Към началото“ (↑) в ъгъла на сайта') }}</label>
