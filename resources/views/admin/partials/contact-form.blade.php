@php
    $cfRows = array_values($contactFields ?? []);
    for ($i = 0; $i < 3; $i++) { $cfRows[] = ['key' => '', 'type' => 'text', 'label' => [], 'required' => false, 'options' => []]; }
@endphp
<form method="post" action="{{ route('admin.settings.contact') }}" class="settings-form">
    @csrf @method('PUT')
    <section class="card">
        <h2>{{ __('Контактна форма') }}</h2>
        <p class="muted">{{ __('Имейл, име, тема и съобщение са винаги налични. Тук добавяте допълнителни полета — те се показват преди съобщението и се добавят към текста му (в панела и в писмото до вас). Празен етикет = празен ред; „Изтрий“ премахва полето. Нужни още редове? Запазете и отворете страницата отново — добавят се три нови празни реда.') }}</p>
        @error('fields')<div class="flash err">{{ $message }}</div>@enderror

        @foreach ($cfRows as $i => $f)
            <fieldset class="cf-row">
                <legend>{{ __('Поле') }} {{ $i + 1 }}</legend>
                <input type="hidden" name="fields[{{ $i }}][key]" value="{{ $f['key'] }}">
                <div class="grid-form">
                    @foreach ($languages as $l)
                        <label>{{ __('Етикет') }} ({{ strtoupper($l->code) }}) <input name="fields[{{ $i }}][label][{{ $l->code }}]" maxlength="100" value="{{ $f['label'][$l->code] ?? '' }}"></label>
                    @endforeach
                    <label>{{ __('Тип') }}
                        <select name="fields[{{ $i }}][type]">
                            @foreach (\App\Support\ContactFields::TYPES as $t => $tl)<option value="{{ $t }}" @selected($f['type'] === $t)>{{ __($tl) }}</option>@endforeach
                        </select>
                    </label>
                </div>
                <label>{!! __('Опции <small class="muted">(само за „Падащ списък“, по една на ред)</small>') !!}
                    <textarea name="fields[{{ $i }}][options]" rows="3" maxlength="2000">{{ implode("\n", $f['options'] ?? []) }}</textarea>
                </label>
                <label class="check"><input type="checkbox" name="fields[{{ $i }}][required]" value="1" @checked(! empty($f['required']))> {{ __('Задължително поле') }}</label>
                @if($f['key'] !== '')
                    <label class="check"><input type="checkbox" name="fields[{{ $i }}][del]" value="1"> {{ __('Изтрий') }}</label>
                @endif
            </fieldset>
        @endforeach
    </section>
    <button class="btn primary">{{ __('Запази полетата') }}</button>
</form>
