@php
    $seo = $seo ?? [];
    $siteName = setting('site_name', config('app.name'));
    $pageTitle = $seo['title'] ?? $siteName;
    $fullTitle = ($pageTitle === $siteName) ? $siteName : $pageTitle.' — '.$siteName;
    $desc = $seo['description'] ?? setting_l('meta_description');
    $canonical = $seo['canonical'] ?? url()->current();
    $ogImage = $seo['image'] ?? (($id = setting('og_media_id')) ? upload_url(optional(\App\Models\Media::find($id))->path) : null);
    $logo = ($id = setting('logo_media_id')) ? \App\Models\Media::find($id) : null;
    $favicon = ($id = setting('favicon_media_id')) ? \App\Models\Media::find($id) : null;
    $current = app()->getLocale();
    $switch = $switch ?? [];
@endphp
<!doctype html>
<html lang="{{ $current }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if($desc)<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $desc), 160) }}">@endif
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow, max-image-preview:large' }}">
    <link rel="canonical" href="{{ $canonical }}">
    @foreach (($seo['alternates'] ?? []) as $loc => $href)
        <link rel="alternate" hreflang="{{ $loc }}" href="{{ $href }}">
    @endforeach
    @if(! empty($seo['alternates'][mcms_default_locale()]))
        <link rel="alternate" hreflang="x-default" href="{{ $seo['alternates'][mcms_default_locale()] }}">
    @endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    @if($desc)<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $desc), 200) }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $current) }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    @if(! empty($seo['published']))<meta property="article:published_time" content="{{ $seo['published']->toAtomString() }}">@endif
    <link rel="alternate" type="application/rss+xml" title="{{ t('site.rss') }}" href="{{ lroute('feed') }}">
    @if($favicon)<link rel="icon" href="{{ $favicon->url() }}">@endif
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}?v={{ config('mcms.version') }}">
    @php(do_action('head'))
</head>
<body>
<a class="skip" href="#main">{{ t('site.skip') }}</a>

<header class="site-header">
    <div class="wrap bar">
        <a class="logo" href="{{ lroute('home') }}">
            @if($logo)<img src="{{ $logo->url() }}" alt="{{ $siteName }}" @if($logo->width) width="{{ min($logo->width, 220) }}" @endif>
            @else<span>{{ $siteName }}</span>@endif
        </a>

        <input type="checkbox" id="menu-toggle" class="menu-toggle" hidden>
        <label for="menu-toggle" class="burger" aria-label="{{ t('site.menu') }}">☰</label>

        <nav class="primary" aria-label="{{ t('site.menu') }}">
            <ul>
                @foreach ($headerMenu as $item)
                    <li @class(['has-sub' => ! empty($item['children'])])>
                        <a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif @if(url()->current() === $item['url']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @if(! empty($item['children']))
                            <ul class="sub">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ $child['url'] }}" @if($child['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        @if($languages->count() > 1)
        <nav class="langs" aria-label="{{ t('site.language') }}">
            @foreach ($languages as $lang)
                @php($href = $switch[$lang->code] ?? lroute('home', [], $lang->code))
                <a href="{{ $href }}" hreflang="{{ $lang->code }}" lang="{{ $lang->code }}" @class(['on' => $lang->code === $current]) @if($lang->code === $current) aria-current="true" @endif>{{ strtoupper($lang->code) }}</a>
            @endforeach
        </nav>
        @endif
    </div>
</header>

<main id="main" class="wrap main">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap foot">
        <div>
            <strong>{{ $siteName }}</strong>
            @if($tag = setting_l('tagline'))<p class="muted">{{ $tag }}</p>@endif
            <p class="muted">{{ setting_l('footer_text', '© '.date('Y').' '.$siteName) }}</p>
        </div>
        <nav aria-label="Footer">
            <ul>
                @foreach ($footerMenu as $item)
                    <li><a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
                @endforeach
                <li><a href="{{ lroute('feed') }}">RSS</a></li>
            </ul>
        </nav>
        <nav class="social" aria-label="Social">
            @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $k => $label)
                @if($u = setting('social_'.$k))<a href="{{ $u }}" rel="noopener noreferrer me" target="_blank">{{ $label }}</a>@endif
            @endforeach
        </nav>
    </div>
</footer>

<script src="{{ asset('assets/js/site.js') }}?v={{ config('mcms.version') }}" nonce="{{ csp_nonce() }}" defer></script>
@php(do_action('footer'))
</body>
</html>
