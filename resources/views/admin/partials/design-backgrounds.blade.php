@php
    $bgs = \App\Support\Design::backgrounds();
    $bgIds = collect($bgs)->flatMap(fn ($c) => [$c['image_id'], $c['video_id'], $c['poster_id']])->filter()->unique()->all();
    $bgMedia = $bgIds ? \App\Models\Media::whereIn('id', $bgIds)->get()->keyBy('id') : collect();
    $bv = fn ($k, $f) => old("bg.$k.$f", $bgs[$k][$f] ?? '');
    $bgColor = function ($k, $f, $label) use ($bv) {
        $val = $bv($k, $f);
        return '<label>'.e($label).'<span class="colorfield">'
            .'<input type="color" data-color-pick value="'.e($val ?: '#888888').'" aria-label="'.e($label).'">'
            .'<input type="text" name="bg['.$k.']['.$f.']" data-color-text value="'.e($val).'" maxlength="7" placeholder="'.__('няма').'" pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?" autocomplete="off">'
            .'<button type="button" class="btn sm" data-color-clear title="'.__('Изчисти').'">✕</button></span></label>';
    };
    $bgSelect = function ($k, $f, $label, array $opts) use ($bv) {
        $o = '';
        foreach ($opts as $val => $l) {
            $o .= '<option value="'.e($val).'"'.((string) $bv($k, $f) === (string) $val ? ' selected' : '').'>'.e(__($l)).'</option>';
        }
        return '<label>'.e($label).'<select name="bg['.$k.']['.$f.']">'.$o.'</select></label>';
    };
    $bgNum = fn ($k, $f, $label, $min, $max) => '<label>'.e($label).' <input type="number" name="bg['.$k.']['.$f.']" min="'.$min.'" max="'.$max.'" value="'.e($bv($k, $f)).'"></label>';

    $bgTargets = [];
    foreach (\App\Support\Design::BG_TARGETS as $k => [$l, $s]) { $bgTargets[$k] = [__($l), $s, true]; }
    for ($i = 1; $i <= \App\Support\Design::BG_CUSTOM; $i++) { $bgTargets['c'.$i] = [__('Друг елемент').' '.$i, null, false]; }
@endphp

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
                        <input type="text" name="bg[{{ $k }}][selector]" value="{{ $bv($k, 'selector') }}" maxlength="120" autocomplete="off" spellcheck="false">
                    </label>
                </div>
            @endunless

            <h3>{{ __('Цвят') }}</h3>
            <div class="grid-form">
                {!! $bgColor($k, 'color', __('Фонов цвят')) !!}
                {!! $bgColor($k, 'text', __('Цвят на текста')) !!}
            </div>

            <h3>{{ __('Градиент') }}</h3>
            <label class="check"><input type="checkbox" name="bg[{{ $k }}][grad]" value="1" @checked($bv($k, 'grad') === '1')> {{ __('Включи градиент') }}</label>
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
            @include('admin.partials.media-field', ['name' => "bg[$k][image_id]", 'label' => __('Фонова картинка'), 'm' => $mImg, 'kind' => 'image'])
            <div class="grid-form">
                {!! $bgSelect($k, 'size', __('Размер'), \App\Support\Design::BG_SIZE) !!}
                {!! $bgSelect($k, 'pos', __('Позиция'), \App\Support\Design::BG_POS) !!}
                {!! $bgSelect($k, 'repeat', __('Повторение'), \App\Support\Design::BG_REPEAT) !!}
                {!! $bgSelect($k, 'attach', __('Скрол'), \App\Support\Design::BG_ATTACH) !!}
            </div>

            @unless($noVideo)
                <h3>{{ __('Видео (WebM / MP4)') }}</h3>
                <div class="grid-form">
                    @include('admin.partials.media-field', ['name' => "bg[$k][video_id]", 'label' => __('Фоново видео'), 'm' => $mVid, 'kind' => 'video'])
                    @include('admin.partials.media-field', ['name' => "bg[$k][poster_id]", 'label' => __('Постер (картинка преди/вместо видеото)'), 'm' => $mPos, 'kind' => 'image'])
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
