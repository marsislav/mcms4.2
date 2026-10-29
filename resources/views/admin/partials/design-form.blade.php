@php
    $v = fn ($k) => old($k, $design[$k] ?? '');
    $color = function ($name, $label, $hint = null) use ($v) {
        $val = $v($name);
        return '<label>'.e($label).($hint ? ' <small class="muted">'.e($hint).'</small>' : '').'<span class="colorfield">'
            .'<input type="color" data-color-pick value="'.e($val ?: '#888888').'" aria-label="'.e($label).'">'
            .'<input type="text" name="'.$name.'" data-color-text value="'.e($val).'" maxlength="7" placeholder="'.__('по подразбиране').'" pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" autocomplete="off">'
            .'<button type="button" class="btn sm" data-color-clear title="'.__('Върни по подразбиране').'">✕</button></span></label>';
    };
    $fontSel = function ($name, $label) use ($v) {
        $o = '';
        foreach (\App\Support\Design::FONTS as $k => $f) {
            $o .= '<option value="'.e($k).'"'.($v($name) === (string) $k ? ' selected' : '').'>'.e(__($f[0])).'</option>';
        }
        foreach (\App\Support\Design::customFonts() as $slot => $cf) {
            $o .= '<option value="f'.$slot.'"'.($v($name) === 'f'.$slot ? ' selected' : '').'>'.e($cf['name']).' ('.e(__('мой шрифт')).')</option>';
        }
        return '<label>'.e($label).'<select name="'.$name.'">'.$o.'</select></label>';
    };
    $animSel = function ($name, $label) use ($v) {
        $o = '';
        foreach (\App\Support\Design::ANIMS as $k => $l) {
            $o .= '<option value="'.e($k).'"'.($v($name) === (string) $k ? ' selected' : '').'>'.e(__($l)).'</option>';
        }
        return '<label>'.e($label).'<select name="'.$name.'">'.$o.'</select></label>';
    };
    $maxCols = \App\Support\Design::MAX_COLS;
@endphp

