<?php

namespace App\Support;

/**
 * Site design settings (tab "Design"): colours, fonts, logo size, sticky header,
 * animations and the top bar. Values live in the `settings` table as `design.<key>`
 * (top-bar texts as `design.topbar_cN.<locale>`), so no migration is needed.
 * The generated stylesheet is served from /design.css (CSP: style-src 'self').
 * Pure PHP (no Laravel) except get(), so css() is testable on its own.
 */
class Design
{
    public const ANIMS = [
        'none' => 'Без анимация',
        'fade' => 'Избледняване',
        'slide-up' => 'Отдолу нагоре',
        'slide-down' => 'Отгоре надолу',
        'slide-left' => 'Отдясно наляво',
        'slide-right' => 'Отляво надясно',
        'zoom' => 'Увеличение',
    ];

    /** Self-hosted friendly: system font stacks only (no external requests). */
    public const FONTS = [
        '' => ['По подразбиране на темата', ''],
        'system' => ['Системен (sans-serif)', 'system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif'],
        'humanist' => ['Humanist', 'Seravek,"Gill Sans Nova",Ubuntu,Calibri,"DejaVu Sans","Source Sans Pro",sans-serif'],
        'geometric' => ['Geometric', 'Avenir,Montserrat,Corbel,"URW Gothic","Source Sans Pro",sans-serif'],
        'rounded' => ['Rounded', 'ui-rounded,"Hiragino Maru Gothic ProN",Quicksand,Comfortaa,"Arial Rounded MT","Arial Rounded MT Bold",Calibri,sans-serif'],
        'serif' => ['Serif (Georgia)', 'Georgia,"Iowan Old Style","Palatino Linotype",Palatino,"Times New Roman",serif'],
        'classic' => ['Classic (Times)', '"Times New Roman",Times,"Liberation Serif",serif'],
        'slab' => ['Slab serif', 'Rockwell,"Rockwell Nova","Roboto Slab","DejaVu Serif","Sitka Small",serif'],
        'mono' => ['Monospace', 'ui-monospace,"Cascadia Code","Source Code Pro",Menlo,Consolas,"DejaVu Sans Mono",monospace'],
    ];

    /** key => [label shown in the panel] ; the selector map lives in assets/js/site.js */
    public const ELEMENTS = [
        'header' => 'Хедър',
        'logo' => 'Лого',
        'menu' => 'Елементи на менюто',
        'cards' => 'Карти (блог, свързани)',
        'images' => 'Изображения в текста',
        'headings' => 'Заглавия в текста (H2, H3)',
        'footer' => 'Футър',
    ];

    public const DEFAULTS = [
        'bg' => '', 'fg' => '', 'heading' => '', 'accent' => '', 'header_bg' => '', 'footer_bg' => '',
        'font_body' => '', 'font_heading' => '', 'font_menu' => '', 'font_logo' => '', 'font_size' => '',
        'logo_size' => 'auto', 'sticky_header' => '1',
        'anim_page_type' => 'none', 'anim_page_on' => '1', 'anim_el_on' => '1', 'anim_speed' => '600', 'anim_pause_btn' => '0',
        'el_header' => 'none', 'el_logo' => 'none', 'el_menu' => 'none', 'el_cards' => 'none',
        'el_images' => 'none', 'el_headings' => 'none', 'el_footer' => 'none',
        'topbar_on' => '0', 'topbar_cols' => '2', 'topbar_bg' => '', 'topbar_fg' => '', 'topbar_mobile' => '1',
        'progress_on' => '0', 'progress_color' => '', 'progress_bg' => '', 'progress_height' => '3', 'progress_pos' => 'top', 'progress_scope' => 'posts',
        'blur_on' => '0', 'blur_height' => '15',
        'smooth_on' => '0', 'smooth_duration' => '1.2',
        'topbar_c1_align' => 'left', 'topbar_c2_align' => 'right', 'topbar_c3_align' => 'center', 'topbar_c4_align' => 'right',
    ];

    public const COLOR_KEYS = ['bg', 'fg', 'heading', 'accent', 'header_bg', 'footer_bg', 'topbar_bg', 'topbar_fg', 'progress_color', 'progress_bg'];
    public const BOOL_KEYS = ['sticky_header', 'anim_page_on', 'anim_el_on', 'anim_pause_btn', 'topbar_on', 'topbar_mobile', 'progress_on', 'blur_on', 'smooth_on'];
    public const PROGRESS_POS = ['top' => 'Най-горе', 'bottom' => 'Най-долу'];
    public const PROGRESS_SCOPE = ['posts' => 'Само публикации', 'content' => 'Публикации и страници', 'all' => 'Навсякъде'];
    public const MAX_COLS = 4;

