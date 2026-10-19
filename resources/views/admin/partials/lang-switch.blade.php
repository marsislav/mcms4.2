<span class="lang-switch" aria-label="Language">
    @foreach (\App\Support\AdminLang::LANGS as $code => $label)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $code]) }}" hreflang="{{ $code }}" @class(['on' => app()->getLocale() === $code]) @if(app()->getLocale() === $code) aria-current="true" @endif>{{ strtoupper($code) }}</a>
    @endforeach
</span>
