{{-- Rendered before the site scripts: back-to-top button, cookie consent banner and consent-gated scripts. --}}
@if(\App\Support\Options::on('back_to_top'))<a class="to-top" href="#" aria-label="{{ t('site.top', __('Към началото')) }}">↑</a>@endif
@include('site.partials.cookie-consent')
