<label class="check"><input type="checkbox" name="livesearch" value="1" @checked($opt['livesearch'] === '1')> {{ __('Търсене в реално време (резултати докато пишете)') }}</label>
<div class="grid-form">
    <label>{{ __('Минимум знаци') }} <input type="number" name="live_min" min="1" max="6" value="{{ $opt['live_min'] }}"></label>
    <label>{{ __('Максимум резултати') }} <input type="number" name="live_max" min="3" max="15" value="{{ $opt['live_max'] }}"></label>
</div>
