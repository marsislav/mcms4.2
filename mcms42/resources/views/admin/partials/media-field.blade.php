<div class="media-field" data-media-field>
    <span class="label">{{ $label }}</span>
    <input type="hidden" name="{{ $name }}" value="{{ old($name, $m?->id) }}">
    <img class="media-preview" alt="" @if($m) src="{{ $m->thumbUrl() }}" @else hidden @endif>
    <div>
        <button type="button" class="btn sm" data-media-upload>⬆ Качи</button>
        <button type="button" class="btn sm" data-media-choose>Библиотека</button>
        <button type="button" class="btn sm" data-media-clear>Премахни</button>
    </div>
</div>