    /* ------------------------------------------------------------ reading */

    public static function get(string $key): string
    {
        $v = function_exists('setting') ? setting('design.'.$key) : null;

        return ($v === null || $v === '') ? (self::DEFAULTS[$key] ?? '') : (string) $v;
    }

    /** All values with defaults applied (for the form). */
    public static function all(): array
    {
        $out = [];
        foreach (self::DEFAULTS as $k => $_) {
            $out[$k] = self::get($k);
        }

        return $out;
    }

    public static function animType(string $v): string
    {
        return isset(self::ANIMS[$v]) ? $v : 'none';
    }

    /** Page-load animation type, or 'none' when switched off. */
    public static function pageAnim(): string
    {
        return self::get('anim_page_on') === '1' ? self::animType(self::get('anim_page_type')) : 'none';
    }

    /** Whether the reading progress bar shows on the given page kind (post | page | list). */
    public static function progressFor(string $kind): bool
    {
        if (self::get('progress_on') !== '1') {
            return false;
        }

        return match (self::get('progress_scope')) {
            'all' => true,
            'content' => in_array($kind, ['post', 'page'], true),
            default => $kind === 'post',
        };
    }

    /** Element animations as [key => type] (only the active ones). */
    public static function elementAnims(): array
    {
        if (self::get('anim_el_on') !== '1') {
            return [];
        }
        $out = [];
        foreach (array_keys(self::ELEMENTS) as $k) {
            $t = self::animType(self::get('el_'.$k));
            if ($t !== 'none') {
                $out[$k] = $t;
            }
        }

        return $out;
    }

    public static function speed(): int
    {
        return max(150, min(2000, (int) self::get('anim_speed')));
    }

