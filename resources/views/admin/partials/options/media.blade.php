<div class="grid-form">
    <label>{!! __('Макс. размер на файл (MB) <small class="muted">(по подразбиране :p1; не надвишава лимита на хостинга — upload_max_filesize)</small>', ['p1' => e(config('mcms.max_upload_mb'))]) !!}
        <input type="number" name="upload_max_mb" min="1" max="64" value="{{ $opt['upload_max_mb'] }}" placeholder="{{ config('mcms.max_upload_mb') }}"></label>
    <label>{!! __('Макс. страна на изображението (px) <small class="muted">по-големите се смаляват</small>') !!}
        <input type="number" name="image_max_px" min="800" max="6000" value="{{ $opt['image_max_px'] }}" placeholder="{{ config('mcms.image_max_px') }}"></label>
    <label>{{ __('Ширина на миниатюрата (px)') }}
        <input type="number" name="thumb_px" min="100" max="1200" value="{{ $opt['thumb_px'] }}" placeholder="{{ config('mcms.thumb_px') }}"></label>
</div>
<p class="muted">{{ __('Празно = стойността по подразбиране. Важи за нови качвания; вече качените файлове не се променят. Автоматичното преобразуване в WebP и качеството му са в „Общи“.') }}</p>
