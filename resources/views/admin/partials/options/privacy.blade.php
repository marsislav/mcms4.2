<label class="check"><input type="checkbox" name="cc_on" value="1" @checked($opt['cc_on'] === '1')> {{ __('Показвай банер за бисквитки (GDPR)') }}</label>
<div class="grid-form">
    <label>{{ __('Позиция') }}
        <select name="cc_position">@foreach (\App\Support\Options::CC_POSITIONS as $k => $lbl)<option value="{{ $k }}" @selected($opt['cc_position'] === $k)>{{ __($lbl) }}</option>@endforeach</select>
    </label>
    <label>{{ __('Запомни избора (дни)') }} <input type="number" name="cc_days" min="1" max="730" value="{{ $opt['cc_days'] }}"></label>
    <label>{{ __('Адрес на политиката за поверителност') }} <input name="cc_policy_url" maxlength="300" value="{{ $opt['cc_policy_url'] }}" placeholder="/privacy"></label>
</div>
<label class="check"><input type="checkbox" name="cc_analytics" value="1" @checked($opt['cc_analytics'] === '1')> {{ __('Категория „Статистика“ (избираема от посетителя)') }}</label>
<label class="check"><input type="checkbox" name="cc_marketing" value="1" @checked($opt['cc_marketing'] === '1')> {{ __('Категория „Маркетинг“ (избираема от посетителя)') }}</label>
<label class="check"><input type="checkbox" name="cc_reopen" value="1" @checked($opt['cc_reopen'] === '1')> {{ __('Малък бутон за промяна на избора след съгласие') }}</label>
@include('admin.partials.loc-input', ['key' => 'cc_title', 'label' => __('Заглавие'), 'max' => 120])
@include('admin.partials.loc-input', ['key' => 'cc_text', 'label' => __('Текст'), 'rows' => 3, 'max' => 600])
@include('admin.partials.loc-input', ['key' => 'cc_accept', 'label' => __('Бутон „Приемам“'), 'max' => 120])
@include('admin.partials.loc-input', ['key' => 'cc_reject', 'label' => __('Бутон „Отказвам“'), 'max' => 120])
@include('admin.partials.loc-input', ['key' => 'cc_settings', 'label' => __('Бутон „Настройки“'), 'max' => 120])
@include('admin.partials.loc-input', ['key' => 'cc_policy_label', 'label' => __('Текст на връзката към политиката'), 'max' => 120])
<label>{!! __('Скриптове „Статистика“ <small class="muted">зареждат се само след съгласие</small>') !!}
    <textarea name="scripts_analytics" rows="5" maxlength="20000" spellcheck="false" class="code">{{ $opt['scripts_analytics'] }}</textarea></label>
<label>{!! __('Скриптове „Маркетинг“ <small class="muted">зареждат се само след съгласие</small>') !!}
    <textarea name="scripts_marketing" rows="5" maxlength="20000" spellcheck="false" class="code">{{ $opt['scripts_marketing'] }}</textarea></label>
<p class="muted">{{ __('Външните хостове на тези скриптове трябва да са разрешени в „Скриптове“ → разрешени адреса (CSP).') }}</p>
