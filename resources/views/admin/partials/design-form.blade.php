@php
    $v = fn ($k) => old($k, $design[$k] ?? '');
    $color = function ($name, $label, $hint = null) use ($v) {
        $val = $v($name);
        return '<label>'.e($label).($hint ? ' <small class="muted">'.e($hint).'</small>' : '').'<span class="colorfield">'
            .'<input type="color" data-color-pick value="'.e($val ?: '#888888').'" aria-label="'.e($label).'">'
            .'<input type="text" name="'.$name.'" data-color-text value="'.e($val).'" maxlength="7" placeholder="по подразбиране" pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" autocomplete="off">'
            .'<button type="button" class="btn sm" data-color-clear title="Върни по подразбиране">✕</button></span></label>';
    };
    $fontSel = function ($name, $label) use ($v) {
        $o = '';
        foreach (\App\Support\Design::FONTS as $k => $f) {
            $o .= '<option value="'.e($k).'"'.($v($name) === (string) $k ? ' selected' : '').'>'.e($f[0]).'</option>';
        }
        return '<label>'.e($label).'<select name="'.$name.'">'.$o.'</select></label>';
    };
    $animSel = function ($name, $label) use ($v) {
        $o = '';
        foreach (\App\Support\Design::ANIMS as $k => $l) {
            $o .= '<option value="'.e($k).'"'.($v($name) === (string) $k ? ' selected' : '').'>'.e($l).'</option>';
        }
        return '<label>'.e($label).'<select name="'.$name.'">'.$o.'</select></label>';
    };
    $maxCols = \App\Support\Design::MAX_COLS;
@endphp

