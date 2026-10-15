@extends('admin.layout')
@php
    $isPost = $type === 'post';
    $exists = $content->exists;
    $routeBase = $isPost ? 'admin.posts' : 'admin.pages';
    $default = mcms_default_locale();
    $fv = fn ($loc, $field) => old("tr.$loc.$field", optional($content->tr($loc))->{$field});
@endphp
@section('title', ($exists ? 'Edit: ' : 'New ').($isPost ? 'post' : 'page'))

@section('actions')
    <a class="btn" href="{{ route($routeBase.'.index') }}">← Back to list</a>
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
                    <label>Title @if($loc === $default)<em class="req">*</em>@endif
                        <input name="tr[{{ $loc }}][title]" value="{{ $fv($loc, 'title') }}" maxlength="190" data-slug-source="{{ $loc }}" @if($loc === $default) required @endif>
                    </label>
                    <label>Address (slug) <small class="muted">— generated automatically if empty</small>
                        <input name="tr[{{ $loc }}][slug]" value="{{ $fv($loc, 'slug') }}" maxlength="150" data-slug-target="{{ $loc }}" pattern="[a-z0-9\-]*">
                    </label>
                    @if($isPost)
                    <label>Excerpt
                        <textarea name="tr[{{ $loc }}][excerpt]" rows="2" maxlength="1000">{{ $fv($loc, 'excerpt') }}</textarea>
                    </label>
                    @endif
                    <label>Content
                        <textarea name="tr[{{ $loc }}][body]" id="body-{{ $loc }}" rows="18" data-editor>{{ $fv($loc, 'body') }}</textarea>
                    </label>
                    <details>
                        <summary>SEO</summary>
                        <label>Meta title <input name="tr[{{ $loc }}][meta_title]" value="{{ $fv($loc, 'meta_title') }}" maxlength="190"></label>
                        <label>Meta description <textarea name="tr[{{ $loc }}][meta_description]" rows="2" maxlength="300">{{ $fv($loc, 'meta_description') }}</textarea></label>
                    </details>

                    @if($exists && ($t = $content->tr($loc)) && $t->revisions->isNotEmpty())
                    <details>
                        <summary>Revisions ({{ $t->revisions->count() }})</summary>
                        <ul class="revisions">
                            @foreach ($t->revisions->take(10) as $rev)
                                <li>{{ $rev->created_at?->format('d.m.Y H:i') }} — {{ \Illuminate\Support\Str::limit($rev->title, 50) }}
                                    <button class="btn sm" form="rev-{{ $rev->id }}">Restore</button></li>
                            @endforeach
                        </ul>
                    </details>
                    @endif
                </div>
            @endforeach
        </div>

        <aside class="editor-side">
            <section class="card">
                <h3>Publishing</h3>
                <label>Status
                    <select name="status">
                        <option value="draft" @selected(old('status', $content->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $content->status) === 'published')>Published</option>
                    </select>
                </label>
                <label>Publish date/time <small class="muted">(a future date = scheduled)</small>
                    <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $content->publish_at?->format('Y-m-d\TH:i')) }}">
                </label>
                @unless($isPost)
                <label class="check"><input type="checkbox" name="is_home" value="1" @checked(old('is_home', $content->is_home))> Home page</label>
                <label>Order <input type="number" name="sort" min="0" max="100000" value="{{ old('sort', $content->sort ?? 0) }}"></label>
                @endunless
                <label class="check"><input type="checkbox" name="noindex" value="1" @checked(old('noindex', $content->noindex))> Hide from search engines (noindex)</label>
                <button class="btn primary block">{{ $exists ? 'Save changes' : 'Create' }}</button>
            </section>

            @if($isPost)
            <section class="card">
                <h3>Category</h3>
                <select name="category_id">
                    <option value="">— none —</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id', $content->category_id) === $cat->id)>{{ $cat->adminName() }}</option>
                    @endforeach
                </select>
            </section>
            @endif

            <section class="card">
                <h3>Image</h3>
                <div class="media-field" data-media-field>
                    <input type="hidden" name="featured_media_id" value="{{ old('featured_media_id', $content->featured_media_id) }}">
                    <img class="media-preview" alt="" @if($content->featured) src="{{ $content->featured->thumbUrl() }}" @else hidden @endif>
                    <div>
                        <button type="button" class="btn sm" data-media-choose>Choose</button>
                        <button type="button" class="btn sm" data-media-clear>Remove</button>
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
            <form id="rev-{{ $rev->id }}" method="post" action="{{ route('admin.revisions.restore', $rev) }}" data-confirm="The current content will be replaced (and saved as a revision). Continue?">@csrf</form>
        @endforeach
    @endforeach
@endif
@endsection
