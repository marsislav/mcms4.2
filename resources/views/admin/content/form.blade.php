@extends('admin.layout')
@php
    $isPost = $type === 'post';
    $exists = $content->exists;
    $routeBase = $isPost ? 'admin.posts' : 'admin.pages';
    $default = mcms_default_locale();
    $fv = fn ($loc, $field) => old("tr.$loc.$field", optional($content->tr($loc))->{$field});
@endphp
@section('title', ($exists ? 'Редакция: ' : 'Нова ').($isPost ? 'публикация' : 'страница'))

@section('actions')
    <a class="btn" href="{{ route($routeBase.'.index') }}">← Към списъка</a>
@endsection

@section('content')
<form method="post" action="{{ $exists ? route($routeBase.'.update', $content) : route($routeBase.'.store') }}" class="editor-form">
    @csrf
    @if($exists) @method('PUT') @endif

    <div class="editor-grid">
        <div class="editor-main">
            <div class="tabs" data-tabs>
                @foreach ($languages as $i => $lang)
                    <button type="button" data-tab="{{ $lang->code }}" @class(['active' => $i === 0])>{{ strtoupper($lang->code) }} <small>{{ $lang->native_name ?: $lang->name }}</small></button>
                @endforeach
            </div>

            @foreach ($languages as $i => $lang)
                @php($loc = $lang->code)
                <div class="tab-panel" data-panel="{{ $loc }}" @if($i !== 0) hidden @endif>
                    <label>Заглавие @if($loc === $default)<em class="req">*</em>@endif
                        <input name="tr[{{ $loc }}][title]" value="{{ $fv($loc, 'title') }}" maxlength="190" data-slug-source="{{ $loc }}" @if($loc === $default) required @endif>
                    </label>
                    <label>Адрес (slug) <small class="muted">— автоматично, ако е празно</small>
                        <input name="tr[{{ $loc }}][slug]" value="{{ $fv($loc, 'slug') }}" maxlength="150" data-slug-target="{{ $loc }}" pattern="[a-z0-9\-]*">
                    </label>
                    @if($isPost)
                    <label>Кратко описание
                        <textarea name="tr[{{ $loc }}][excerpt]" rows="2" maxlength="1000">{{ $fv($loc, 'excerpt') }}</textarea>
                    </label>
                    @endif
                    <label>Съдържание
                        <textarea name="tr[{{ $loc }}][body]" id="body-{{ $loc }}" rows="18" data-editor>{{ $fv($loc, 'body') }}</textarea>
                    </label>
                    <details>
                        <summary>SEO</summary>
                        <label>Meta заглавие <input name="tr[{{ $loc }}][meta_title]" value="{{ $fv($loc, 'meta_title') }}" maxlength="190"></label>
                        <label>Meta описание <textarea name="tr[{{ $loc }}][meta_description]" rows="2" maxlength="300">{{ $fv($loc, 'meta_description') }}</textarea></label>
                    </details>

                    @if($exists && ($t = $content->tr($loc)) && $t->revisions->isNotEmpty())
                    <details>
                        <summary>Версии ({{ $t->revisions->count() }})</summary>
                        <ul class="revisions">
                            @foreach ($t->revisions->take(10) as $rev)
                                <li>{{ $rev->created_at?->format('d.m.Y H:i') }} — {{ \Illuminate\Support\Str::limit($rev->title, 50) }}
                                    <button class="btn sm" form="rev-{{ $rev->id }}">Възстанови</button></li>
                            @endforeach
                        </ul>
                    </details>
                    @endif
                </div>
            @endforeach
        </div>

        <aside class="editor-side">
            <section class="card">
                <h3>Публикуване</h3>
                <label>Статус
                    <select name="status">
                        <option value="draft" @selected(old('status', $content->status) === 'draft')>Чернова</option>
                        <option value="published" @selected(old('status', $content->status) === 'published')>Публикувана</option>
                    </select>
                </label>
                <label>Дата/час на публикуване <small class="muted">(бъдеща = насрочена)</small>
                    <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $content->publish_at?->format('Y-m-d\TH:i')) }}">
                </label>
                @unless($isPost)
                <label class="check"><input type="checkbox" name="is_home" value="1" @checked(old('is_home', $content->is_home))> Начална страница</label>
                <label>Подредба <input type="number" name="sort" min="0" max="100000" value="{{ old('sort', $content->sort ?? 0) }}"></label>
                @endunless
                <label class="check"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $content->noindex))> Скрий от търсачките (noindex)</label>
                <button class="btn primary block">{{ $exists ? 'Запази промените' : 'Създай' }}</button>
            </section>

            @if($isPost)
            <section class="card">
                <h3>Категория</h3>
                <select name="category_id">
                    <option value="">— без —</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id', $content->category_id) === $cat->id)>{{ $cat->adminName() }}</option>
                    @endforeach
                </select>
            </section>
            @endif

            @if($isPost)
            <section class="card">
                <h3>Тагове</h3>
                @if(! ($taxReady ?? false))
                    <p class="muted">Нужно е обновяване на базата — вижте <a href="{{ route('admin.tags.index') }}">Тагове</a>.</p>
                @else
                    <input name="tags" value="{{ old('tags', $tagsValue) }}" maxlength="1500" placeholder="php, laravel, новини" autocomplete="off" data-tags-input>
                    <small class="muted">Разделяйте със запетая. Новите тагове се създават сами.</small>
                    @if($allTags->isNotEmpty())
                    <div class="chips" aria-label="Съществуващи тагове">
                        @foreach ($allTags as $tg)
                            <button type="button" class="chip" data-tag-add="{{ $tg->adminName() }}">{{ $tg->adminName() }}</button>
                        @endforeach
                    </div>
                    @endif
                @endif
            </section>

            @if($taxReady ?? false)
            <section class="card" data-related>
                <h3>Свързани публикации</h3>
                <div class="chips" data-chips>
                    @foreach ((old('related') ? $postChoices->whereIn('id', old('related')) : $relatedSelected) as $rp)
                        <span class="chip sel">{{ $rp->adminTitle() }}<input type="hidden" name="related[]" value="{{ $rp->id }}"><button type="button" data-chip-remove aria-label="Махни">×</button></span>
                    @endforeach
                </div>
                <input list="related-list" data-related-input placeholder="Търсене по заглавие…" autocomplete="off">
                <datalist id="related-list">
                    @foreach ($postChoices as $rp)<option value="{{ $rp->adminTitle() }} (#{{ $rp->id }})"></option>@endforeach
                </datalist>
                <small class="muted">Ръчно избраните се показват първи, после автоматично — със същите тагове и от същата категория.</small>
            </section>
            @endif
            @endif

            <section class="card">
                <h3>Изображение</h3>
                <div class="media-field" data-media-field>
                    <input type="hidden" name="featured_media_id" value="{{ old('featured_media_id', $content->featured_media_id) }}">
                    <img class="media-preview" alt="" @if($content->featured) src="{{ $content->featured->thumbUrl() }}" @else hidden @endif>
                    <div>
                        <button type="button" class="btn sm" data-media-choose>Избери</button>
                        <button type="button" class="btn sm" data-media-clear>Премахни</button>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</form>

{{-- Revision restore forms live outside the main form (nested forms are invalid HTML) --}}
@if($exists)
    @foreach ($content->translations as $t)
        @foreach ($t->revisions->take(10) as $rev)
            <form id="rev-{{ $rev->id }}" method="post" action="{{ route('admin.revisions.restore', $rev) }}" data-confirm="Текущото съдържание ще бъде заменено (и запазено като версия). Продължавате ли?">@csrf</form>
        @endforeach
    @endforeach
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/grid.css') }}?v={{ config('mcms.version') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/ckeditor5/ckeditor5.css') }}?v=49.0.0">
@endpush
@push('scripts')
<script src="{{ asset('assets/vendor/ckeditor5/ckeditor5.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset('assets/vendor/ckeditor5/translations/bg.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset('assets/js/editor-fa.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset('assets/js/editor-grid.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset('assets/js/editor.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
@endpush
