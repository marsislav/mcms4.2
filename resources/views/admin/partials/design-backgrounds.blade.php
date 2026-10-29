@php
    $bgs = \App\Support\Design::backgrounds();
    $bgIds = collect($bgs)->flatMap(fn ($c) => [$c['image_id'], $c['video_id'], $c['poster_id'], $c['x1_image_id'], $c['x2_image_id'], $c['x3_image_id']])->filter()->unique()->all();
    $bgMedia = $bgIds ? \App\Models\Media::whereIn('id', $bgIds)->get()->keyBy('id') : collect();
    $bv = fn ($k, $f) => old("ebg.$k.$f", $bgs[$k][$f] ?? '');
    $bgColor = function ($k, $f, $label) use ($bv) {
        $val = $bv($k, $f);
        return '<label>'.e($label).'<span class="colorfield">'
            .'<input type="color" data-color-pick value="'.e($val ?: '#888888').'" aria-label="'.e($label).'">'
            .'<input type="text" name="ebg['.$k.']['.$f.']" data-color-text value="'.e($val).'" maxlength="7" placeholder="'.__('няма').'" pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" autocomplete="off">'
            .'<button type="button" class="btn sm" data-color-clear title="'.__('Изчисти').'">✕</button></span></label>';
    };
    $bgSelect = function ($k, $f, $label, array $opts) use ($bv) {
        $o = '';
        foreach ($opts as $val => $l) {
            $o .= '<option value="'.e($val).'"'.((string) $bv($k, $f) === (string) $val ? ' selected' : '').'>'.e(__($l)).'</option>';
        }
        return '<label>'.e($label).'<select name="ebg['.$k.']['.$f.']">'.$o.'</select></label>';
    };
    // size: free text (keyword or lengths such as 50%, 200px, 100% auto) with suggestions
    $bgSize = fn ($k, $f, $label) => '<label>'.e($label).' <input type="text" name="ebg['.$k.']['.$f.']" list="bg-size-list" value="'.e($bv($k, $f)).'" maxlength="24" placeholder="cover" autocomplete="off" spellcheck="false"></label>';
    $bgNum = fn ($k, $f, $label, $min, $max) => '<label>'.e($label).' <input type="number" name="ebg['.$k.']['.$f.']" min="'.$min.'" max="'.$max.'" value="'.e($bv($k, $f)).'"></label>';

    $bgTargets = [];
    foreach (\App\Support\Design::BG_TARGETS as $k => [$l, $s]) { $bgTargets[$k] = [__($l), $s, true]; }
    for ($i = 1; $i <= \App\Support\Design::BG_CUSTOM; $i++) { $bgTargets['c'.$i] = [__('Друг елемент').' '.$i, null, false]; }
@endphp

