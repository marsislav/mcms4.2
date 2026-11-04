<div class="grid-form">
    <label>{!! __('Макс. размер на файл (MB) <small class="muted">(по подразбиране :p1; не надвишава лимита на хостинга — upload_max_filesize)</small>', ['p1' => e(config('mcms.max_upload_mb'))]) !!}
        <input type="number" name="upload_max_mb" min="1" max="64" value="{{ $opt['upload_max_mb'] }}" placeholder="{{ config('mcms.max_upload_mb') }}"></label>
    <label>{!! __('Макс. страна на изображението (px) <small class="muted">по-големите се смаляват</small>') !!}
        <input type="number" name="image_max_px" min="800" max="6000" value="{{ $opt['image_max_px'] }}" placeholder="{{ config('mcms.image_max_px') }}"></label>
    <label>{{ __('Ширина на миниатюрата (px)') }}
        <input type="number" name="thumb_px" min="100" max="1200" value="{{ $opt['thumb_px'] }}" placeholder="{{ config('mcms.thumb_px') }}"></label>
</div>
<p class="muted">{{ __('Празно = стойността по подразбиране. Важи за нови качвания; вече качените файлове не се променят. Автоматичното преобразуване в WebP и качеството му са в „Общи“.') }}</p>
<hr>
<h3>{{ __('Lightbox') }}</h3>
<label class="check"><input type="checkbox" name="lightbox_on" value="1" @checked($opt['lightbox_on'] === '1')> {{ __('Enable the lightbox (galleries and single images open in a full-screen viewer)') }}</label>
<label class="check"><input type="checkbox" name="lightbox_default" value="1" @checked($opt['lightbox_default'] === '1')> {{ __('New images and galleries added in the editor open in the lightbox by default (can be changed per image / gallery)') }}</label>
<label class="check"><input type="checkbox" name="lightbox_all" value="1" @checked($opt['lightbox_all'] === '1')> {{ __('Also open every older image inside articles and pages in the lightbox (unless it is switched off in the editor)') }}</label>
<div class="grid-form">
    <label>{{ __('Transition') }}
        <select name="lightbox_fx">
            @foreach(['fade' => __('Fade'), 'zoom' => __('Zoom'), 'slide' => __('Slide'), 'none' => __('None')] as $k => $label)
                <option value="{{ $k }}" @selected($opt['lightbox_fx'] === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ __('Background') }}
        <select name="lightbox_bg">
            @foreach(['dark' => __('Dark'), 'black' => __('Black'), 'light' => __('Light')] as $k => $label)
                <option value="{{ $k }}" @selected($opt['lightbox_bg'] === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>{{ __('Background opacity (%)') }} <input type="number" name="lightbox_opacity" min="30" max="100" value="{{ $opt['lightbox_opacity'] }}"></label>
    <label>{!! __('Slideshow interval (seconds) <small class="muted">0 = no slideshow button</small>') !!} <input type="number" name="lightbox_auto" min="0" max="30" value="{{ $opt['lightbox_auto'] }}"></label>
</div>
<label class="check"><input type="checkbox" name="lightbox_loop" value="1" @checked($opt['lightbox_loop'] === '1')> {{ __('Loop from the last image back to the first') }}</label>
<label class="check"><input type="checkbox" name="lightbox_count" value="1" @checked($opt['lightbox_count'] === '1')> {{ __('Show the counter (3 / 12)') }}</label>
<label class="check"><input type="checkbox" name="lightbox_caps" value="1" @checked($opt['lightbox_caps'] === '1')> {{ __('Show the caption under the image') }}</label>
<label class="check"><input type="checkbox" name="lightbox_thumbs" value="1" @checked($opt['lightbox_thumbs'] === '1')> {{ __('Show a strip of thumbnails') }}</label>
<label class="check"><input type="checkbox" name="lightbox_zoom" value="1" @checked($opt['lightbox_zoom'] === '1')> {{ __('Allow zooming to full size (click the image)') }}</label>
<label class="check"><input type="checkbox" name="lightbox_backdrop" value="1" @checked($opt['lightbox_backdrop'] === '1')> {{ __('Close when clicking the background') }}</label>
<label class="check"><input type="checkbox" name="lightbox_dl" value="1" @checked($opt['lightbox_dl'] === '1')> {{ __('Show a download button') }}</label>
<p class="muted">{{ __('Keyboard: ← → to browse, Esc to close, Space to start / stop the slideshow. Swipe works on touch screens.') }}</p>
