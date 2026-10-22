@extends('site.layout')

@section('kind', 'page')

@section('content')
@include('site.partials.breadcrumbs')
<article class="entry narrow">
    <header><h1>{{ t('contact.title') }}</h1></header>
    <p class="lead">{{ t('contact.intro') }}</p>

    @if(session('contact_ok'))<div class="notice ok" role="status">{{ session('contact_ok') }}</div>@endif
    @if(session('contact_error') || $errors->any())
        <div class="notice err" role="alert">{{ session('contact_error') ?: t('contact.error') }}</div>
    @endif

    <form class="contact-form" method="post" action="{{ lroute('contact.send') }}">
        @csrf
        <input type="hidden" name="ts" value="{{ $ts }}">
        {{-- honeypot: hidden from humans via CSS class, bots tend to fill it --}}
        <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <label>{{ t('contact.name') }}
            <input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
        </label>
        <label>{{ t('contact.email') }}
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">
        </label>
        <label>{{ t('contact.subject') }}
            <input name="subject" value="{{ old('subject') }}" maxlength="150">
        </label>
        @if(! empty($fields))<div class="cf-grid">@foreach ($fields as $f)@include('site.partials.contact-field', ['f' => $f])@endforeach</div>@endif
        <label>{{ t('contact.message') }}
            <textarea name="message" rows="7" required minlength="5" maxlength="5000">{{ old('message') }}</textarea>
        </label>
        <button class="btn">{{ t('contact.send') }}</button>
    </form>
</article>
@endsection
