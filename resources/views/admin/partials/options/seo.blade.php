<h3>{{ __('Трохи от хляб (breadcrumbs)') }}</h3>
<label class="check"><input type="checkbox" name="breadcrumbs" value="1" @checked($opt['breadcrumbs'] === '1')> {{ __('Показвай навигационна пътека (Начало › Блог › Категория › Статия)') }}</label>
<div class="grid-form">
    <label>{{ __('Разделител') }}
        <select name="bc_separator">@foreach (\App\Support\Options::BC_SEPARATORS as $k => $lbl)<option value="{{ $k }}" @selected($opt['bc_separator'] === $k)>{{ $lbl }}</option>@endforeach</select>
    </label>
</div>
@include('admin.partials.loc-input', ['key' => 'bc_home', 'label' => __('Текст за „Начало“'), 'max' => 60, 'ph' => __('Начало')])
<label class="check"><input type="checkbox" name="bc_show_current" value="1" @checked($opt['bc_show_current'] === '1')> {{ __('Показвай и текущата страница в края') }}</label>
<label class="check"><input type="checkbox" name="bc_on_pages" value="1" @checked($opt['bc_on_pages'] === '1')> {{ __('На страниците') }}</label>
<label class="check"><input type="checkbox" name="bc_on_posts" value="1" @checked($opt['bc_on_posts'] === '1')> {{ __('На публикациите') }}</label>
<label class="check"><input type="checkbox" name="bc_on_lists" value="1" @checked($opt['bc_on_lists'] === '1')> {{ __('На списъците (блог, категория, таг, автор, търсене, контакти)') }}</label>

<h3>{{ __('Структурирани данни') }}</h3>
<label class="check"><input type="checkbox" name="jsonld" value="1" @checked($opt['jsonld'] === '1')> {{ __('JSON-LD за търсачките (WebSite + търсене, статия/страница, навигационна пътека)') }}</label>

<h3>{{ __('Грешка 404') }}</h3>
<label class="check"><input type="checkbox" name="notfound_log" value="1" @checked($opt['notfound_log'] === '1')> {{ __('Записвай несъществуващите адреса (списък в „Журнал 404“ с бутон за пренасочване)') }}</label>
<div class="grid-form">
    <label>{{ __('Пази записите (дни)') }} <input type="number" name="notfound_keep_days" min="1" max="3650" value="{{ $opt['notfound_keep_days'] }}"></label>
</div>
