{{-- Standalone layout for static pages (page.blade.php). It does NOT extend site.layout — edit head, header,
     main and footer here freely (e.g. a landing-page look). Keep it in sync with site.layout when you change shared parts. --}}
@php
    $seo = $seo ?? [];
    $siteName = setting('site_name', config('app.name'));
    $pageTitle = $seo['title'] ?? $siteName;
    $fullTitle = ($pageTitle === $siteName) ? $siteName.(($tl = trim(strip_tags((string) setting_l('tagline')))) !== '' ? ' — '.$tl : '') : $pageTitle.' — '.$siteName;
    $desc = $seo['description'] ?? setting_l('meta_description');
    $canonical = $seo['canonical'] ?? url()->current();
    $ogImage = $seo['image'] ?? (($id = setting('og_media_id')) ? upload_url(optional(\App\Models\Media::find($id))->path) : null);
    $logo = ($id = setting('logo_media_id')) ? \App\Models\Media::find($id) : null;
    $favicon = ($id = setting('favicon_media_id')) ? \App\Models\Media::find($id) : null;
    $current = app()->getLocale();
    $switch = $switch ?? [];
    $topbar = \App\Support\Design::topbar();
    $pageAnim = \App\Support\Design::pageAnim();
    $elAnim = \App\Support\Design::elementAnims();
    $hasAnim = $pageAnim !== 'none' || $elAnim;
    $footerW = \App\Support\Widgets::footer();
    $sidebarW = \App\Support\Widgets::sidebar();
    $showSidebar = $sidebarW['on'] && $sidebarW['widgets'] !== [] && trim($__env->yieldContent('has_sidebar')) === '1';
@endphp
<!doctype html>
<html lang="{{ $current }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    @if($desc)<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $desc), 160) }}">@endif
    <meta name="robots" content="{{ \App\Support\Options::on('noindex') ? 'noindex, nofollow' : ($seo['robots'] ?? 'index, follow, max-image-preview:large') }}">
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
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome/css/all.min.css') }}?v=7.3.1">
    <link rel="stylesheet" href="{{ asset_v('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/grid.css') }}">
    <link rel="stylesheet" href="{{ url('design.css') }}?v={{ \App\Support\Design::version() }}{{ substr(md5(\App\Support\Options::get('custom_css')), 0, 6) }}">
    {!! \App\Support\Options::scripts('head', csp_nonce()) !!}
    @php do_action('head'); @endphp
</head>
<body @class(['pa-'.$pageAnim => $pageAnim !== 'none']) @if($elAnim) data-anim="{{ json_encode($elAnim) }}" @endif>
<a class="skip" href="#main">{{ t('site.skip') }}</a>

@if($topbar)
<div class="topbar">
    <div class="wrap topbar-in" style="--tb-cols: {{ count($topbar) }}">
        @foreach ($topbar as $col)
            @if(trim($col['html']) !== '')<div class="tb-col tb-{{ $col['align'] }}">{!! $col['html'] !!}</div>@else<div class="tb-col"></div>@endif
        @endforeach
    </div>
