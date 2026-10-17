@extends('admin.layout')
@section('title', 'Настройки')

@section('content')
@php
    $s = fn ($k, $d = '') => old($k, setting($k, $d));
    $media = fn ($name, $label, $m) => view('admin.partials.media-field', compact('name', 'label', 'm'));
@endphp

<div class="tabs" data-stabs role="tablist">
    <button type="button" class="active" data-stab="general" role="tab">Общи</button>
    <button type="button" data-stab="design" role="tab">Дизайн</button>
    <button type="button" data-stab="discussion" role="tab">Дискусии</button>
    <button type="button" data-stab="footer" role="tab">Футър</button>
    <button type="button" data-stab="sidebar" role="tab">Сайдбар</button>
</div>

<div data-spanel="general">
<form method="post" action="{{ route('admin.settings.update') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>Общи</h2>
        <div class="grid-form">
            <label>Име на сайта <input name="site_name" value="{{ $s('site_name') }}" required maxlength="100"></label>
            <label>Имейл за контакт (получава съобщения) <input type="email" name="contact_email" value="{{ $s('contact_email') }}" maxlength="190"></label>
            <label>Публикации на страница <input type="number" name="posts_per_page" value="{{ $s('posts_per_page', 9) }}" min="1" max="50" required></label>
            <label>Свързани публикации под статия <small class="muted">(0 = изключено)</small> <input type="number" name="related_count" value="{{ $s('related_count', 3) }}" min="0" max="12" required></label>
        </div>
        @foreach (['tagline' => 'Слоган', 'meta_description' => 'Meta описание по подразбиране', 'footer_text' => 'Текст във футъра'] as $key => $label)
            <div class="grid-form">
                @foreach ($languages as $l)
                    <label>{{ $label }} ({{ strtoupper($l->code) }}) <input name="{{ $key }}[{{ $l->code }}]" value="{{ old("$key.$l->code", setting("$key.$l->code")) }}" maxlength="300"></label>
                @endforeach
            </div>
        @endforeach
    </section>

    <section class="card">
        <h2>Изображения</h2>
        <div class="grid-form">
            {!! $media('logo_media_id', 'Лого', $logo) !!}
            {!! $media('favicon_media_id', 'Favicon (PNG)', $favicon) !!}
            {!! $media('og_media_id', 'Изображение за споделяне (OG)', $ogImage) !!}
        </div>
        <label class="check"><input type="checkbox" name="webp_convert" value="1" @checked($s('webp_convert', '1') !== '0')> Преобразувай качените снимки (JPG, PNG, статичен GIF) във WebP @unless(function_exists('imagewebp'))<strong>— GD на сървъра няма WebP поддръжка, функцията не работи</strong>@endunless</label>
        <label>Качество на WebP за JPG <small class="muted">40–100; PNG и GIF се записват без загуба</small>
            <input type="number" name="webp_quality" min="40" max="100" value="{{ $s('webp_quality', 82) }}" class="narrow"></label>
    </section>

    <section class="card">
        <h2>Социални мрежи</h2>
        <div class="grid-form">
            @foreach (['facebook', 'instagram', 'linkedin', 'youtube'] as $n)
                <label>{{ ucfirst($n) }} <input type="url" name="social_{{ $n }}" value="{{ $s('social_'.$n) }}" maxlength="200" placeholder="https://"></label>
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2>Сигурност и поддръжка</h2>
        <label class="check"><input type="checkbox" name="maintenance_mode" value="1" @checked($s('maintenance_mode') === '1')> Режим „профилактика“ (публичният сайт е недостъпен; панелът работи)</label>
        <label>Допълнителни редове за robots.txt <textarea name="robots_extra" rows="3" maxlength="1000">{{ $s('robots_extra') }}</textarea></label>
    </section>

    <section class="card">
        <h2>Изпращане на поща (SMTP)</h2>
        <p class="muted">Ако е празно, се използва конфигурацията от .env. Паролата се съхранява криптирана.</p>
        <div class="grid-form">
            <label>Хост <input name="smtp_host" value="{{ $s('smtp_host') }}" maxlength="190" placeholder="mail.example.com"></label>
            <label>Порт <input type="number" name="smtp_port" value="{{ $s('smtp_port', 587) }}" min="1" max="65535"></label>
            <label>Криптиране
                <select name="smtp_encryption">
                    <option value="tls" @selected($s('smtp_encryption', 'tls') === 'tls')>STARTTLS (587)</option>
                    <option value="ssl" @selected($s('smtp_encryption') === 'ssl')>SSL/TLS (465)</option>
                </select>
            </label>
            <label>Потребител <input name="smtp_username" value="{{ $s('smtp_username') }}" maxlength="190" autocomplete="off"></label>
            <label>Парола <input type="password" name="smtp_password" placeholder="{{ setting('smtp_password') ? '•••••••• (запазена)' : '' }}" autocomplete="new-password" maxlength="300"></label>
            <label>Подател (имейл) <input type="email" name="mail_from_address" value="{{ $s('mail_from_address') }}" maxlength="190"></label>
            <label>Подател (име) <input name="mail_from_name" value="{{ $s('mail_from_name') }}" maxlength="100"></label>
        </div>
        @if(setting('smtp_password'))<label class="check"><input type="checkbox" name="smtp_password_clear" value="1"> Изтрий запазената парола</label>@endif
    </section>

    <button class="btn primary">Запази настройките</button>
</form>

<form method="post" action="{{ route('admin.settings.mail-test') }}" class="inline-form">
    @csrf <button class="btn">Изпрати тестово писмо до мен</button>
</form>
</div>

<div data-spanel="design">
    @include('admin.partials.design-form')
</div>

<div data-spanel="discussion">
    @include('admin.partials.discussion-form')
</div>

<div data-spanel="footer">
    @include('admin.partials.footer-form')
</div>

<div data-spanel="sidebar">
    @include('admin.partials.sidebar-form')
</div>

{{-- one blank card per widget type; admin.js clones it (placeholders __I__ = unique index, __C__ = column) --}}
<div hidden>
    @foreach (\App\Support\Widgets::TYPES as $type => $label)
        <template data-widget-tpl="{{ $type }}">@include('admin.partials.widget-card', ['col' => '__C__', 'idx' => '__I__', 'w' => ['type' => $type, 'id' => '', 'hide_empty' => true, 'show_count' => true, 'count' => 5, 'limit' => 30], 'new' => true])</template>
    @endforeach
</div>
@push('scripts')
<script src="{{ asset('assets/js/editor-fa.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
@endpush
@endsection
