{{-- One field of the contact form builder. Params: $i (index or __I__), $f (field array), $languages. --}}
@php $f = \App\Support\ContactFields::normalize($f); $n = fn ($k) => "fields[$i][$k]"; @endphp
<details class="cf-row" data-cf-row @if(! empty($open)) open @endif>
    <summary>
        <span class="cf-handle muted" aria-hidden="true">⠿</span>
        <span class="cf-title">{{ \App\Support\ContactFields::label($f, mcms_default_locale()) ?: __('Ново поле') }}</span>
        <span class="muted cf-type-label">{{ __(\App\Support\ContactFields::TYPES[$f['type']] ?? $f['type']) }}</span>
        <span class="w-actions">
            <button type="button" class="btn sm" data-cf-up title="{{ __('Нагоре') }}" aria-label="{{ __('Нагоре') }}">↑</button>
            <button type="button" class="btn sm" data-cf-down title="{{ __('Надолу') }}" aria-label="{{ __('Надолу') }}">↓</button>
            <button type="button" class="btn sm danger" data-cf-del title="{{ __('Премахни') }}" aria-label="{{ __('Премахни') }}">✕</button>
        </span>
    </summary>
    <div class="cf-body">
        <input type="hidden" name="{{ $n('key') }}" value="{{ $f['key'] }}">
        <div class="grid-form">
            @foreach ($languages as $l)
                <label>{{ __('Етикет') }} ({{ strtoupper($l->code) }}) <input name="{{ $n('label') }}[{{ $l->code }}]" maxlength="100" value="{{ $f['label'][$l->code] ?? '' }}" data-cf-label></label>
            @endforeach
            <label>{{ __('Тип') }}
                <select name="{{ $n('type') }}" data-cf-type>
                    @foreach (\App\Support\ContactFields::TYPES as $t => $tl)<option value="{{ $t }}" @selected($f['type'] === $t)>{{ __($tl) }}</option>@endforeach
                </select>
            </label>
            <label>{{ __('Ширина') }}
                <select name="{{ $n('width') }}">
                    @foreach (\App\Support\ContactFields::WIDTHS as $w => $wl)<option value="{{ $w }}" @selected($f['width'] === $w)>{{ __($wl) }}</option>@endforeach
                </select>
            </label>
        </div>

        <div data-cf-for="text textarea email tel url number">
            <div class="grid-form">
                @foreach ($languages as $l)
                    <label>{{ __('Примерен текст (placeholder)') }} ({{ strtoupper($l->code) }}) <input name="{{ $n('placeholder') }}[{{ $l->code }}]" maxlength="120" value="{{ $f['placeholder'][$l->code] ?? '' }}"></label>
                @endforeach
            </div>
        </div>
        <div class="grid-form">
            @foreach ($languages as $l)
                <label>{{ __('Помощен текст под полето') }} ({{ strtoupper($l->code) }}) <input name="{{ $n('help') }}[{{ $l->code }}]" maxlength="300" value="{{ $f['help'][$l->code] ?? '' }}"></label>
            @endforeach
        </div>

        <div data-cf-for="select radio checkboxes">
            <label>{!! __('Опции <small class="muted">(по една на ред)</small>') !!}
                <textarea name="{{ $n('options') }}" rows="4" maxlength="3000">{{ implode("\n", $f['options']) }}</textarea>
            </label>
        </div>
        <div data-cf-for="text textarea email tel url number date time select radio checkbox checkboxes">
            <label>{!! __('Стойност по подразбиране <small class="muted">(за отметка: 1 = отметната; за „Няколко отметки“ — по една на ред)</small>') !!}
                <input name="{{ $n('default') }}" maxlength="200" value="{{ $f['default'] }}">
            </label>
        </div>
        <div data-cf-for="text textarea" class="grid-form">
            <label>{{ __('Мин. знаци') }} <input type="number" name="{{ $n('minlen') }}" min="0" max="2000" value="{{ $f['minlen'] }}"></label>
            <label>{{ __('Макс. знаци') }} <input type="number" name="{{ $n('maxlen') }}" min="1" max="2000" value="{{ $f['maxlen'] }}"></label>
        </div>
        <div data-cf-for="textarea"><label>{{ __('Редове') }} <input type="number" name="{{ $n('rows') }}" min="2" max="20" value="{{ $f['rows'] }}" class="narrow"></label></div>
        <div data-cf-for="number" class="grid-form">
            <label>{{ __('Минимум') }} <input name="{{ $n('min') }}" inputmode="decimal" value="{{ $f['min'] }}"></label>
            <label>{{ __('Максимум') }} <input name="{{ $n('max') }}" inputmode="decimal" value="{{ $f['max'] }}"></label>
        </div>
        <div data-cf-for="date" class="grid-form">
            <label>{{ __('Най-ранна дата') }} <input type="date" name="{{ $n('min') }}" value="{{ $f['min'] }}"></label>
            <label>{{ __('Най-късна дата') }} <input type="date" name="{{ $n('max') }}" value="{{ $f['max'] }}"></label>
        </div>
        <label>{!! __('CSS клас <small class="muted">(по избор)</small>') !!} <input name="{{ $n('class') }}" maxlength="80" value="{{ $f['class'] }}" placeholder="{{ __('напр. highlight') }}"></label>
        <div data-cf-for="text textarea email tel url number date time select radio checkbox checkboxes">
            <label class="check"><input type="checkbox" name="{{ $n('required') }}" value="1" @checked(! empty($f['required']))> {{ __('Задължително поле') }}</label>
        </div>
    </div>
</details>
