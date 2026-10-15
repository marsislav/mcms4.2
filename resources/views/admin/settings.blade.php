@extends('admin.layout')
@section('title', 'Settings')

@section('content')
@php
    $s = fn ($k, $d = '') => old($k, setting($k, $d));
    $media = fn ($name, $label, $m) => view('admin.partials.media-field', compact('name', 'label', 'm'));
@endphp

<form method="post" action="{{ route('admin.settings.update') }}" class="settings-form">
    @csrf @method('PUT')

    <section class="card">
        <h2>General</h2>
        <div class="grid-form">
            <label>Site name <input name="site_name" value="{{ $s('site_name') }}" required maxlength="100"></label>
            <label>Contact email (receives messages) <input type="email" name="contact_email" value="{{ $s('contact_email') }}" maxlength="190"></label>
            <label>Posts per page <input type="number" name="posts_per_page" value="{{ $s('posts_per_page', 9) }}" min="1" max="50" required></label>
        </div>
        @foreach (['tagline' => 'Tagline', 'meta_description' => 'Default meta description', 'footer_text' => 'Footer text'] as $key => $label)
            <div class="grid-form">
                @foreach ($languages as $l)
                    <label>{{ $label }} ({{ strtoupper($l->code) }}) <input name="{{ $key }}[{{ $l->code }}]" value="{{ old("$key.$l->code", setting("$key.$l->code")) }}" maxlength="300"></label>
                @endforeach
            </div>
        @endforeach
    </section>

    <section class="card">
        <h2>Images</h2>
        <div class="grid-form">
            {!! $media('logo_media_id', 'Logo', $logo) !!}
            {!! $media('favicon_media_id', 'Favicon (PNG)', $favicon) !!}
            {!! $media('og_media_id', 'Sharing image (OG)', $ogImage) !!}
        </div>
    </section>

    <section class="card">
        <h2>Social networks</h2>
        <div class="grid-form">
            @foreach (['facebook', 'instagram', 'linkedin', 'youtube'] as $n)
                <label>{{ ucfirst($n) }} <input type="url" name="social_{{ $n }}" value="{{ $s('social_'.$n) }}" maxlength="200" placeholder="https://"></label>
            @endforeach
        </div>
    </section>

    <section class="card">
        <h2>Security and maintenance</h2>
        <label class="check"><input type="checkbox" name="require_2fa_admin" value="1" @checked($s('require_2fa_admin') === '1')> Require 2FA for administrators</label>
        <label class="check"><input type="checkbox" name="maintenance_mode" value="1" @checked($s('maintenance_mode') === '1')> Maintenance mode (the public site is unavailable; the admin panel keeps working)</label>
        <label>Extra lines for robots.txt <textarea name="robots_extra" rows="3" maxlength="1000">{{ $s('robots_extra') }}</textarea></label>
    </section>

    <section class="card">
        <h2>Outgoing mail (SMTP)</h2>
        <p class="muted">If left empty, the configuration from .env is used. The password is stored encrypted.</p>
        <div class="grid-form">
            <label>Host <input name="smtp_host" value="{{ $s('smtp_host') }}" maxlength="190" placeholder="mail.example.com"></label>
            <label>Port <input type="number" name="smtp_port" value="{{ $s('smtp_port', 587) }}" min="1" max="65535"></label>
            <label>Encryption
                <select name="smtp_encryption">
                    <option value="tls" @selected($s('smtp_encryption', 'tls') === 'tls')>STARTTLS (587)</option>
                    <option value="ssl" @selected($s('smtp_encryption') === 'ssl')>SSL/TLS (465)</option>
                </select>
            </label>
            <label>Username <input name="smtp_username" value="{{ $s('smtp_username') }}" maxlength="190" autocomplete="off"></label>
            <label>Password <input type="password" name="smtp_password" placeholder="{{ setting('smtp_password') ? '•••••••• (saved)' : '' }}" autocomplete="new-password" maxlength="300"></label>
            <label>Sender (email) <input type="email" name="mail_from_address" value="{{ $s('mail_from_address') }}" maxlength="190"></label>
            <label>Sender (name) <input name="mail_from_name" value="{{ $s('mail_from_name') }}" maxlength="100"></label>
        </div>
        @if(setting('smtp_password'))<label class="check"><input type="checkbox" name="smtp_password_clear" value="1"> Delete the saved password</label>@endif
    </section>

    <button class="btn primary">Save settings</button>
</form>

<form method="post" action="{{ route('admin.settings.mail-test') }}" class="inline-form">
    @csrf <button class="btn">Send me a test email</button>
</form>
@endsection
