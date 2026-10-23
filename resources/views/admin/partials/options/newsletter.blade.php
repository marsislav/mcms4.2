<label class="check"><input type="checkbox" name="nl_on" value="1" @checked($opt['nl_on'] === '1')> {{ __('Включи формуляра за бюлетин (джаджа „Бюлетин“)') }}</label>
<label class="check"><input type="checkbox" name="nl_double_optin" value="1" @checked($opt['nl_double_optin'] === '1')> {{ __('Двойно потвърждение (имейл с връзка за потвърждение)') }}</label>
<label class="check"><input type="checkbox" name="nl_require_consent" value="1" @checked($opt['nl_require_consent'] === '1')> {{ __('Изисквай съгласие (отметка) при записване') }}</label>
<label class="check"><input type="checkbox" name="nl_ask_name" value="1" @checked($opt['nl_ask_name'] === '1')> {{ __('Питай и за име') }}</label>
<label class="check"><input type="checkbox" name="nl_notify_admin" value="1" @checked($opt['nl_notify_admin'] === '1')> {{ __('Известявай администратора за нов абонат') }}</label>
<label class="check"><input type="checkbox" name="nl_notify_posts" value="1" @checked($opt['nl_notify_posts'] === '1')> {{ __('Изпращай имейл на абонатите при нова публикация (до 200 получатели наведнъж)') }}</label>
@include('admin.partials.loc-input', ['key' => 'nl_title', 'label' => __('Заглавие'), 'max' => 120])
@include('admin.partials.loc-input', ['key' => 'nl_text', 'label' => __('Кратък текст'), 'rows' => 2, 'max' => 400])
@include('admin.partials.loc-input', ['key' => 'nl_consent', 'label' => __('Текст на съгласието'), 'rows' => 2, 'max' => 400])
@include('admin.partials.loc-input', ['key' => 'nl_button', 'label' => __('Текст на бутона'), 'max' => 120])
