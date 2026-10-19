<form method="post" action="{{ route('admin.settings.sidebar') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>{{ __('Сайдбар на блога') }}</h2>
        <label class="check"><input type="checkbox" name="sidebar_on" value="1" @checked($sidebar['on'])> {!! __('<strong>Сайдбарът е включен</strong>') !!}</label>
        <label>{{ __('Страна') }}
            <select name="sidebar_side">
                <option value="right" @selected($sidebar['side'] === 'right')>{{ __('Вдясно') }}</option>
                <option value="left" @selected($sidebar['side'] === 'left')>{{ __('Вляво') }}</option>
            </select>
        </label>
        <p class="muted">{!! __('Показва се <strong>само в блога</strong> — списък с публикации, категории, тагове и самите публикации. Не се показва на страниците. На телефон отива под съдържанието.') !!}</p>
    </section>

    <section class="card">
        <h3>{{ __('Уиджети') }}</h3>
        @include('admin.partials.widget-area', ['col' => 0, 'list' => $sidebar['widgets']])
    </section>

    <button class="btn primary">{{ __('Запази сайдбара') }}</button>
</form>
