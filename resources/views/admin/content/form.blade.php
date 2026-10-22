@extends('admin.layout')
@php
    $isPost = $type === 'post';
    $exists = $content->exists;
    $routeBase = $isPost ? 'admin.posts' : 'admin.pages';
    $default = mcms_default_locale();
    $fv = fn ($loc, $field) => old("tr.$loc.$field", optional($content->tr($loc))->{$field});
@endphp
@section('title', ($exists ? __('Редакция: ') : __('Нова ')).($isPost ? __('публикация') : __('страница')))

@section('actions')
    {!! __('<a class="btn" href=":p1">← Към списъка</a>', ['p1' => e(route($routeBase.'.index'))]) !!}
@endsection

@section('content')
@if(! $exists && ! empty($templates))
<form method="get" class="inline-field template-pick" action="{{ route($routeBase.'.create') }}">
    <label>{{ __('Започни от шаблон') }}
        <select name="template"><option value="">—</option>@foreach ($templates as $tpl)<option value="{{ $tpl->id }}" @selected(($pickedTemplate ?? null) === $tpl->id)>{{ $tpl->name }}</option>@endforeach</select>
    </label>
    <button class="btn sm">{{ __('Зареди') }}</button>
</form>
@endif
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
                @php $loc = $lang->code; @endphp
                <div class="tab-panel" data-panel="{{ $loc }}" @if($i !== 0) hidden @endif>
                    <label>{{ __('Заглавие') }} @if($loc === $default)<em class="req">*</em>@endif
                        <input name="tr[{{ $loc }}][title]" value="{{ $fv($loc, 'title') }}" maxlength="190" data-slug-source="{{ $loc }}" @if($loc === $default) required @endif>
                    </label>
                    <label>{!! __('Адрес (slug) <small class="muted">— автоматично, ако е празно</small>') !!}
                        <input name="tr[{{ $loc }}][slug]" value="{{ $fv($loc, 'slug') }}" maxlength="150" data-slug-target="{{ $loc }}" pattern="[a-z0-9\-]*">
                    </label>
                    @if($isPost)
                    <label>{{ __('Кратко описание') }}
                        <textarea name="tr[{{ $loc }}][excerpt]" rows="2" maxlength="1000">{{ $fv($loc, 'excerpt') }}</textarea>
                    </label>
                    @endif
                    <label>{{ __('Съдържание') }}
                        <textarea name="tr[{{ $loc }}][body]" id="body-{{ $loc }}" rows="18" data-editor>{{ $fv($loc, 'body') }}</textarea>
                    </label>
                    <details>
                        <summary>SEO</summary>
                        <label>{{ __('Meta заглавие') }} <input name="tr[{{ $loc }}][meta_title]" value="{{ $fv($loc, 'meta_title') }}" maxlength="190"></label>
                        <label>{{ __('Meta описание') }} <textarea name="tr[{{ $loc }}][meta_description]" rows="2" maxlength="300">{{ $fv($loc, 'meta_description') }}</textarea></label>
                    </details>

                    @if($exists && ($t = $content->tr($loc)) && $t->revisions->isNotEmpty())
                    <details>
                        <summary>{{ __('Версии (:p1)', ['p1' => $t->revisions->count()]) }}</summary>
                        <ul class="revisions">
                            @foreach ($t->revisions->take(10) as $rev)
                                <li>{{ $rev->created_at?->format('d.m.Y H:i') }} — {{ \Illuminate\Support\Str::limit($rev->title, 50) }}
                                    <a class="btn sm" href="{{ route('admin.revisions.compare', ['translation' => $t->id, 'a' => $rev->id, 'b' => 'current']) }}">{{ __('Сравни') }}</a>
                                    <button class="btn sm" form="rev-{{ $rev->id }}">{{ __('Възстанови') }}</button></li>
                            @endforeach
                        </ul>
                    </details>
                    @endif
                </div>
            @endforeach
        </div>

        <aside class="editor-side">
            <section class="card">
                <h3>{{ __('Публикуване') }}</h3>
                <label>{{ __('Статус') }}
                    <select name="status">
                        <option value="draft" @selected(old('status', $content->status) === 'draft')>{{ __('Чернова') }}</option>
                        @if($isPost || auth()->user()->allows('pages'))<option value="pending" @selected(old('status', $content->status) === 'pending')>{{ __('Чака одобрение') }}</option>@endif
                        @if(auth()->user()->allows('publish'))<option value="published" @selected(old('status', $content->status) === 'published')>{{ __('Публикувана') }}</option>@endif
                    </select>
                </label>
                @unless(auth()->user()->allows('publish'))<p class="muted">{{ __('Вашите статии се публикуват след одобрение от редактор или администратор.') }}</p>@endunless
                <label>{!! __('Дата/час на публикуване <small class="muted">(бъдеща = насрочена)</small>') !!}
                    <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $content->publish_at?->format('Y-m-d\TH:i')) }}">
                </label>
                @unless($isPost)
                <label class="check"><input type="checkbox" name="is_home" value="1" @checked(old('is_home', $content->is_home))> {{ __('Начална страница') }}</label>
                <label>{{ __('Подредба') }} <input type="number" name="sort" min="0" max="100000" value="{{ old('sort', $content->sort ?? 0) }}"></label>
                @endunless
                <label class="check"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $content->noindex))> {{ __('Скрий от търсачките (noindex)') }}</label>
                <button class="btn primary block">{{ $exists ? __('Запази промените') : __('Създай') }}</button>
            </section>

            @if(\App\Support\Discussion::ready())
            <section class="card">
                <h3>{{ __('Дискусия') }}</h3>
                @php $co = old('comments_open', $content->comments_open === null ? '' : ($content->comments_open ? '1' : '0')); @endphp
                <label>{{ __('Коментари') }}
                    <select name="comments_open">
                        <option value="" @selected($co === '')>{{ __('По подразбиране (:p1)', ['p1' => \App\Support\Discussion::on($isPost ? 'on_posts' : 'on_pages') ? __('отворени') : __('затворени')]) }}</option>
                        <option value="1" @selected($co === '1')>{{ __('Отворени') }}</option>
                        <option value="0" @selected($co === '0')>{{ __('Затворени') }}</option>
                    </select>
                </label>
                @if($exists && ($n = \App\Models\Comment::where('content_id', $content->id)->whereIn('status', ['approved', 'pending'])->count()))
                    {!! __('<a href=":p1">Коментари: :p2</a>', ['p1' => e(route('admin.comments.index', ['content' => $content->id])), 'p2' => e($n)]) !!}
                @endif
            </section>
            @endif

            @if($isPost)
            <section class="card">
                <h3>{{ __('Категория') }}</h3>
                <select name="category_id">
                    <option value="">{{ __('— без —') }}</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id', $content->category_id) === $cat->id)>{{ $cat->adminName() }}</option>
                    @endforeach
                </select>
            </section>
            @endif

            @if($isPost)
            <section class="card">
                <h3>{{ __('Тагове') }}</h3>
                @if(! ($taxReady ?? false))
                    <p class="muted">{!! __('Нужно е обновяване на базата — вижте <a href=":p1">Тагове</a>.', ['p1' => e(route('admin.tags.index'))]) !!}</p>
                @else
                    <input name="tags" value="{{ old('tags', $tagsValue) }}" maxlength="1500" placeholder="{{ __('php, laravel, новини') }}" autocomplete="off" data-tags-input>
                    {!! __('<small class="muted">Разделяйте със запетая. Новите тагове се създават сами.</small>') !!}
                    @if($allTags->isNotEmpty())
                    <div class="chips" aria-label="{{ __('Съществуващи тагове') }}">
                        @foreach ($allTags as $tg)
                            <button type="button" class="chip" data-tag-add="{{ $tg->adminName() }}">{{ $tg->adminName() }}</button>
                        @endforeach
                    </div>
                    @endif
                @endif
            </section>

            @if($taxReady ?? false)
            <section class="card" data-related>
                <h3>{{ __('Свързани публикации') }}</h3>
                <div class="chips" data-chips>
                    @foreach ((old('related') ? $postChoices->whereIn('id', old('related')) : $relatedSelected) as $rp)
                        <span class="chip sel">{{ $rp->adminTitle() }}<input type="hidden" name="related[]" value="{{ $rp->id }}"><button type="button" data-chip-remove aria-label="{{ __('Махни') }}">×</button></span>
                    @endforeach
                </div>
                <input list="related-list" data-related-input placeholder="{{ __('Търсене по заглавие…') }}" autocomplete="off">
                <datalist id="related-list">
                    @foreach ($postChoices as $rp)<option value="{{ $rp->adminTitle() }} (#{{ $rp->id }})"></option>@endforeach
                </datalist>
                {!! __('<small class="muted">Ръчно избраните се показват първи, после автоматично — със същите тагове и от същата категория.</small>') !!}
            </section>
            @endif
            @endif

            <section class="card">
                <h3>{{ __('Изображение') }}</h3>
                <div class="media-field" data-media-field>
                    <input type="hidden" name="featured_media_id" value="{{ old('featured_media_id', $content->featured_media_id) }}">
                    <img class="media-preview" alt="" @if($content->featured) src="{{ $content->featured->thumbUrl() }}" @else hidden @endif>
                    <div>
                        <button type="button" class="btn sm" data-media-upload>{{ __('⬆ Качи') }}</button>
        <button type="button" class="btn sm" data-media-choose>{{ __('Библиотека') }}</button>
                        <button type="button" class="btn sm" data-media-clear>{{ __('Премахни') }}</button>
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
            <form id="rev-{{ $rev->id }}" method="post" action="{{ route('admin.revisions.restore', $rev) }}" data-confirm="{{ __('Текущото съдържание ще бъде заменено (и запазено като версия). Продължавате ли?') }}">@csrf</form>
        @endforeach
    @endforeach
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset_v('assets/css/grid.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/ckeditor5/ckeditor5.css') }}?v=49.0.0">
@endpush
@push('scripts')
<script src="{{ asset('assets/vendor/ckeditor5/ckeditor5.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>
@if(app()->getLocale() === 'bg')<script src="{{ asset('assets/vendor/ckeditor5/translations/bg.umd.js') }}?v=49.0.0" nonce="{{ csp_nonce() }}" defer></script>@endif
<script src="{{ asset_v('assets/js/editor-fa.js') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset_v('assets/js/editor-grid.js') }}" nonce="{{ csp_nonce() }}" defer></script>
<script src="{{ asset_v('assets/js/editor.js') }}" nonce="{{ csp_nonce() }}" defer></script>
@endpush
