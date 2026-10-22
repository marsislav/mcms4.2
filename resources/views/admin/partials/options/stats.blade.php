<label class="check"><input type="checkbox" name="stats_on" value="1" @checked($opt['stats_on'] === '1')> {{ __('Брои посещенията (страници, публикации, категории, тагове, автори)') }}</label>
<label class="check"><input type="checkbox" name="stats_skip_logged" value="1" @checked($opt['stats_skip_logged'] === '1')> {{ __('Не брои влезлите в панела потребители') }}</label>
<label class="check"><input type="checkbox" name="stats_skip_bots" value="1" @checked($opt['stats_skip_bots'] === '1')> {{ __('Не брои ботове и роботи') }}</label>
<div class="grid-form">
    <label>{{ __('Пази дневните данни (дни)') }} <input type="number" name="stats_keep_days" min="7" max="3650" value="{{ $opt['stats_keep_days'] }}"></label>
    <label>{!! __('Период за „Най-четени“ (дни) <small class="muted">0 = за всички времена</small>') !!} <input type="number" name="popular_days" min="0" max="3650" value="{{ $opt['popular_days'] }}"></label>
</div>
<p class="muted">{{ __('Не се записват IP адреси или бисквитки — само брой на ден за всеки адрес. „Най-четени“ се добавя като джаджа в Сайдбар/Футър.') }}</p>
