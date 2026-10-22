{{-- GDPR cookie consent. Texts/options: Settings > Cookies (GDPR). Choice is stored client-side in the "mcms_consent" cookie
     (necessary|analytics|marketing flags); gated scripts are re-injected by site.js only after consent. --}}
@if(\App\Support\Options::on('cc_on'))
@php
    $ccAnalytics = \App\Support\Options::on('cc_analytics');
    $ccMarketing = \App\Support\Options::on('cc_marketing');
    $ccPolicy = \App\Support\Options::get('cc_policy_url');
@endphp
<div class="cc cc-{{ \App\Support\Options::get('cc_position') }}" id="cc-banner" role="dialog" aria-live="polite" aria-labelledby="cc-title" hidden
     data-cc-days="{{ (int) \App\Support\Options::get('cc_days') }}">
    <div class="cc-box">
        <h2 id="cc-title">{{ \App\Support\Options::loc('cc_title', __('Бисквитки')) }}</h2>
        <p>{{ \App\Support\Options::loc('cc_text', __('Използваме бисквитки, за да работи сайтът и — с вашето съгласие — за статистика и маркетинг.')) }}
            @if($ccPolicy !== '')<a href="{{ $ccPolicy }}">{{ \App\Support\Options::loc('cc_policy_label', __('Политика за поверителност')) }}</a>@endif</p>
        @if($ccAnalytics || $ccMarketing)
        <div class="cc-prefs" hidden>
            <label class="cc-row"><input type="checkbox" checked disabled> {{ __('Необходими') }}</label>
            @if($ccAnalytics)<label class="cc-row"><input type="checkbox" data-cc-cat="analytics"> {{ __('Статистика') }}</label>@endif
            @if($ccMarketing)<label class="cc-row"><input type="checkbox" data-cc-cat="marketing"> {{ __('Маркетинг') }}</label>@endif
            <button type="button" class="btn" data-cc="save">{{ __('Запази избора') }}</button>
        </div>
        @endif
        <div class="cc-actions">
            <button type="button" class="btn primary" data-cc="accept">{{ \App\Support\Options::loc('cc_accept', __('Приемам всички')) }}</button>
            <button type="button" class="btn" data-cc="reject">{{ \App\Support\Options::loc('cc_reject', __('Само необходимите')) }}</button>
            @if($ccAnalytics || $ccMarketing)<button type="button" class="btn link" data-cc="prefs">{{ \App\Support\Options::loc('cc_settings', __('Настройки')) }}</button>@endif
        </div>
    </div>
</div>
@if(\App\Support\Options::on('cc_reopen'))
<button type="button" class="cc-reopen" data-cc="open" hidden aria-label="{{ \App\Support\Options::loc('cc_title', __('Бисквитки')) }}">🍪</button>
@endif
@if($ccAnalytics && trim(\App\Support\Options::get('scripts_analytics')) !== '')<template data-cc-scripts="analytics">{!! \App\Support\Options::get('scripts_analytics') !!}</template>@endif
@if($ccMarketing && trim(\App\Support\Options::get('scripts_marketing')) !== '')<template data-cc-scripts="marketing">{!! \App\Support\Options::get('scripts_marketing') !!}</template>@endif
@endif
