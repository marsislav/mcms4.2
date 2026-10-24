@php $kind = $kind ?? 'image'; @endphp
<div class="media-field" data-media-field data-media-kind="{{ $kind }}">
    <span class="label">{{ $label }}</span>
    <input type="hidden" name="{{ $name }}" value="{{ old($name, $m?->id) }}">
    @if($kind === 'video')
        <video class="media-preview" muted playsinline preload="metadata" @if($m) src="{{ $m->url() }}#t=0.1" @else hidden @endif></video>
    @else
        <img class="media-preview" alt="" @if($m) src="{{ $m->thumbUrl() }}" @else hidden @endif>
    @endif
    <div>
        <button type="button" class="btn sm" data-media-upload>{{ __('⬆ Качи') }}</button>
        <button type="button" class="btn sm" data-media-choose>{{ __('Библиотека') }}</button>
        <button type="button" class="btn sm" data-media-clear>{{ __('Премахни') }}</button>
    </div>
</div>
