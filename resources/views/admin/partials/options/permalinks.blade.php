<div class="grid-form">
    <label>{{ __('Адрес на блога') }} <input name="blog_base" value="{{ $opt['blog_base'] }}" maxlength="40" pattern="[a-z0-9][a-z0-9\-]*"></label>
    <label>{{ __('Префикс на категории') }} <input name="category_base" value="{{ $opt['category_base'] }}" maxlength="40" pattern="[a-z0-9][a-z0-9\-]*"></label>
    <label>{{ __('Префикс на тагове') }} <input name="tag_base" value="{{ $opt['tag_base'] }}" maxlength="40" pattern="[a-z0-9][a-z0-9\-]*"></label>
</div>
<p class="muted">{!! __('Пример: <code>:p1/моя-статия</code>, <code>:p2/новини</code>, <code>:p3/php</code>. Само малки латински букви, цифри и тире.', ['p1' => e(url($opt['blog_base'])), 'p2' => e(url($opt['blog_base'].'/'.$opt['category_base'])), 'p3' => e(url($opt['blog_base'].'/'.$opt['tag_base']))]) !!}</p>
<p class="muted">{!! __('Старите адреси (<code>/blog/…</code>, <code>/blog/category/…</code>) се пренасочват автоматично с 301. Менютата и картите на сайта използват новите адреси сами. Адресът на самата статия (slug) се редактира във формата на статията.') !!}</p>
