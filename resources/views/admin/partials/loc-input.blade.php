{{-- Per-language option text. Params: $key (option key), $label, optional $rows (textarea when > 1), $max, $ph (placeholder). Stored as opt.<key>.<locale>. --}}
@php
    $rows = $rows ?? 1;
    $max = $max ?? 200;
    $ph = $ph ?? '';
@endphp
<fieldset class="loc-field">
    <legend>{{ $label }}</legend>
    @foreach (mcms_locales() as $l)
        <label class="inline-field"><span class="muted">{{ strtoupper($l) }}</span>
            @if($rows > 1)
                <textarea name="{{ $key }}[{{ $l }}]" rows="{{ $rows }}" maxlength="{{ $max }}" placeholder="{{ $ph }}">{{ old($key.'.'.$l, \App\Support\Options::get($key.'.'.$l)) }}</textarea>
            @else
                <input name="{{ $key }}[{{ $l }}]" maxlength="{{ $max }}" placeholder="{{ $ph }}" value="{{ old($key.'.'.$l, \App\Support\Options::get($key.'.'.$l)) }}">
            @endif
        </label>
    @endforeach
</fieldset>
