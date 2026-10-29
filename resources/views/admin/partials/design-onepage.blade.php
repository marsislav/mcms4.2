@php
    $opRows = \App\Support\OnePage::rows();
    $opPages = \App\Models\Content::where('type', 'page')->where('is_home', false)->with('translations')->orderBy('sort')->get();
@endphp
<section class="card" id="onepage">
    <h2>{{ __('One-page режим') }}</h2>
    <p class="muted">{{ __('Началната страница става една дълга страница от секции: първо „Начало“, после избраните страници по реда по-долу. Връзките в менюто към тези страници стават котви (#). Всяка секция има своя анимация при скрол.') }}</p>
    <label class="check"><input type="checkbox" name="op_on" value="1" @checked($v('op_on') === '1')> {{ __('Включи one-page режима') }}</label>

    <h3>{{ __('Секции') }}</h3>
    <div class="grid-form">
        @for ($i = 0; $i < \App\Support\OnePage::SLOTS; $i++)
            @php($cur = $opRows[$i] ?? [])
            @php($curC = (int) old("ops.$i.c", $cur['c'] ?? 0))
            @php($curA = old("ops.$i.a", $cur['a'] ?? 'default'))
            <label>{{ __('Секция') }} {{ $i + 2 }}
                <select name="ops[{{ $i }}][c]"><option value="">—</option>
                    @foreach($opPages as $p)<option value="{{ $p->id }}" @selected($curC === $p->id)>{{ $p->adminTitle() }}</option>@endforeach
                </select>
            </label>
            <label>{{ __('Анимация на секцията') }}
                <select name="ops[{{ $i }}][a]"><option value="default" @selected($curA === 'default')>{{ __('Както по подразбиране') }}</option>
                    @foreach(\App\Support\OnePage::ANIMS as $k => $l)<option value="{{ $k }}" @selected($curA === $k)>{{ __($l) }}</option>@endforeach
                </select>
            </label>
        @endfor
    </div>

    <h3>{{ __('Анимации') }}</h3>
    <div class="grid-form">
        <label>{{ __('Анимация по подразбиране') }}
            <select name="op_anim">@foreach (\App\Support\OnePage::ANIMS as $k => $l)<option value="{{ $k }}" @selected($v('op_anim') === $k)>{{ __($l) }}</option>@endforeach</select>
        </label>
        <label>{{ __('Заглавия на секциите') }}
            <select name="op_title">@foreach (\App\Support\OnePage::TITLE as $k => $l)<option value="{{ $k }}" @selected($v('op_title') === $k)>{{ __($l) }}</option>@endforeach</select>
        </label>
        <label>{{ __('Продължителност (ms)') }} <input type="number" name="op_duration" min="200" max="2000" step="50" value="{{ $v('op_duration') }}" required></label>
        <label>{{ __('Забавяне между елементите (ms)') }} <input type="number" name="op_stagger" min="0" max="400" step="10" value="{{ $v('op_stagger') }}" required></label>
    </div>
    <label class="check"><input type="checkbox" name="op_children" value="1" @checked($v('op_children') === '1')> {{ __('Елементите в секцията (абзаци, картинки, списъци) излизат един по един') }}</label>
    <label class="check"><input type="checkbox" name="op_parallax" value="1" @checked($v('op_parallax') === '1')> {{ __('Паралакс на главните картинки') }}</label>
    <label class="check"><input type="checkbox" name="op_dots" value="1" @checked($v('op_dots') === '1')> {{ __('Точки за навигация отстрани') }}</label>
    <label class="check"><input type="checkbox" name="op_full" value="1" @checked($v('op_full') === '1')> {{ __('Всяка секция на цял екран') }}</label>
    <label class="check"><input type="checkbox" name="op_snap" value="1" @checked($v('op_snap') === '1')> {{ __('Залепване към секциите при скрол (не работи заедно с плавния скрол)') }}</label>
</section>