<datalist id="bg-size-list"><option value="cover"><option value="contain"><option value="auto"><option value="100% 100%"><option value="100% auto"><option value="auto 100%"><option value="50%"><option value="25%"><option value="200px"><option value="400px"></datalist>
<section class="card" id="bg-elements">
    <h2>{{ __('Фонове на елементи') }}</h2>
    <p class="muted">{{ __('За всеки елемент: цвят, градиент, картинка (JPG, PNG, WebP, GIF) и видео (WebM, MP4). Слоевете се наслагват: видео → картинка → градиент → цвят. Празните полета не променят нищо.') }}</p>
    <label class="check"><input type="checkbox" name="bg_video_mobile" value="1" @checked($v('bg_video_mobile') === '1')> {{ __('Пускай видео фоновете и на телефони (иначе се показва само постерът)') }}</label>

    @foreach ($bgTargets as $k => [$label, $sel, $fixed])
        @php
            $used = \App\Support\Design::bgUsed($bgs[$k]);
            $noVideo = in_array($k, \App\Support\Design::BG_NO_VIDEO, true);
            $mImg = $bgMedia[(int) $bv($k, 'image_id')] ?? null;
            $mVid = $bgMedia[(int) $bv($k, 'video_id')] ?? null;
            $mPos = $bgMedia[(int) $bv($k, 'poster_id')] ?? null;
        @endphp
        <details class="bg-box" @if($used) open @endif>
            <summary><strong>{{ $label }}</strong>@if($sel) <code class="muted">{{ $sel }}</code>@endif @if($used) <span class="chip sel">{{ __('зададен') }}</span>@endif</summary>

            @unless($fixed)
                <div class="grid-form">
                    <label>{{ __('CSS селектор') }} <small class="muted">{{ __('напр. .hero, #about, section.cta') }}</small>
                        <input type="text" name="ebg[{{ $k }}][selector]" value="{{ $bv($k, 'selector') }}" maxlength="120" autocomplete="off" spellcheck="false">
                    </label>
                </div>
            @endunless

            <h3>{{ __('Цвят') }}</h3>
            <div class="grid-form">
                {!! $bgColor($k, 'color', __('Фонов цвят')) !!}
                {!! $bgColor($k, 'text', __('Цвят на текста')) !!}
            </div>

            <h3>{{ __('Градиент') }}</h3>
            <label class="check"><input type="checkbox" name="ebg[{{ $k }}][grad]" value="1" @checked($bv($k, 'grad') === '1')> {{ __('Включи градиент') }}</label>
            <div class="grid-form">
                {!! $bgSelect($k, 'grad_type', __('Вид'), \App\Support\Design::BG_GRAD) !!}
                {!! $bgNum($k, 'grad_angle', __('Ъгъл (°, за линеен и конусен)'), 0, 360) !!}
                {!! $bgColor($k, 'grad_c1', __('Цвят 1 (начало)')) !!}
                {!! $bgNum($k, 'grad_a1', __('Плътност 1 (%)'), 0, 100) !!}
                {!! $bgColor($k, 'grad_c3', __('Цвят 2 (среда, по желание)')) !!}
                {!! $bgNum($k, 'grad_a3', __('Плътност 2 (%)'), 0, 100) !!}
                {!! $bgColor($k, 'grad_c2', __('Цвят 3 (край)')) !!}
                {!! $bgNum($k, 'grad_a2', __('Плътност 3 (%)'), 0, 100) !!}
            </div>

            <h3>{{ __('Картинка') }}</h3>
            @include('admin.partials.media-field', ['name' => "ebg[$k][image_id]", 'label' => __('Фонова картинка'), 'm' => $mImg, 'kind' => 'image'])
            <div class="grid-form">
                {!! $bgSize($k, 'size', __('Размер')) !!}
                {!! $bgSelect($k, 'pos', __('Позиция'), \App\Support\Design::BG_POS) !!}
                {!! $bgSelect($k, 'repeat', __('Повторение'), \App\Support\Design::BG_REPEAT) !!}
                {!! $bgSelect($k, 'attach', __('Скрол'), \App\Support\Design::BG_ATTACH) !!}
            </div>

            <h3>{{ __('Допълнителни картинки (наслагват се)') }}</h3>
            <p class="muted">{{ __('До :n картинки с различни размер, позиция и смесване. Редът отгоре надолу: наслагване → градиент → допълнителни 1, 2, 3 → основната картинка отдолу.', ['n' => \App\Support\Design::BG_EXTRAS]) }}</p>
            @for ($n = 1; $n <= \App\Support\Design::BG_EXTRAS; $n++)
                <details class="bg-sub" @if($bv($k, "x{$n}_image_path") !== '') open @endif>
                    <summary>{{ __('Допълнителна картинка') }} {{ $n }}</summary>
                    @include('admin.partials.media-field', ['name' => "ebg[$k][x{$n}_image_id]", 'label' => __('Картинка').' '.$n, 'm' => $bgMedia[(int) $bv($k, "x{$n}_image_id")] ?? null, 'kind' => 'image'])
                    <div class="grid-form">
                        {!! $bgSize($k, "x{$n}_size", __('Размер')) !!}
                        {!! $bgSelect($k, "x{$n}_pos", __('Позиция'), \App\Support\Design::BG_POS) !!}
                        {!! $bgSelect($k, "x{$n}_repeat", __('Повторение'), \App\Support\Design::BG_REPEAT) !!}
                        {!! $bgSelect($k, "x{$n}_attach", __('Скрол'), \App\Support\Design::BG_ATTACH) !!}
                        {!! $bgSelect($k, "x{$n}_blend", __('Смесване със слоевете отдолу'), \App\Support\Design::BG_BLEND) !!}
                    </div>
                </details>
            @endfor

            <h3>{{ __('Анимация на фона') }}</h3>
            <div class="grid-form">
                {!! $bgSelect($k, 'anim', __('Анимация'), \App\Support\Design::BG_ANIM) !!}
                {!! $bgSelect($k, 'anim_mode', __('Как върви'), \App\Support\Design::BG_ANIM_MODE) !!}
                {!! $bgSelect($k, 'anim_loop', __('Повторение'), \App\Support\Design::BG_ANIM_LOOP) !!}
                {!! $bgNum($k, 'anim_speed', __('Продължителност (сек.)'), 3, 120) !!}
            </div>

            @unless($noVideo)
                <h3>{{ __('Видео (WebM / MP4)') }}</h3>
                <div class="grid-form">
                    @include('admin.partials.media-field', ['name' => "ebg[$k][video_id]", 'label' => __('Фоново видео'), 'm' => $mVid, 'kind' => 'video'])
                    @include('admin.partials.media-field', ['name' => "ebg[$k][poster_id]", 'label' => __('Постер (картинка преди/вместо видеото)'), 'm' => $mPos, 'kind' => 'image'])
                </div>
                <small class="muted">{{ __('Видеото е без звук, на цикъл и спира, когато не се вижда. На телефони и при „намалено движение“ се показва постерът.') }}</small>
            @endunless

            <h3>{{ __('Затъмняване (върху картинка, градиент и видео)') }}</h3>
            <div class="grid-form">
                {!! $bgColor($k, 'overlay', __('Цвят на слоя')) !!}
                {!! $bgNum($k, 'overlay_op', __('Плътност (%)'), 0, 100) !!}
            </div>
        </details>
    @endforeach
</section>