</div>
@endif

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
                    <li @class(['has-sub' => ! empty($item['children']), ($item['css_class'] ?? '') => ! empty($item['css_class'])]) @if(! empty($item['css_id'])) id="{{ $item['css_id'] }}" @endif>
                        <a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif @if(url()->current() === $item['url']) aria-current="page" @endif>{{ $item['label'] }}</a>
                        @if(! empty($item['children']))
                            <ul class="sub">
                                @foreach ($item['children'] as $child)
                                    <li @class([($child['css_class'] ?? '') => ! empty($child['css_class'])]) @if(! empty($child['css_id'])) id="{{ $child['css_id'] }}" @endif><a href="{{ $child['url'] }}" @if($child['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $child['label'] }}</a></li>
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
                @php $href = $switch[$lang->code] ?? lroute('home', [], $lang->code); @endphp
                <a href="{{ $href }}" hreflang="{{ $lang->code }}" lang="{{ $lang->code }}" @class(['on' => $lang->code === $current]) @if($lang->code === $current) aria-current="true" @endif>{{ strtoupper($lang->code) }}</a>
            @endforeach
        </nav>
        @endif
    </div>
</header>

<main id="main" class="wrap page-main">
    @yield('content')
</main>

<footer class="site-footer">
@if($footerW['cols'] > 0 && $footerW['has'])
    <div class="wrap foot-widgets" style="--fc: {{ $footerW['cols'] }}">
        @foreach ($footerW['columns'] as $col)
            <div class="foot-col">@include('site.partials.widget-area', ['widgets' => $col])</div>
        @endforeach
    </div>
    <div class="wrap foot-bottom">
        <p class="muted">{{ setting_l('footer_text', '© '.date('Y').' '.$siteName) }}</p>
        @php
            $fTypes = collect($footerW['columns'])->flatten(1)->pluck('type')->all();
            $socials = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'])->filter(fn ($l, $k) => setting('social_'.$k));
        @endphp
        <nav class="foot-links" aria-label="Footer">
            <ul>
                @unless(in_array('menu', $fTypes, true))
                    @foreach ($footerMenu as $item)<li><a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>@endforeach
                @endunless
                @unless(in_array('social', $fTypes, true))
                    @foreach ($socials as $k => $label)<li><a href="{{ setting('social_'.$k) }}" rel="noopener noreferrer me" target="_blank">{{ $label }}</a></li>@endforeach
                @endunless
                <li><a href="{{ lroute('feed') }}">RSS</a></li>
            </ul>
        </nav>
            @if($hasAnim && \App\Support\Design::get('anim_pause_btn') === '1')
                <button type="button" class="anim-toggle" data-anim-toggle aria-pressed="false" data-on="{{ __(':p1', ['p1' => t('site.anim_pause', __('Спри анимациите'))]) }}" data-off="{{ __(':p1', ['p1' => t('site.anim_play', __('Пусни анимациите'))]) }}">{{ t('site.anim_pause', __('Спри анимациите')) }}</button>
            @endif
    </div>
@else

    <div class="wrap foot">
        <div>
            <strong>{{ $siteName }}</strong>
            @if($tag = setting_l('tagline'))<p class="muted">{{ $tag }}</p>@endif
            <p class="muted">{{ setting_l('footer_text', '© '.date('Y').' '.$siteName) }}</p>
        </div>
        <nav aria-label="Footer">
            <ul>
                @foreach ($footerMenu as $item)
                    <li @class([($item['css_class'] ?? '') => ! empty($item['css_class'])]) @if(! empty($item['css_id'])) id="{{ $item['css_id'] }}" @endif><a href="{{ $item['url'] }}" @if($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif>{{ $item['label'] }}</a></li>
                @endforeach
                <li><a href="{{ lroute('feed') }}">RSS</a></li>
            </ul>
            @if($hasAnim && \App\Support\Design::get('anim_pause_btn') === '1')
                <button type="button" class="anim-toggle" data-anim-toggle aria-pressed="false" data-on="{{ __(':p1', ['p1' => t('site.anim_pause', __('Спри анимациите'))]) }}" data-off="{{ __(':p1', ['p1' => t('site.anim_play', __('Пусни анимациите'))]) }}">{{ t('site.anim_pause', __('Спри анимациите')) }}</button>
            @endif
        </nav>
        <nav class="social" aria-label="Social">
            @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'] as $k => $label)
                @if($u = setting('social_'.$k))<a href="{{ $u }}" rel="noopener noreferrer me" target="_blank">{{ $label }}</a>@endif
            @endforeach
        </nav>
    </div>
@endif
</footer>

@if(\App\Support\Options::on('back_to_top'))<a class="to-top" href="#" aria-label="{{ t('site.top', __('Към началото')) }}">↑</a>@endif

<script src="{{ asset_v('assets/js/site.js') }}" nonce="{{ csp_nonce() }}" defer></script>
{!! \App\Support\Options::scripts('footer', csp_nonce()) !!}
@php do_action('footer'); @endphp
</body>
</html>
