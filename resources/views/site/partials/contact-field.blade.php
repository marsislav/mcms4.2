{{-- One custom contact-form field (App\Support\ContactFields). Param: $f. --}}
@php
    $f = \App\Support\ContactFields::normalize($f);
    $fn = \App\Support\ContactFields::PREFIX.$f['key'];
    $fl = \App\Support\ContactFields::label($f);
    $ph = \App\Support\ContactFields::text($f, 'placeholder');
    $help = \App\Support\ContactFields::text($f, 'help');
    $req = ! empty($f['required']);
    $def = old($fn, $f['default']);
    $opts = \App\Support\ContactFields::options($f);
    $wrap = 'cf cf-'.$f['type'].' cf-w-'.$f['width'].($f['class'] !== '' ? ' '.$f['class'] : '');
    $hid = 'help-'.$f['key'];
@endphp
@if($f['type'] === 'heading')
    <div class="{{ $wrap }}"><h2 class="cf-heading">{{ $fl }}</h2>@if($help !== '')<p class="muted">{{ $help }}</p>@endif</div>
@elseif($f['type'] === 'checkbox')
    <div class="{{ $wrap }}">
        <label class="check"><input type="checkbox" name="{{ $fn }}" value="1" @checked($def) @if($req) required @endif @if($help !== '') aria-describedby="{{ $hid }}" @endif> {{ $fl }}</label>
        @if($help !== '')<small id="{{ $hid }}" class="muted">{{ $help }}</small>@endif
    </div>
@elseif(in_array($f['type'], ['radio', 'checkboxes'], true))
    @php $picked = (array) (is_array($def) ? $def : preg_split('/\R/', (string) $def)); @endphp
    <fieldset class="{{ $wrap }}" @if($help !== '') aria-describedby="{{ $hid }}" @endif>
        <legend>{{ $fl }}@if($req) <span aria-hidden="true">*</span>@endif</legend>
        @foreach ($opts as $oi => $o)
            <label class="check">
                @if($f['type'] === 'radio')<input type="radio" name="{{ $fn }}" value="{{ $o }}" @checked(in_array($o, $picked, true)) @if($req && $oi === 0) required @endif>
                @else<input type="checkbox" name="{{ $fn }}[]" value="{{ $o }}" @checked(in_array($o, $picked, true))>@endif
                {{ $o }}
            </label>
        @endforeach
        @if($help !== '')<small id="{{ $hid }}" class="muted">{{ $help }}</small>@endif
    </fieldset>
@else
    <div class="{{ $wrap }}">
        <label>{{ $fl }}@if($req) <span aria-hidden="true">*</span>@endif
            @if($f['type'] === 'textarea')
                <textarea name="{{ $fn }}" rows="{{ (int) $f['rows'] }}" maxlength="{{ $f['maxlen'] ?: 2000 }}" @if($f['minlen']) minlength="{{ $f['minlen'] }}" @endif @if($ph !== '') placeholder="{{ $ph }}" @endif @if($req) required @endif @if($help !== '') aria-describedby="{{ $hid }}" @endif>{{ $def }}</textarea>
            @elseif($f['type'] === 'select')
                <select name="{{ $fn }}" @if($req) required @endif @if($help !== '') aria-describedby="{{ $hid }}" @endif>
                    <option value="">—</option>
                    @foreach ($opts as $o)<option value="{{ $o }}" @selected($def === $o)>{{ $o }}</option>@endforeach
                </select>
            @else
                <input type="{{ $f['type'] }}" name="{{ $fn }}" value="{{ is_array($def) ? '' : $def }}"
                    @if(in_array($f['type'], ['text'], true)) maxlength="{{ $f['maxlen'] ?: 200 }}" @if($f['minlen']) minlength="{{ $f['minlen'] }}" @endif @elseif($f['type'] !== 'number' && $f['type'] !== 'date' && $f['type'] !== 'time') maxlength="300" @endif
                    @if($f['type'] === 'number') step="any" @if($f['min'] !== '') min="{{ $f['min'] }}" @endif @if($f['max'] !== '') max="{{ $f['max'] }}" @endif @endif
                    @if($f['type'] === 'date') @if($f['min'] !== '') min="{{ $f['min'] }}" @endif @if($f['max'] !== '') max="{{ $f['max'] }}" @endif @endif
                    @if($ph !== '') placeholder="{{ $ph }}" @endif @if($req) required @endif @if($help !== '') aria-describedby="{{ $hid }}" @endif>
            @endif
        </label>
        @if($help !== '')<small id="{{ $hid }}" class="muted">{{ $help }}</small>@endif
    </div>
@endif
