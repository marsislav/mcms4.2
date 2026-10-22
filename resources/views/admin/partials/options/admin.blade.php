<label class="check"><input type="checkbox" name="adminbar" value="1" @checked($opt['adminbar'] === '1')> {{ __('Лента за администратори на сайта (бутони „Нова статия/страница/категория“, „Редактирай“)') }}</label>
<div class="grid-form">
    <label>{{ __('Журнал на грешките: колко KB от края да се четат') }} <input type="number" name="log_tail_kb" min="50" max="2000" value="{{ $opt['log_tail_kb'] }}"></label>
</div>
<p><a href="{{ route('admin.system.migrations') }}">{{ __('Миграции на базата данни →') }}</a></p>
