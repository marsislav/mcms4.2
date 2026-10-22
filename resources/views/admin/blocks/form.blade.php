@extends('admin.layout')
@section('title', $row ? __('Редакция') : ($kind === 'template' ? __('Нов шаблон') : __('Нов блок')))

@section('content')
@php
    $def = mcms_default_locale();
    $i18n = $row && $row->i18n ? (json_decode($row->i18n, true) ?: []) : [];
@endphp
<form method="post" action="{{ $row ? route('admin.blocks.update', $row->id) : route('admin.blocks.store') }}" class="settings-form">
    @csrf @if($row) @method('PUT') @endif
    <input type="hidden" name="kind" value="{{ $kind }}">
    <section class="card">
        <div class="grid-form">
            <label>{{ __('Име') }} <input name="name" required maxlength="120" value="{{ old('name', $row->name ?? '') }}"></label>
            @if($kind === 'block')
                <label>{!! __('Код (slug) <small class="muted">за <code>[block код]</code></small>') !!} <input name="slug" maxlength="80" pattern="[a-z0-9\-]*" value="{{ old('slug', $row->slug ?? '') }}"></label>
            @else
                <label>{{ __('Важи за') }}
                    <select name="applies_to">@foreach (\App\Support\Blocks::APPLIES as $k => $lbl)<option value="{{ $k }}" @selected(old('applies_to', $row->applies_to ?? 'any') === $k)>{{ __($lbl) }}</option>@endforeach</select>
                </label>
            @endif
        </div>
        <label>{{ __('Съдържание') }} ({{ strtoupper($def) }})
            <textarea name="body" rows="14" data-editor>{{ old('body', $row->body ?? '') }}</textarea>
        </label>
        @foreach (mcms_locales() as $l)
            @if($l !== $def)
            <label>{{ __('Съдържание') }} ({{ strtoupper($l) }}) <small class="muted">{{ __('празно = използва се основният език') }}</small>
                <textarea name="i18n[{{ $l }}]" rows="10" data-editor>{{ old('i18n.'.$l, $i18n[$l] ?? '') }}</textarea>
            </label>
            @endif
        @endforeach
    </section>
    <button class="btn primary">{{ __('Запази') }}</button>
    <a class="btn" href="{{ route('admin.blocks.index') }}">{{ __('Назад') }}</a>
</form>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/ckeditor5/ckeditor5.css') }}?v=49.0.0">
@endpush
@push('scripts')
<script src="{{ asset('assets/vendor/ckeditor5/ckeditor5.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>
@if(app()->getLocale() === 'bg')<script src="{{ asset('assets/vendor/ckeditor5/translations/bg.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>@endif
<script src="{{ asset_v('assets/js/editor-fa.js') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset_v('assets/js/editor.js') }}" nonce="{{ csp_nonce() }}" defer></script>
@endpush
