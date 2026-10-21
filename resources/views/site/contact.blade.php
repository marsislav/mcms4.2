@extends('site.layout')

@section('content')
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
        @foreach (($fields ?? []) as $f)
            @php $fn = \App\Support\ContactFields::PREFIX.$f['key']; $fl = \App\Support\ContactFields::label($f); @endphp
            @if($f['type'] === 'checkbox')
                <label class="check cf-{{ $f['type'] }}"><input type="checkbox" name="{{ $fn }}" value="1" @checked(old($fn)) @if(! empty($f['required'])) required @endif> {{ $fl }}</label>
            @else
                <label class="cf-{{ $f['type'] }}">{{ $fl }}@if(! empty($f['required'])) <span aria-hidden="true">*</span>@endif
                    @if($f['type'] === 'textarea')
                        <textarea name="{{ $fn }}" rows="4" maxlength="2000" @if(! empty($f['required'])) required @endif>{{ old($fn) }}</textarea>
                    @elseif($f['type'] === 'select')
                        <select name="{{ $fn }}" @if(! empty($f['required'])) required @endif>
                            <option value="">—</option>
                            @foreach (\App\Support\ContactFields::options($f) as $o)<option value="{{ $o }}" @selected(old($fn) === $o)>{{ $o }}</option>@endforeach
                        </select>
                    @else
                        <input type="{{ $f['type'] }}" name="{{ $fn }}" value="{{ old($fn) }}" maxlength="300" @if($f['type'] === 'number') step="any" @endif @if(! empty($f['required'])) required @endif>
                    @endif
                </label>
            @endif
        @endforeach
        <label>{{ t('contact.message') }}
            <textarea name="message" rows="7" required minlength="5" maxlength="5000">{{ old('message') }}</textarea>
        </label>
        <button class="btn">{{ t('contact.send') }}</button>
    </form>
</article>
@endsection
