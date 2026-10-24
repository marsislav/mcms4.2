@php
    $fxSets = \App\Support\Fx::bars();
    $fv = fn ($i, $f, $def = '') => old("pbars.$i.$f", $fxSets[$i][$f] ?? $def);
    $fxColor = function ($i, $f, $label) use ($fv) {
        $val = $fv($i, $f);
        return '<label>'.e($label).'<span class="colorfield">'
            .'<input type="color" data-color-pick value="'.e($val ?: '#888888').'" aria-label="'.e($label).'">'
            .'<input type="text" name="pbars['.$i.']['.$f.']" data-color-text value="'.e($val).'" maxlength="7" placeholder="'.__('по подразбиране').'" pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" autocomplete="off">'
            .'<button type="button" class="btn sm" data-color-clear title="'.__('Изчисти').'">✕</button></span></label>';
    };
@endphp

<section class="card" id="fx-h1">
    <h2>{{ __('Анимирани заглавия (H1)') }}</h2>
    <div class="grid-form">
        <label>{{ __('Анимация') }}
            <select name="h1_anim">@foreach (\App\Support\Design::H1_ANIMS as $k => $l)<option value="{{ $k }}" @selected($v('h1_anim') === $k)>{{ __($l) }}</option>@endforeach</select>
        </label>
        <label>{{ __('Къде се прилага') }}
            <select name="h1_scope">@foreach (\App\Support\Design::H1_SCOPE as $k => $l)<option value="{{ $k }}" @selected($v('h1_scope') === $k)>{{ __($l) }}</option>@endforeach</select>
        </label>
        {!! $color('h1_underline_color', __('Цвят на подчертаването'), __('празно = акцентният цвят')) !!}
    </div>
    <label class="check"><input type="checkbox" name="h1_underline" value="1" @checked($v('h1_underline') === '1')> {{ __('Подчертавай дума в заглавието (линията се чертае)') }}</label>
    <div class="grid-form">
        <label>{{ __('Коя дума по подразбиране') }}
            <select name="h1_word">@foreach (\App\Support\Design::H1_WORD as $k => $l)<option value="{{ $k }}" @selected($v('h1_word') === $k)>{{ __($l) }}</option>@endforeach</select>
        </label>
    </div>
    <small class="muted">{{ __('За конкретна страница или статия избери друга дума в полето „Подчертана дума“ под заглавието ѝ. Работи за заглавия само с текст. Спира при „намалено движение“.') }}</small>

    <h3>{{ __('Въртящ се текст') }}</h3>
    <p class="muted">{!! __('Сложи в текста на страница или статия: :code — думите се сменят една след друга.', ['code' => '<code>[rotate Първи текст | Втори текст | Трети текст]</code>']) !!}</p>
    <div class="grid-form">
        <label>{{ __('Смяна на всеки (ms)') }} <input type="number" name="rotate_interval" min="1000" max="10000" step="100" value="{{ $v('rotate_interval') }}" required></label>
    </div>
</section>

<section class="card" id="fx-bars">
    <h2>{{ __('Прогрес барове') }}</h2>
    <p class="muted">{!! __('Създай набор, после го сложи където искаш с :code (в страница, статия, HTML уиджет или блок). Всеки ред е един бар: :fmt', ['code' => '<code>[bars име]</code>', 'fmt' => '<code>Етикет | процент | #цвят (по желание) | бележка (по желание)</code>']) !!}</p>

    @for ($i = 1; $i <= \App\Support\Fx::SETS; $i++)
        @php $used = \App\Support\Fx::used($fxSets[(string) $i]); @endphp
        <details class="bg-box" @if($used) open @endif>
            <summary><strong>{{ __('Набор') }} {{ $i }}</strong> @if($used)<code>[bars {{ $fxSets[(string) $i]['slug'] }}]</code>@endif</summary>
            <div class="grid-form">
                <label>{{ __('Име (латиница, за кода)') }} <input type="text" name="pbars[{{ $i }}][slug]" value="{{ $fv((string) $i, 'slug') }}" maxlength="40" pattern="[a-z0-9\-]*" placeholder="skills" autocomplete="off"></label>
                <label>{{ __('Заглавие (по желание)') }} <input type="text" name="pbars[{{ $i }}][title]" value="{{ $fv((string) $i, 'title') }}" maxlength="100"></label>
                <label>{{ __('Вид') }}
                    <select name="pbars[{{ $i }}][style]">@foreach (\App\Support\Fx::STYLES as $k => $l)<option value="{{ $k }}" @selected($fv((string) $i, 'style', 'bar') === $k)>{{ __($l) }}</option>@endforeach</select>
                </label>
                <label>{{ __('Бързина на пълнене (ms)') }} <input type="number" name="pbars[{{ $i }}][speed]" min="400" max="5000" step="100" value="{{ $fv((string) $i, 'speed', '1400') }}"></label>
                {!! $fxColor((string) $i, 'color', __('Цвят на баровете')) !!}
                {!! $fxColor((string) $i, 'track', __('Цвят на пистата')) !!}
            </div>
            <label class="check"><input type="hidden" name="pbars[{{ $i }}][counter]" value="0"><input type="checkbox" name="pbars[{{ $i }}][counter]" value="1" @checked($fv((string) $i, 'counter', '1') === '1')> {{ __('Показвай процента (брои се нагоре)') }}</label>
            <label>{{ __('Барове — по един на ред') }}
                <textarea name="pbars[{{ $i }}][rows_text]" rows="6" spellcheck="false" placeholder="HTML & CSS | 95&#10;WordPress | 90 | #ff6036 | 10 години&#10;Laravel | 80">{{ old("pbars.$i.rows_text", \App\Support\Fx::rowsText($fxSets[(string) $i]['rows'])) }}</textarea>
            </label>
        </details>
    @endfor
</section>
