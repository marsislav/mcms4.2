<label class="check"><input type="checkbox" name="listings_on" value="1" @checked($opt['listings_on'] === '1')> {!! __('<strong>Включи обявите</strong> <small class="muted">(страница <code>:p1</code>; изключено = публичните страници връщат 404, а данните се запазват)</small>', ['p1' => e(url('obyavi'))]) !!}</label>
<label class="check"><input type="checkbox" name="listings_approval" value="1" @checked($opt['listings_approval'] === '1')> {{ __('Новите и редактираните обяви чакат одобрение от администратор/редактор') }}</label>
<label class="check"><input type="checkbox" name="listings_notify" value="1" @checked($opt['listings_notify'] === '1')> {{ __('Имейл до администратора при обява за одобрение') }}</label>
<label>{{ __('Възможни срокове на валидност (дни, със запетая)') }} <input name="listings_days" value="{{ $opt['listings_days'] }}" maxlength="40" placeholder="7,14,30,60"></label>
<label>{{ __('Снимки на обява (0–10)') }} <input type="number" name="listings_images" min="0" max="10" value="{{ $opt['listings_images'] }}"></label>
<label>{{ __('Обяви на страница') }} <input type="number" name="listings_per_page" min="3" max="48" value="{{ $opt['listings_per_page'] }}"></label>
<p class="muted">{!! __('Подаването изисква профил (Настройки → Потребители → регистрация). Модерация: меню <strong>Обяви</strong>.') !!}</p>