<form method="post" action="{{ route('admin.settings.design') }}" class="settings-form" enctype="multipart/form-data">
    @csrf @method('PUT')

    <section class="card">
        <h2>{{ __('Цветове') }}</h2>
        <p class="muted">{{ __('Празно поле = цветът на темата. Ако зададете само фон или само текст, другият се подбира автоматично за добър контраст. Зададените цветове важат и при тъмна тема на устройството.') }}</p>
        <div class="grid-form">
            {!! $color('bg', __('Цвят на фона')) !!}
            {!! $color('fg', __('Цвят на текста')) !!}
            {!! $color('heading', __('Цвят на заглавията')) !!}
            {!! $color('accent', __('Акцентен цвят (връзки, бутони)')) !!}
            {!! $color('header_bg', __('Фон на хедъра')) !!}
            {!! $color('footer_bg', __('Фон на футъра')) !!}
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Шрифтове') }}</h2>
        <p class="muted">{{ __('Ползват се системни шрифтове — без външни заявки (бързо, GDPR-съвместимо, съвместимо със CSP).') }}</p>
        <div class="grid-form">
            {!! $fontSel('font_body', __('Основен текст')) !!}
            {!! $fontSel('font_heading', __('Заглавия')) !!}
            {!! $fontSel('font_menu', __('Меню')) !!}
            {!! $fontSel('font_logo', __('Лого (текстово)')) !!}
            <label>{!! __('Базов размер на текста (px) <small class="muted">14–22, празно = 16</small>') !!}
                <input type="number" name="font_size" min="14" max="22" value="{{ $v('font_size') }}"></label>
        </div>

        <h3>{{ __('Свои шрифтове') }}</h3>
        <p class="muted">{!! __('Качи шрифт във формат <code>.woff2</code> (или <code>.woff</code>), даваш му име и после го избираш в списъците по-горе. Файловете се зареждат от твоя сървър — без Google Fonts. Готови WOFF2 файлове: google-webfonts-helper.herokuapp.com или fonts.google.com → изтегли → конвертирай TTF в WOFF2.') !!}</p>
        @error('fonts')<p class="error">{{ $message }}</p>@enderror
        @for ($i = 1; $i <= \App\Support\Design::CUSTOM_FONTS; $i++)
            @php($cf = \App\Support\Design::customFonts()[$i] ?? null)
            <details class="bg-sub" @if($cf) open @endif>
                <summary>{{ __('Шрифт') }} {{ $i }} @if($cf)— <strong>{{ $cf['name'] }}</strong>@endif</summary>
                <div class="grid-form">
                    <label>{{ __('Име (както ще се вижда в списъка)') }} <input type="text" name="fonts[{{ $i }}][name]" maxlength="40" value="{{ old("fonts.$i.name", $cf['name'] ?? '') }}" autocomplete="off"></label>
                    <label>{{ __('Обикновен (400) — .woff2 / .woff') }} @if($cf)<small class="muted">✓ {{ basename($cf['regular']) }}</small>@endif
                        <input type="file" name="fonts[{{ $i }}][regular]" accept=".woff2,.woff"></label>
                    <label>{{ __('Удебелен (700) — по желание') }} @if($cf && $cf['bold'] !== '')<small class="muted">✓ {{ basename($cf['bold']) }}</small>@endif
                        <input type="file" name="fonts[{{ $i }}][bold]" accept=".woff2,.woff"></label>
                </div>
                @if($cf)<label class="check"><input type="checkbox" name="fonts[{{ $i }}][remove]" value="1"> {{ __('Премахни този шрифт') }}</label>@endif
            </details>
        @endfor
    </section>

    <section class="card">
        <h2>{{ __('Лого и хедър') }}</h2>
        <div class="grid-form">
            <label>{!! __('Височина на логото <small class="muted">„auto“ или 20–300 (px)</small>') !!}
                <input name="logo_size" value="{{ $v('logo_size') }}" maxlength="4" required list="logo-sizes" autocomplete="off">
                <datalist id="logo-sizes"><option value="auto"><option value="32"><option value="40"><option value="56"><option value="80"><option value="120"></datalist>
            </label>
        </div>
        <label class="check"><input type="checkbox" name="sticky_header" value="1" @checked($v('sticky_header') === '1')> {{ __('Залепен хедър (sticky) — остава горе при скролиране') }}</label>
    </section>

    <section class="card">
        <h2>{{ __('Анимация на страницата') }}</h2>
        <div class="grid-form">
            {!! $animSel('anim_page_type', __('Тип анимация при зареждане')) !!}
            <label>{!! __('Скорост (ms) <small class="muted">150–2000</small>') !!}
                <input type="number" name="anim_speed" min="150" max="2000" step="50" value="{{ $v('anim_speed') }}" required></label>
        </div>
        <label class="check"><input type="checkbox" name="anim_page_on" value="1" @checked($v('anim_page_on') === '1')> {!! __('Анимацията на страницата е включена <small class="muted">(махнете отметката, за да я спрете, без да губите избора)</small>') !!}</label>
        <label class="check"><input type="checkbox" name="anim_pause_btn" value="1" @checked($v('anim_pause_btn') === '1')> {{ __('Покажи на посетителите бутон „Спри анимациите“ във футъра') }}</label>
        <p class="muted">{{ __('Посетители, които са включили „намалено движение“ в устройството си, не виждат анимации независимо от настройките.') }}</p>
    </section>

    <section class="card">
        <h2>{!! __('Анимации на елементи <small class="muted">(при скролиране до елемента)</small>') !!}</h2>
        <label class="check"><input type="checkbox" name="anim_el_on" value="1" @checked($v('anim_el_on') === '1')> {!! __('Анимациите на елементите са включени <small class="muted">(главен ключ — спира всички отделни анимации)</small>') !!}</label>
        <div class="grid-form">
            @foreach (\App\Support\Design::ELEMENTS as $k => $label)
                {!! $animSel('el_'.$k, __($label)) !!}
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Top-bar (лента над хедъра)') }}</h2>
        <label class="check"><input type="checkbox" name="topbar_on" value="1" data-topbar-on @checked($v('topbar_on') === '1')> {{ __('Top-bar е включен') }}</label>
        <div data-topbar-box>
            <div class="grid-form">
                <label>{{ __('Брой колони') }}
                    <select name="topbar_cols" data-topbar-cols>
                        @for ($i = 1; $i <= $maxCols; $i++)<option value="{{ $i }}" @selected((int) $v('topbar_cols') === $i)>{{ $i }}</option>@endfor
                    </select>
                </label>
                {!! $color('topbar_bg', __('Фон на top-bar')) !!}
                {!! $color('topbar_fg', __('Цвят на текста')) !!}
            </div>
            <label class="check"><input type="checkbox" name="topbar_mobile" value="1" @checked($v('topbar_mobile') === '1')> {{ __('Показвай и на телефон (колоните се подреждат една под друга)') }}</label>

            @for ($i = 1; $i <= $maxCols; $i++)
                <fieldset class="tb-fieldset" data-topbar-col="{{ $i }}">
                    <legend>{{ __('Колона') }} {{ $i }}</legend>
                    <label>{{ __('Подравняване') }}
                        <select name="topbar_c{{ $i }}_align">
                            @foreach (['left' => __('Вляво'), 'center' => __('Център'), 'right' => __('Вдясно')] as $a => $al)
                                <option value="{{ $a }}" @selected($v('topbar_c'.$i.'_align') === $a)>{{ $al }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid-form">
                        @foreach ($languages as $l)
                            <label>{{ __('Съдържание (:p1)', ['p1' => strtoupper($l->code)]) }}
                                <textarea name="topbar[c{{ $i }}][{{ $l->code }}]" rows="3" maxlength="2000" placeholder="{{ __('Текст или HTML: &lt;a href=&quot;tel:+359…&quot;&gt;…&lt;/a&gt;') }}">{{ old("topbar.c$i.$l->code", \App\Models\Setting::get("design.topbar_c$i.$l->code")) }}</textarea>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endfor
            <p class="muted">{!! __('Разрешени са връзки, удебеляване, курсив и икони на Font Awesome, напр. <code>&lt;span class="fa-solid fa-phone" aria-hidden="true"&gt;&lt;/span&gt; &lt;a href="tel:+359888000000"&gt;0888 000 000&lt;/a&gt;</code>. Празни колони не се показват.') !!}</p>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Лента за прогрес на четенето') }}</h2>
        <label class="check"><input type="checkbox" name="progress_on" value="1" @checked($v('progress_on') === '1')> {{ __('Показвай лента за прогрес при скролиране') }}</label>
        <div class="grid-form">
            {!! $color('progress_color', __('Цвят на лентата'), __('празно = акцентният цвят')) !!}
            {!! $color('progress_bg', __('Фон на пистата')) !!}
            <label>{{ __('Дебелина (px)') }} <input type="number" name="progress_height" min="1" max="12" value="{{ $v('progress_height') }}" required></label>
            <label>{{ __('Позиция') }}
                <select name="progress_pos">@foreach (\App\Support\Design::PROGRESS_POS as $k => $l)<option value="{{ $k }}" @selected($v('progress_pos') === $k)>{{ __($l) }}</option>@endforeach</select>
            </label>
            <label>{{ __('Къде се показва') }}
                <select name="progress_scope">@foreach (\App\Support\Design::PROGRESS_SCOPE as $k => $l)<option value="{{ $k }}" @selected($v('progress_scope') === $k)>{{ __($l) }}</option>@endforeach</select>
            </label>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Размазване най-долу (progressive blur)') }}</h2>
        <label class="check"><input type="checkbox" name="blur_on" value="1" @checked($v('blur_on') === '1')> {{ __('Показвай постепенно размазване най-долу на екрана') }}</label>
        <div class="grid-form">
            <label>{{ __('Височина (% от екрана)') }} <input type="number" name="blur_height" min="5" max="50" value="{{ $v('blur_height') }}" required></label>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Плавен скрол') }}</h2>
        <label class="check"><input type="checkbox" name="smooth_on" value="1" @checked($v('smooth_on') === '1')> {{ __('Включи плавен скрол с колелцето (Lenis)') }}</label>
        <div class="grid-form">
            <label>{{ __('Продължителност (сек.)') }} <input type="number" name="smooth_duration" min="0.4" max="3" step="0.1" value="{{ $v('smooth_duration') }}" required></label>
        </div>
    </section>

    <section class="card">
        <h2>{{ __('Анимиран шум (grain)') }}</h2>
        <label class="check"><input type="checkbox" name="grain_on" value="1" @checked($v('grain_on') === '1')> {{ __('Показвай анимиран шум върху целия сайт') }}</label>
        <div class="grid-form">
            {!! $color('grain_color', __('Цвят на точките'), __('празно = бяло')) !!}
            <label>{{ __('Плътност на шума (%)') }} <input type="number" name="grain_density" min="5" max="100" value="{{ $v('grain_density') }}" required></label>
            <label>{{ __('Видимост (%)') }} <input type="number" name="grain_opacity" min="1" max="40" value="{{ $v('grain_opacity') }}" required></label>
        </div>
        <small class="muted">{{ __('Високата плътност натоварва слабите устройства. Изключва се при „намалено движение“.') }}</small>
    </section>

    <section class="card" id="gdpr-look">
        <h2>{{ __('Банер за бисквитки (GDPR) — външен вид') }}</h2>
        <p class="muted">{{ __('Текстовете, позицията и категориите се задават в Настройки → Бисквитки (GDPR). Тук е само видът. Фон (цвят, градиент, картинка) и цвят на текста: „Фонове на елементи“ → „Банер за бисквитки“.') }}</p>
        <div class="grid-form">
            {!! $color('gd_btn_bg', __('Основен бутон — фон')) !!}
            {!! $color('gd_btn_fg', __('Основен бутон — текст')) !!}
            {!! $color('gd_btn2_bg', __('Втори бутон — фон')) !!}
            {!! $color('gd_btn2_fg', __('Втори бутон — текст и рамка')) !!}
            {!! $color('gd_link', __('Цвят на връзките')) !!}
            {!! $color('gd_reopen_bg', __('Малкият бутон 🍪 — фон')) !!}
            <label>{{ __('Закръгление (px)') }} <input type="number" name="gd_radius" min="0" max="40" value="{{ $v('gd_radius') }}" placeholder="{{ __('по подразбиране') }}"></label>
            <label>{{ __('Ширина на кутията (px)') }} <input type="number" name="gd_width" min="280" max="900" value="{{ $v('gd_width') }}" placeholder="{{ __('по подразбиране') }}"></label>
            <label>{{ __('Размер на текста (px)') }} <input type="number" name="gd_font" min="12" max="20" value="{{ $v('gd_font') }}" placeholder="{{ __('по подразбиране') }}"></label>
        </div>
        <label class="check"><input type="checkbox" name="gd_btn2_ghost" value="1" @checked($v('gd_btn2_ghost') === '1')> {{ __('Вторият бутон без фон (само рамка)') }}</label>
        <label class="check"><input type="checkbox" name="gd_glass" value="1" @checked($v('gd_glass') === '1')> {{ __('Стъклен ефект (полупрозрачен фон с размазване)') }}</label>
        <label class="check"><input type="checkbox" name="gd_dim" value="1" @checked($v('gd_dim') === '1')> {{ __('Затъмнявай сайта зад банера (посетителят трябва да избере)') }}</label>
        <small class="muted">{{ __('За да видиш банера след избор, изтрий бисквитката mcms_consent или включи малкия бутон 🍪 в Настройки → Бисквитки.') }}</small>
    </section>

    @include('admin.partials.design-onepage')
    @include('admin.partials.design-effects')

    @include('admin.partials.design-backgrounds')

    <div class="btn-row">
        <button class="btn primary">{{ __('Запази дизайна') }}</button>
        {!! __('<a class="btn" href=":p1" target="_blank" rel="noopener">Виж сайта ↗</a>', ['p1' => e(url('/'))]) !!}
    </div>
</form>

<form method="post" action="{{ route('admin.settings.design-reset') }}" class="inline-form" data-confirm="{{ __('Да върна ли целия дизайн към подразбиращите се стойности?') }}">
    @csrf <button class="btn danger">{{ __('Върни дизайна по подразбиране') }}</button>
</form>