<form method="post" action="{{ route('admin.settings.design') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>Цветове</h2>
        <p class="muted">Празно поле = цветът на темата. Ако зададете само фон или само текст, другият се подбира автоматично за добър контраст. Зададените цветове важат и при тъмна тема на устройството.</p>
        <div class="grid-form">
            {!! $color('bg', 'Цвят на фона') !!}
            {!! $color('fg', 'Цвят на текста') !!}
            {!! $color('heading', 'Цвят на заглавията') !!}
            {!! $color('accent', 'Акцентен цвят (връзки, бутони)') !!}
            {!! $color('header_bg', 'Фон на хедъра') !!}
            {!! $color('footer_bg', 'Фон на футъра') !!}
        </div>
    </section>

    <section class="card">
        <h2>Шрифтове</h2>
        <p class="muted">Ползват се системни шрифтове — без външни заявки (бързо, GDPR-съвместимо, съвместимо със CSP).</p>
        <div class="grid-form">
            {!! $fontSel('font_body', 'Основен текст') !!}
            {!! $fontSel('font_heading', 'Заглавия') !!}
            {!! $fontSel('font_menu', 'Меню') !!}
            {!! $fontSel('font_logo', 'Лого (текстово)') !!}
            <label>Базов размер на текста (px) <small class="muted">14–22, празно = 16</small>
                <input type="number" name="font_size" min="14" max="22" value="{{ $v('font_size') }}"></label>
        </div>
    </section>

    <section class="card">
        <h2>Лого и хедър</h2>
        <div class="grid-form">
            <label>Височина на логото <small class="muted">„auto“ или 20–300 (px)</small>
                <input name="logo_size" value="{{ $v('logo_size') }}" maxlength="4" required list="logo-sizes" autocomplete="off">
                <datalist id="logo-sizes"><option value="auto"><option value="32"><option value="40"><option value="56"><option value="80"><option value="120"></datalist>
            </label>
        </div>
        <label class="check"><input type="checkbox" name="sticky_header" value="1" @checked($v('sticky_header') === '1')> Залепен хедър (sticky) — остава горе при скролиране</label>
    </section>

    <section class="card">
        <h2>Анимация на страницата</h2>
        <div class="grid-form">
            {!! $animSel('anim_page_type', 'Тип анимация при зареждане') !!}
            <label>Скорост (ms) <small class="muted">150–2000</small>
                <input type="number" name="anim_speed" min="150" max="2000" step="50" value="{{ $v('anim_speed') }}" required></label>
        </div>
        <label class="check"><input type="checkbox" name="anim_page_on" value="1" @checked($v('anim_page_on') === '1')> Анимацията на страницата е включена <small class="muted">(махнете отметката, за да я спрете, без да губите избора)</small></label>
        <label class="check"><input type="checkbox" name="anim_pause_btn" value="1" @checked($v('anim_pause_btn') === '1')> Покажи на посетителите бутон „Спри анимациите“ във футъра</label>
        <p class="muted">Посетители, които са включили „намалено движение“ в устройството си, не виждат анимации независимо от настройките.</p>
    </section>

    <section class="card">
        <h2>Анимации на елементи <small class="muted">(при скролиране до елемента)</small></h2>
        <label class="check"><input type="checkbox" name="anim_el_on" value="1" @checked($v('anim_el_on') === '1')> Анимациите на елементите са включени <small class="muted">(главен ключ — спира всички отделни анимации)</small></label>
        <div class="grid-form">
            @foreach (\App\Support\Design::ELEMENTS as $k => $label)
                {!! $animSel('el_'.$k, $label) !!}
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2>Top-bar (лента над хедъра)</h2>
        <label class="check"><input type="checkbox" name="topbar_on" value="1" data-topbar-on @checked($v('topbar_on') === '1')> Top-bar е включен</label>
        <div data-topbar-box>
            <div class="grid-form">
                <label>Брой колони
                    <select name="topbar_cols" data-topbar-cols>
                        @for ($i = 1; $i <= $maxCols; $i++)<option value="{{ $i }}" @selected((int) $v('topbar_cols') === $i)>{{ $i }}</option>@endfor
                    </select>
                </label>
                {!! $color('topbar_bg', 'Фон на top-bar') !!}
                {!! $color('topbar_fg', 'Цвят на текста') !!}
            </div>
            <label class="check"><input type="checkbox" name="topbar_mobile" value="1" @checked($v('topbar_mobile') === '1')> Показвай и на телефон (колоните се подреждат една под друга)</label>

            @for ($i = 1; $i <= $maxCols; $i++)
                <fieldset class="tb-fieldset" data-topbar-col="{{ $i }}">
                    <legend>Колона {{ $i }}</legend>
                    <label>Подравняване
                        <select name="topbar_c{{ $i }}_align">
                            @foreach (['left' => 'Вляво', 'center' => 'Център', 'right' => 'Вдясно'] as $a => $al)
                                <option value="{{ $a }}" @selected($v('topbar_c'.$i.'_align') === $a)>{{ $al }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid-form">
                        @foreach ($languages as $l)
                            <label>Съдържание ({{ strtoupper($l->code) }})
                                <textarea name="topbar[c{{ $i }}][{{ $l->code }}]" rows="3" maxlength="2000" placeholder="Текст или HTML: &lt;a href=&quot;tel:+359…&quot;&gt;…&lt;/a&gt;">{{ old("topbar.c$i.$l->code", \App\Models\Setting::get("design.topbar_c$i.$l->code")) }}</textarea>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endfor
            <p class="muted">Разрешени са връзки, удебеляване, курсив и икони на Font Awesome, напр. <code>&lt;span class="fa-solid fa-phone" aria-hidden="true"&gt;&lt;/span&gt; &lt;a href="tel:+359888000000"&gt;0888 000 000&lt;/a&gt;</code>. Празни колони не се показват.</p>
        </div>
    </section>

    <div class="btn-row">
        <button class="btn primary">Запази дизайна</button>
        <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">Виж сайта ↗</a>
    </div>
</form>

<form method="post" action="{{ route('admin.settings.design-reset') }}" class="inline-form" data-confirm="Да върна ли целия дизайн към подразбиращите се стойности?">
    @csrf <button class="btn danger">Върни дизайна по подразбиране</button>
</form>
