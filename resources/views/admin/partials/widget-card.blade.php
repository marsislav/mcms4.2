@php
    $type = $w['type'];
    $name = fn ($f) => "cols[$col][$idx][$f]";
    $titleNow = \App\Support\Widgets::text($w, 'title', mcms_default_locale());
    $checked = fn ($k, $default = false) => ! empty($new) ? (bool) ($w[$k] ?? $default) : ! empty($w[$k]);
@endphp
<details class="widget-card" data-widget @if(! empty($new)) open @endif>
    <summary>
        <span class="wtype">{{ __(\App\Support\Widgets::TYPES[$type] ?? $type) }}</span>
        <span class="wtitle muted">{{ $titleNow }}</span>
        <span class="w-actions">
            <button type="button" class="btn sm" data-w-up title="{{ __('Нагоре') }}" aria-label="{{ __('Нагоре') }}">↑</button>
            <button type="button" class="btn sm" data-w-down title="{{ __('Надолу') }}" aria-label="{{ __('Надолу') }}">↓</button>
            <button type="button" class="btn sm danger" data-w-del title="{{ __('Премахни') }}" aria-label="{{ __('Премахни') }}">✕</button>
        </span>
    </summary>
    <div class="widget-body">
        <input type="hidden" name="{{ $name('type') }}" value="{{ $type }}">
        <input type="hidden" name="{{ $name('id') }}" value="{{ $w['id'] ?? '' }}">

        <div class="grid-form">
            @foreach ($languages as $l)
                <label>{{ __('Заглавие (:p1)', ['p1' => strtoupper($l->code)]) }}
                    <input name="{{ $name('title') }}[{{ $l->code }}]" maxlength="120" value="{{ $w['title'][$l->code] ?? '' }}">
                </label>
            @endforeach
            <label>{!! __('Икона (Font Awesome) <small class="muted">пред заглавието</small> <span class="inline-field">') !!}
                    <input name="{{ $name('icon') }}" data-fa-input maxlength="80" placeholder="fa-solid fa-envelope" value="{{ $w['icon'] ?? '' }}">
                    <button type="button" class="btn sm" data-fa-pick="icon">{{ __('Избери') }}</button>
                </span>
            </label>
        </div>

        @if($type === 'html')
            <p class="muted">{!! __('HTML: параграфи, връзки, списъци, изображения и икони на Font Awesome — <code>&lt;span class="fa-solid fa-phone" aria-hidden="true"&gt;&lt;/span&gt;</code>. Небезопасният код се премахва при запис.') !!}</p>
            @foreach ($languages as $l)
                <label>{{ __('Съдържание (:p1)', ['p1' => strtoupper($l->code)]) }}
                    <textarea name="{{ $name('body') }}[{{ $l->code }}]" rows="6" maxlength="20000" data-w-body>{{ $w['body'][$l->code] ?? '' }}</textarea>
                </label>
            @endforeach
            <button type="button" class="btn sm" data-fa-pick="insert">{{ __('＋ Вмъкни икона в текста') }}</button>
        @elseif($type === 'menu')
            <label>{{ __('Меню') }}
                <select name="{{ $name('menu') }}">
                    @foreach ($menus as $m)<option value="{{ $m->location }}" @selected(($w['menu'] ?? 'footer') === $m->location)>{{ $m->name }} ({{ $m->location }})</option>@endforeach
                </select>
            </label>
            {!! __('<small class="muted">Менютата се редактират в „Менюта“. Има смисъл най-вече менюто за футъра.</small>') !!}
        @elseif($type === 'recent_posts')
            <div class="grid-form">
                <label>{{ __('Брой') }} <input type="number" name="{{ $name('count') }}" min="1" max="20" value="{{ $w['count'] ?? 5 }}"></label>
            </div>
            <label class="check"><input type="checkbox" name="{{ $name('show_date') }}" value="1" @checked($checked('show_date'))> {{ __('Показвай датата') }}</label>
            <label class="check"><input type="checkbox" name="{{ $name('show_thumb') }}" value="1" @checked($checked('show_thumb'))> {{ __('Показвай малка снимка') }}</label>
        @elseif($type === 'categories')
            <label class="check"><input type="checkbox" name="{{ $name('show_count') }}" value="1" @checked($checked('show_count', true))> {{ __('Показвай броя публикации') }}</label>
            <label class="check"><input type="checkbox" name="{{ $name('hide_empty') }}" value="1" @checked($checked('hide_empty', true))> {{ __('Скрий празните категории') }}</label>
        @elseif($type === 'tags')
            <div class="grid-form">
                <label>{{ __('Максимум тагове') }} <input type="number" name="{{ $name('limit') }}" min="1" max="100" value="{{ $w['limit'] ?? 30 }}"></label>
                <label>{{ __('Вид') }}
                    <select name="{{ $name('style') }}">
                        <option value="cloud" @selected(($w['style'] ?? 'cloud') === 'cloud')>{{ __('Облак') }}</option>
                        <option value="list" @selected(($w['style'] ?? '') === 'list')>{{ __('Списък') }}</option>
                    </select>
                </label>
            </div>
            <label class="check"><input type="checkbox" name="{{ $name('show_count') }}" value="1" @checked($checked('show_count', true))> {{ __('Показвай броя публикации') }}</label>
        @elseif($type === 'recent_comments')
            <label>{{ __('Брой') }} <input type="number" name="{{ $name('count') }}" min="1" max="20" value="{{ $w['count'] ?? 5 }}" class="narrow"></label>
            {!! __('<small class="muted">Показва се само ако коментарите са включени (Дискусии).</small>') !!}
        @elseif($type === 'search')
            {!! __('<small class="muted">Поле за търсене в сайта.</small>') !!}
        @elseif($type === 'account')
            {!! __('<small class="muted">Връзки „Регистрация“ (ако е отворена), „Вход“ или „Моят профил“ и „Нова статия“ за влезлите потребители.</small>') !!}
        @elseif($type === 'social')
            {!! __('<small class="muted">Връзки към Facebook, Instagram, LinkedIn и YouTube от „Общи“ → Социални мрежи (с икони).</small>') !!}
        @endif

        <label>{!! __('CSS клас на уиджета <small class="muted">(по избор)</small>') !!}
            <input name="{{ $name('class') }}" maxlength="120" placeholder="{{ __('напр. dark-box') }}" value="{{ $w['class'] ?? '' }}" pattern="[A-Za-z_][A-Za-z0-9_\-]*(\s+[A-Za-z_][A-Za-z0-9_\-]*)*">
        </label>
    </div>
</details>