    /** Top bar columns: [['html'=>..., 'align'=>...], ...] or [] when off/empty. */
    public static function topbar(): array
    {
        if (self::get('topbar_on') !== '1') {
            return [];
        }
        $n = max(1, min(self::MAX_COLS, (int) self::get('topbar_cols')));
        $cols = [];
        $any = false;
        for ($i = 1; $i <= $n; $i++) {
            $html = (string) setting_l('design.topbar_c'.$i, '');
            $align = self::get('topbar_c'.$i.'_align');
            $cols[] = ['html' => $html, 'align' => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left'];
            $any = $any || trim($html) !== '';
        }

        return $any ? $cols : [];
    }

    /** Short hash for cache-busting /design.css?v=… */
    public static function version(): string
    {
        return substr(md5(self::css()), 0, 8);
    }

    /* ----------------------------------------------------------- CSS output */

    public static function css(): string
    {
        $r = [];
        $root = [];

        // colours -------------------------------------------------------
        $bg = self::color('bg');
        $fg = self::color('fg');
        if ($bg && ! $fg) {
            $fg = self::onColor($bg, '#231f1a', '#f0ebe3');
        } elseif ($fg && ! $bg) {
            $bg = self::onColor($fg, '#171513', '#faf7f2'); // light text -> dark bg and vice versa
        }
        if ($bg && $fg) {
            $root[] = "--bg:$bg;--fg:$fg;--surface:color-mix(in srgb,$bg 94%,$fg);--line:color-mix(in srgb,$bg 84%,$fg);--muted:color-mix(in srgb,$fg 62%,$bg);color-scheme:".(self::lum($bg) > .5 ? 'light' : 'dark');
        }
        if ($accent = self::color('accent')) {
            $root[] = "--accent:$accent;--accent-fg:".self::onColor($accent, '#ffffff', '#111111', true);
        }

        // fonts ---------------------------------------------------------
        if ($s = self::stack('font_body')) {
            $root[] = "--sans:$s";
        }
        if ($s = self::stack('font_heading')) {
            $root[] = "--serif:$s";
        }
        if ($root) {
            $r[] = ':root{'.implode(';', $root).'}';
        }
        if ($px = (int) self::get('font_size')) {
            if ($px >= 14 && $px <= 22) {
                $r[] = "html{font-size:{$px}px}";
            }
        }
        if ($c = self::color('heading')) {
            $r[] = "h1,h2,h3,h4,h5,h6,.card h2 a{color:$c}";
        }
        if ($s = self::stack('font_menu')) {
            $r[] = ".primary a,.langs a{font-family:$s}";
        }
        if ($s = self::stack('font_logo')) {
            $r[] = ".logo{font-family:$s}";
        }

        // logo, header, footer -------------------------------------------
        $ls = self::get('logo_size');
        if (ctype_digit($ls) && (int) $ls >= 20 && (int) $ls <= 300) {
            $r[] = ".logo img{max-height:none;height:{$ls}px;width:auto;max-width:100%}.logo span{font-size:calc({$ls}px * .55)}";
            $r[] = "@media (max-width:760px){.logo img{height:min({$ls}px,3.5rem)}.logo span{font-size:min(calc({$ls}px * .55),1.9rem)}}";
        }
        if (self::get('sticky_header') !== '1') {
            $r[] = '.site-header{position:static}';
        }
        if ($c = self::color('header_bg')) {
            $r[] = self::scope('.site-header', $c, 'backdrop-filter:none');
        }
        if ($c = self::color('footer_bg')) {
            $r[] = self::scope('.site-footer', $c);
        }

        // top bar ---------------------------------------------------------
        if ($c = self::color('topbar_bg')) {
            $fgT = self::color('topbar_fg');
            $r[] = self::scope('.topbar', $c, '', $fgT);
        } elseif ($fgT = self::color('topbar_fg')) {
            $r[] = ".topbar{color:$fgT}.topbar a{color:$fgT}";
        }
        if (self::get('topbar_mobile') !== '1') {
            $r[] = '@media (max-width:760px){.topbar{display:none}}';
        }

        // reading progress bar --------------------------------------------
        if (self::get('progress_on') === '1') {
            $h = max(1, min(12, (int) self::get('progress_height')));
            $pc = self::color('progress_color');
            $pb = self::color('progress_bg');
            $bottom = self::get('progress_pos') === 'bottom';
            $r[] = '.read-progress{height:'.$h.'px;'.($bottom ? 'bottom:0;top:auto' : 'top:0;bottom:auto').($pb ? ';background:'.$pb : '').'}'
                .'.read-progress span{'.($pc ? 'background:'.$pc : '').'}';
        }

        // progressive blur at the bottom of the viewport ------------------
        if (self::get('blur_on') === '1') {
            $r[] = ':root{--pblur-h:'.max(5, min(50, (int) self::get('blur_height'))).'vh}';
        }

        // animation speed -------------------------------------------------
        $r[] = ':root{--anim-ms:'.self::speed().'ms}';

        return "/* mCMS design — generated from Settings > Design */\n".implode("\n", $r)."\n";
    }

    /* -------------------------------------------------------------- helpers */

    /** Rule for a block with its own background: readable text, links, borders. */
    private static function scope(string $sel, string $bg, string $extra = '', ?string $fg = null): string
    {
        $fg = $fg ?: self::onColor($bg, '#231f1a', '#f0ebe3');
        $rules = "$sel{background:$bg;color:$fg;--fg:$fg;--surface:color-mix(in srgb,$bg 94%,$fg);--line:color-mix(in srgb,$bg 82%,$fg);--muted:color-mix(in srgb,$fg 65%,$bg)".($extra ? ';'.$extra : '').'}';
        $rules .= "$sel a:not(.on){color:$fg}$sel .langs a.on{color:var(--accent-fg)}";

        return $rules;
    }

    private static function color(string $key): ?string
    {
        return self::normalizeColor(self::get($key));
    }

    public static function normalizeColor(?string $v): ?string
    {
        $v = trim((string) $v);
        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v)) {
            return null;
        }
        if (strlen($v) === 4) {
            $v = '#'.$v[1].$v[1].$v[2].$v[2].$v[3].$v[3];
        }

        return strtolower($v);
    }

    private static function stack(string $key): ?string
    {
        $k = self::get($key);

        return ($k !== '' && isset(self::FONTS[$k]) && self::FONTS[$k][1] !== '') ? self::FONTS[$k][1] : null;
    }

    /** WCAG relative luminance 0..1 of #rrggbb. */
    public static function lum(string $hex): float
    {
        $c = array_map(function ($h) {
            $v = hexdec($h) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    /** Pick the better-contrast colour of two candidates for text on $bg (or reverse when $darkFirst is for light bg). */
    private static function onColor(string $bg, string $a, string $b, bool $contrast = false): string
    {
        if ($contrast) { // choose between $a and $b by best contrast against $bg
            $l = self::lum($bg);
            $ca = (max($l, self::lum($a)) + .05) / (min($l, self::lum($a)) + .05);
            $cb = (max($l, self::lum($b)) + .05) / (min($l, self::lum($b)) + .05);

            return $ca >= $cb ? $a : $b;
        }

        // $a = text for light backgrounds, $b = text for dark backgrounds
        return self::lum($bg) > .4 ? $a : $b;
    }
}
