@extends('admin.layout')
@section('title', __('Медия'))

@section('content')
<section class="card">
    <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="upload-form">
        @csrf
        <input type="file" name="files[]" multiple required accept="image/jpeg,image/png,image/webp,image/gif,application/pdf">
        <button class="btn primary">{{ __('Качи') }}</button>
        {!! __('<small class="muted">JPG, PNG, WebP, GIF, PDF · до :p1 MB · изображенията се обработват и почистват от метаданни</small>', ['p1' => e(config('mcms.max_upload_mb'))]) !!}
    </form>
</section>

<form class="filters" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Търсене по име…') }}" maxlength="80">
    <button class="btn">{{ __('Търси') }}</button>
</form>

<div class="media-grid">
    @forelse ($items as $m)
        <figure class="media-item">
            @if($m->isImage())
                <a href="{{ $m->url() }}" target="_blank" rel="noopener"><img src="{{ $m->thumbUrl() }}" alt="{{ $m->alt }}" loading="lazy"></a>
            @else
                <a class="file-ico" href="{{ $m->url() }}" target="_blank" rel="noopener">PDF</a>
            @endif
            <figcaption>
                <strong title="{{ $m->original_name }}">{{ \Illuminate\Support\Str::limit($m->original_name, 28) }}</strong>
                <small class="muted">{{ $m->width ? $m->width.'×'.$m->height.' · ' : '' }}{{ number_format($m->size / 1024, 0) }} KB</small>
                <input class="copy-url" readonly value="{{ $m->url() }}" aria-label="URL">
                <form method="post" action="{{ route('admin.media.update', $m) }}" class="inline-form">
                    @csrf @method('PUT')
                    <input name="alt" value="{{ $m->alt }}" placeholder="{{ __('Alt текст') }}" maxlength="190">
                    <button class="btn sm">✓</button>
                </form>
                <form method="post" action="{{ route('admin.media.destroy', $m) }}" data-confirm="{{ __('Да изтрия ли файла?') }}">
                    @csrf @method('DELETE')<button class="btn sm danger">{{ __('Изтрий') }}</button>
                </form>
            </figcaption>
        </figure>
    @empty
        <p class="muted">{{ __('Все още няма файлове.') }}</p>
    @endforelse
</div>
{{ $items->links() }}
@endsection
