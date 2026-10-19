<label class="check"><input type="checkbox" name="registration" value="1" @checked($opt['registration'] === '1')> {!! __('<strong>Всеки може да се регистрира</strong> <small class="muted">(страница <code>:p1</code>)</small>', ['p1' => e(url('register'))]) !!}</label>
<label>{{ __('Роля на новите потребители') }}
    <select name="default_role">
        <option value="subscriber" @selected($opt['default_role'] === 'subscriber')>{{ __('Абонат — само профил и резюме') }}</option>
        <option value="contributor" @selected($opt['default_role'] === 'contributor')>{{ __('Сътрудник — пише статии, администратор/редактор ги одобрява') }}</option>
        <option value="author" @selected($opt['default_role'] === 'author')>{{ __('Автор — пише и публикува собствените си статии') }}</option>
    </select>
</label>
<label class="check"><input type="checkbox" name="reg_approval" value="1" @checked($opt['reg_approval'] === '1')> {{ __('Новите профили чакат одобрение от администратор (Потребители → „Активен профил“)') }}</label>
<label class="check"><input type="checkbox" name="reg_notify" value="1" @checked($opt['reg_notify'] === '1')> {{ __('Имейл до администратора при нова регистрация и при статия за одобрение') }}</label>
<p class="muted">{!! __('Роли: <strong>Абонат</strong> (профил), <strong>Сътрудник</strong> (чернови и „Чака одобрение“, не публикува, няма медия), <strong>Автор</strong> (публикува свои статии, качва медия), <strong>Редактор</strong> (всички статии и страници), <strong>Администратор</strong>. Страниците и настройките са само за редактор/администратор. Защита: скрито поле, ограничение на опитите, парола минимум 10 знака. Потвърждение на имейл няма — при спам включете одобрение.') !!}</p>
