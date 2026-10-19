<label>{{ __('Часова зона') }}
    <select name="timezone">
        <option value="">{{ __('По подразбиране (:p1)', ['p1' => config('app.timezone')]) }}</option>
        @foreach (\DateTimeZone::listIdentifiers() as $tz)<option value="{{ $tz }}" @selected($opt['timezone'] === $tz)>{{ $tz }}</option>@endforeach
    </select>
</label>
<p class="muted">{{ __('Внимание: вече записаните дати и планирани публикации се четат в новата зона, т.е. ще се изместят. Сменяйте я преди да планирате публикации.') }}</p>
<div class="grid-form">
    <label>{{ __('Формат на датата') }}
        <select name="date_format">@foreach (array_keys(\App\Support\Options::DATE_FORMATS) as $f)<option value="{{ $f }}" @selected($opt['date_format'] === $f)>{{ now()->translatedFormat($f) }}</option>@endforeach</select>
    </label>
    <label>{{ __('Формат на часа') }}
        <select name="time_format">@foreach (\App\Support\Options::TIME_FORMATS as $f => $ex)<option value="{{ $f }}" @selected($opt['time_format'] === $f)>{{ now()->translatedFormat($f) }}</option>@endforeach</select>
    </label>
</div>
<p class="muted">{{ __('Сега:') }} {{ now()->translatedFormat($opt['date_format'].', '.$opt['time_format']) }}</p>
