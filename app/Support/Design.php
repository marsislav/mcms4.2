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

    public const CUSTOM_FONTS = 6;
    private const FONT_PATH = '#^\d{4}/\d{2}/[0-9a-f]{24}\.woff2?$#';

    /** Clean family name (letters, digits, space, hyphen), '' when empty. */
    public static function fontName($v): string
    {
        return is_string($v) ? trim(mb_substr((string) preg_replace('/[^\p{L}\p{N} \-]/u', '', $v), 0, 40)) : '';
    }

    /** Own fonts saved in Settings: slot (1..6) => ['name', 'regular', 'bold'] (paths relative to /uploads). */
    public static function customFonts(): array
    {
        $raw = function_exists('setting') ? setting('design.fonts') : null;
        $arr = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $out = [];
        foreach (is_array($arr) ? $arr : [] as $slot => $f) {
            $slot = (int) $slot;
            if ($slot < 1 || $slot > self::CUSTOM_FONTS || ! is_array($f)) {
                continue;
            }
            $name = self::fontName($f['name'] ?? '');
            $reg = (isset($f['regular']) && is_string($f['regular']) && preg_match(self::FONT_PATH, $f['regular'])) ? $f['regular'] : '';
            $bold = (isset($f['bold']) && is_string($f['bold']) && preg_match(self::FONT_PATH, $f['bold'])) ? $f['bold'] : '';
            if ($name !== '' && $reg !== '') {
                $out[$slot] = ['name' => $name, 'regular' => $reg, 'bold' => $bold];
            }
        }

        return $out;
    }

    /** Keys a font select may post: the built-in stacks plus f1..f6. */
    public static function fontKeys(): array
    {
        $k = array_keys(self::FONTS);
        for ($i = 1; $i <= self::CUSTOM_FONTS; $i++) {
            $k[] = 'f'.$i;
        }

        return $k;
    }

    private static function fontFaceCss(): array
    {
        $r = [];
        foreach (self::customFonts() as $f) {
            $fam = '"'.$f['name'].'"';
            $face = fn (string $path, int $w) => '@font-face{font-family:'.$fam.';src:url("'.self::uploadUrl($path).'") format("'.(str_ends_with($path, '.woff2') ? 'woff2' : 'woff').'");font-weight:'.$w.';font-style:normal;font-display:swap}';
            $r[] = $face($f['regular'], 400);
            if ($f['bold'] !== '') {
                $r[] = $face($f['bold'], 700);
            }
        }

        return $r;
    }

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
        'op_on' => '0', 'op_anim' => 'fade-up', 'op_title' => 'none', 'op_duration' => '800', 'op_stagger' => '90', 'op_children' => '1', 'op_dots' => '1', 'op_snap' => '0', 'op_parallax' => '0', 'op_full' => '0',
        'blur_on' => '0', 'blur_height' => '15', 'bg_video_mobile' => '0',
        'grain_on' => '0', 'grain_color' => '', 'grain_opacity' => '9', 'grain_density' => '60',
        'h1_anim' => 'none', 'h1_scope' => 'entry', 'h1_underline' => '0', 'h1_word' => 'last', 'h1_underline_color' => '', 'rotate_interval' => '2600',
        'gd_radius' => '', 'gd_width' => '', 'gd_font' => '', 'gd_glass' => '0', 'gd_dim' => '0', 'gd_btn2_ghost' => '0',
        'gd_btn_bg' => '', 'gd_btn_fg' => '', 'gd_btn2_bg' => '', 'gd_btn2_fg' => '', 'gd_link' => '', 'gd_reopen_bg' => '',
        'smooth_on' => '0', 'smooth_duration' => '1.2',
        'topbar_c1_align' => 'left', 'topbar_c2_align' => 'right', 'topbar_c3_align' => 'center', 'topbar_c4_align' => 'right',
    ];

    public const COLOR_KEYS = ['bg', 'fg', 'heading', 'accent', 'header_bg', 'footer_bg', 'topbar_bg', 'topbar_fg', 'progress_color', 'progress_bg', 'grain_color', 'h1_underline_color', 'gd_btn_bg', 'gd_btn_fg', 'gd_btn2_bg', 'gd_btn2_fg', 'gd_link', 'gd_reopen_bg'];
    public const BOOL_KEYS = ['sticky_header', 'anim_page_on', 'anim_el_on', 'anim_pause_btn', 'topbar_on', 'topbar_mobile', 'progress_on', 'blur_on', 'smooth_on', 'bg_video_mobile', 'grain_on', 'h1_underline', 'gd_glass', 'gd_dim', 'gd_btn2_ghost', 'op_on', 'op_children', 'op_dots', 'op_snap', 'op_parallax', 'op_full'];
    public const PROGRESS_POS = ['top' => 'Най-горе', 'bottom' => 'Най-долу'];
    public const PROGRESS_SCOPE = ['posts' => 'Само публикации', 'content' => 'Публикации и страници', 'all' => 'Навсякъде'];
    public const MAX_COLS = 4;


    /* ------------------------------------------------- element backgrounds */

    /** Fixed targets: key => [label, CSS selector]. Five more free-selector slots (c1..c5) are added by bgKeys(). */
    public const BG_TARGETS = [
        'page' => ['Цялата страница', 'body'],
        'topbar' => ['Горна лента', '.topbar'],
        'header' => ['Хедър', '.site-header'],
        'main' => ['Основна област', 'main.main'],
        'entry' => ['Статия / страница', '.entry, .entry-page'],
        'cards' => ['Карти в списъците', '.card'],
        'sidebar' => ['Страничен панел', '.sidebar'],
        'footer' => ['Футър', '.site-footer'],
        'cc' => ['Банер за бисквитки (GDPR)', '.cc'],
        'op1' => ['One-page секция 1', '.op > .op-sec:nth-child(1)'],
        'op2' => ['One-page секция 2', '.op > .op-sec:nth-child(2)'],
        'op3' => ['One-page секция 3', '.op > .op-sec:nth-child(3)'],
        'op4' => ['One-page секция 4', '.op > .op-sec:nth-child(4)'],
        'op5' => ['One-page секция 5', '.op > .op-sec:nth-child(5)'],
        'op6' => ['One-page секция 6', '.op > .op-sec:nth-child(6)'],
        'op7' => ['One-page секция 7', '.op > .op-sec:nth-child(7)'],
        'op8' => ['One-page секция 8', '.op > .op-sec:nth-child(8)'],
        'op9' => ['One-page секция 9', '.op > .op-sec:nth-child(9)'],
        'op10' => ['One-page секция 10', '.op > .op-sec:nth-child(10)'],
        'op11' => ['One-page секция 11', '.op > .op-sec:nth-child(11)'],
    ];
    public const H1_ANIMS = ['none' => 'Без анимация', 'words' => 'Думите излизат една по една'];
    public const H1_WORD = ['last' => 'Последната дума', 'first' => 'Първата дума', 'last2' => 'Последните две думи'];
    public const H1_SCOPE = ['entry' => 'Само заглавията на статии и страници', 'all' => 'Всички H1 заглавия'];
    /** Word typed on a page/post to underline in its H1 (stored in settings as h1hl.<content id>.<locale>); '' = use the global rule. */
    public static function h1Word($contentId, ?string $locale): string
    {
        $v = function_exists('setting') && $contentId ? setting('h1hl.'.(int) $contentId.'.'.preg_replace('/[^a-z\-]/i', '', (string) $locale)) : '';

        return is_string($v) ? mb_substr(trim(strip_tags($v)), 0, 60) : '';
    }

    public const BG_NO_VIDEO = ['cards', 'cc'];   // repeated elements: a video each would be too heavy
    public const BG_CUSTOM = 5;
    public const BG_SIZE = ['cover' => 'Покрива (cover)', 'contain' => 'Побира се (contain)', 'auto' => 'Оригинален размер', '100% 100%' => 'Разтегнат'];
    public const BG_POS = [
        'center' => 'Център', 'top' => 'Горе', 'bottom' => 'Долу', 'left' => 'Ляво', 'right' => 'Дясно',
        'top left' => 'Горе вляво', 'top right' => 'Горе вдясно', 'bottom left' => 'Долу вляво', 'bottom right' => 'Долу вдясно',
    ];
    public const BG_REPEAT = ['no-repeat' => 'Без повторение', 'repeat' => 'Повтаря се', 'repeat-x' => 'По хоризонтала', 'repeat-y' => 'По вертикала'];
    public const BG_EXTRAS = 3;
    public const BG_BLEND = ['normal' => 'Нормално', 'multiply' => 'Умножение (multiply)', 'screen' => 'Осветляване (screen)', 'overlay' => 'Overlay', 'soft-light' => 'Soft light', 'darken' => 'Потъмняване', 'lighten' => 'Изсветляване', 'luminosity' => 'Luminosity'];
    public const BG_ANIM = [
        'none' => 'Без анимация',
        'kenburns' => 'Ken Burns (бавно приближаване) — картинка',
        'pan' => 'Бавно движение наляво-надясно — картинка',
        'drift' => 'Плаващо по диагонал — картинка',
        'parallax' => 'Паралакс при скрол — картинка',
        'gradient-shift' => 'Движещ се градиент — градиент',
    ];
    public const BG_ANIM_MODE = ['time' => 'По време (върви сама)', 'scroll' => 'При скрол (върви със скрола)'];
    public const BG_ANIM_LOOP = ['infinite' => 'Безкрайно (напред-назад)', 'once' => 'Веднъж (когато се покаже)'];
    public const BG_IMG_ANIMS = ['kenburns', 'pan', 'drift', 'parallax'];
    public const BG_ATTACH = ['scroll' => 'Скролира със страницата', 'fixed' => 'Фиксиран'];
    public const BG_GRAD = ['linear' => 'Линеен', 'radial' => 'Радиален', 'conic' => 'Конусен'];

    /** Stored upload paths look like 2026/10/<24 hex>[_t].<ext> — nothing else is accepted into the CSS. */
    private const BG_PATH = '#^\d{4}/\d{2}/[0-9a-f]{24}(?:_t)?\.(?:webp|jpe?g|png|gif|webm|mp4)$#';

    /** @return list<string> */
    public static function bgKeys(): array
    {
        $k = array_keys(self::BG_TARGETS);
        for ($i = 1; $i <= self::BG_CUSTOM; $i++) {
            $k[] = 'c'.$i;
        }

        return $k;
    }

    /** A user-written selector, or null when it is empty / unsafe (no braces, semicolons, comments, @-rules). */
    public static function validSelector(?string $s): ?string
    {
        $s = trim((string) preg_replace('/\s+/', ' ', (string) $s));
        if ($s === '' || strlen($s) > 120 || ! preg_match('/^[A-Za-z0-9_\-#.\s>+~,:()\[\]="\'*^$|]+$/', $s)) {
            return null;
        }

        return $s;
    }

    /**
     * Whitelist-clean the posted `bg[<key>][<field>]` values. Media paths (image_path / video_path / poster_path)
     * are resolved by the controller from the media library; here they are only pattern-checked.
     *
     * @return array<string, array<string, string>>
     */
    public static function normalizeBg(array $in): array
    {
        $pick = fn ($v, array $allowed, string $def) => is_scalar($v) && in_array((string) $v, $allowed, true) ? (string) $v : $def;
        $hex = fn ($v) => self::normalizeColor(is_scalar($v) ? (string) $v : '') ?? '';
        $num = fn ($v, int $min, int $max, int $def) => (string) max($min, min($max, is_numeric($v) ? (int) $v : $def));
        $path = fn ($v) => (is_string($v) && preg_match(self::BG_PATH, $v)) ? $v : '';
        // background-size: a keyword, or one/two lengths such as 50%, 200px, 100% auto
        $len = '(?:auto|\d{1,4}(?:\.\d{1,2})?(?:px|%|vw|vh|rem|em))';
        $size = fn ($v) => (is_string($v) && (isset(self::BG_SIZE[trim($v)]) || preg_match('#^'.$len.'(?: '.$len.')?$#', trim($v)))) ? trim($v) : 'cover';
        $id = fn ($v) => (is_scalar($v) && ctype_digit((string) $v) && (int) $v > 0) ? (string) (int) $v : '';

        $out = [];
        foreach (self::bgKeys() as $key) {
            $c = is_array($in[$key] ?? null) ? $in[$key] : [];
            $row = [
                'selector' => isset(self::BG_TARGETS[$key]) ? '' : (self::validSelector($c['selector'] ?? '') ?? ''),
                'color' => $hex($c['color'] ?? ''),
                'text' => $hex($c['text'] ?? ''),
                'grad' => ! empty($c['grad']) ? '1' : '0',
                'grad_type' => $pick($c['grad_type'] ?? '', array_keys(self::BG_GRAD), 'linear'),
                'grad_angle' => $num($c['grad_angle'] ?? 180, 0, 360, 180),
                'grad_c1' => $hex($c['grad_c1'] ?? ''), 'grad_a1' => $num($c['grad_a1'] ?? 100, 0, 100, 100),
                'grad_c2' => $hex($c['grad_c2'] ?? ''), 'grad_a2' => $num($c['grad_a2'] ?? 100, 0, 100, 100),
                'grad_c3' => $hex($c['grad_c3'] ?? ''), 'grad_a3' => $num($c['grad_a3'] ?? 100, 0, 100, 100),
                'image_id' => $id($c['image_id'] ?? ''), 'image_path' => $path($c['image_path'] ?? ''),
                'size' => $size($c['size'] ?? ''),
                'pos' => $pick($c['pos'] ?? '', array_keys(self::BG_POS), 'center'),
                'repeat' => $pick($c['repeat'] ?? '', array_keys(self::BG_REPEAT), 'no-repeat'),
                'attach' => $pick($c['attach'] ?? '', array_keys(self::BG_ATTACH), 'scroll'),
                'video_id' => $id($c['video_id'] ?? ''), 'video_path' => $path($c['video_path'] ?? ''),
                'poster_id' => $id($c['poster_id'] ?? ''), 'poster_path' => $path($c['poster_path'] ?? ''),
                'anim' => $pick($c['anim'] ?? '', array_keys(self::BG_ANIM), 'none'),
                'anim_mode' => $pick($c['anim_mode'] ?? '', array_keys(self::BG_ANIM_MODE), 'time'),
                'anim_loop' => $pick($c['anim_loop'] ?? '', array_keys(self::BG_ANIM_LOOP), 'infinite'),
                'anim_speed' => $num($c['anim_speed'] ?? 20, 3, 120, 20),
                'overlay' => $hex($c['overlay'] ?? ''), 'overlay_op' => $num($c['overlay_op'] ?? 40, 0, 100, 40),
            ];
            for ($n = 1; $n <= self::BG_EXTRAS; $n++) {
                $row["x{$n}_image_id"] = $id($c["x{$n}_image_id"] ?? '');
                $row["x{$n}_image_path"] = $path($c["x{$n}_image_path"] ?? '');
                $row["x{$n}_size"] = $size($c["x{$n}_size"] ?? '');
                $row["x{$n}_pos"] = $pick($c["x{$n}_pos"] ?? '', array_keys(self::BG_POS), 'center');
                $row["x{$n}_repeat"] = $pick($c["x{$n}_repeat"] ?? '', array_keys(self::BG_REPEAT), 'no-repeat');
                $row["x{$n}_attach"] = $pick($c["x{$n}_attach"] ?? '', array_keys(self::BG_ATTACH), 'scroll');
                $row["x{$n}_blend"] = $pick($c["x{$n}_blend"] ?? '', array_keys(self::BG_BLEND), 'normal');
                if ($row["x{$n}_image_path"] === '') {
                    $row["x{$n}_image_id"] = '';
                }
            }
            if (in_array($key, self::BG_NO_VIDEO, true)) {
                $row['video_id'] = $row['video_path'] = $row['poster_id'] = $row['poster_path'] = '';
            }
            if ($row['image_path'] === '') {
                $row['image_id'] = '';
            }
            if ($row['video_path'] === '') {
                $row['video_id'] = '';
            }
            if ($row['poster_path'] === '') {
                $row['poster_id'] = '';
            }
            $out[$key] = $row;
        }

        return $out;
    }

    /** Does this row set anything at all? (empty rows are not stored) */
    public static function bgUsed(array $c): bool
    {
        for ($n = 1; $n <= self::BG_EXTRAS; $n++) {
            if (($c["x{$n}_image_path"] ?? '') !== '') {
                return true;
            }
        }

        return $c['color'] !== '' || $c['text'] !== '' || $c['grad'] === '1' || $c['image_path'] !== ''
            || $c['video_path'] !== '' || $c['poster_path'] !== '';
    }

    /** Saved backgrounds, normalised, for every key (unused ones are all-empty rows). */
    public static function backgrounds(): array
    {
        $raw = function_exists('setting') ? setting('design.backgrounds') : null;
        $arr = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return self::normalizeBg(is_array($arr) ? $arr : []);
    }

    /** CSS selector for a row, or null (custom slot without a valid selector). */
    private static function bgSelector(string $key, array $c): ?string
    {
        return $key[0] === 'c' && ! isset(self::BG_TARGETS[$key]) ? ($c['selector'] !== '' ? $c['selector'] : null) : (self::BG_TARGETS[$key][1] ?? null);
    }

    /** Append a suffix to every selector of a comma list: ".a, .b" + " > x" => ".a > x,.b > x". */
    private static function sfx(string $sel, string $suffix): string
    {
        return implode(',', array_map(fn ($s) => trim($s).$suffix, explode(',', $sel)));
    }

    private static function rgba(string $hex, string $pct): string
    {
        [$r, $g, $b] = array_map('hexdec', str_split(substr($hex, 1), 2));

        return 'rgba('.$r.','.$g.','.$b.','.round(((int) $pct) / 100, 2).')';
    }

    private static function bgGradient(array $c): ?string
    {
        if ($c['grad'] !== '1' || $c['grad_c1'] === '' || $c['grad_c2'] === '') {
            return null;
        }
        $stops = [[$c['grad_c1'], $c['grad_a1']], [$c['grad_c2'], $c['grad_a2']]];
        if ($c['grad_c3'] !== '') {
            array_splice($stops, 1, 0, [[$c['grad_c3'], $c['grad_a3']]]);   // c3 sits between c1 and c2
        }
        $n = count($stops);
        $list = [];
        foreach ($stops as $i => [$col, $a]) {
            $list[] = self::rgba($col, $a).' '.round($i * 100 / ($n - 1)).'%';
        }
        $list = implode(',', $list);
        $ang = (int) $c['grad_angle'];

        return match ($c['grad_type']) {
            'radial' => "radial-gradient(circle at center,$list)",
            'conic' => "conic-gradient(from {$ang}deg,$list)",
            default => "linear-gradient({$ang}deg,$list)",
        };
    }

    /** Rules for the per-element backgrounds (colour, gradient, image, video overlay, text colour). */
    private static function bgCss(): array
    {
        $r = [];
        foreach (self::backgrounds() as $key => $c) {
            if (! self::bgUsed($c) || ($sel = self::bgSelector($key, $c)) === null) {
                continue;
            }
            $d = [];
            if ($c['color'] !== '') {
                $d[] = 'background-color:'.$c['color'];
            }
            $grad = self::bgGradient($c);
            $gs = $grad && $c['anim'] === 'gradient-shift';   // moving gradient lives in its own script-made layer (.mbg-grad)
            $hasOv = $c['overlay'] !== '' && $c['overlay_op'] !== '0';
            $img = $c['image_path'] !== '' ? "url('".self::uploadUrl($c['image_path'])."')" : null;
            $extras = [];
            for ($n = 1; $n <= self::BG_EXTRAS; $n++) {
                if ($c["x{$n}_image_path"] !== '') {
                    $extras[] = ["url('".self::uploadUrl($c["x{$n}_image_path"])."')", $c["x{$n}_size"], $c["x{$n}_pos"], $c["x{$n}_repeat"], $c["x{$n}_attach"], $c["x{$n}_blend"]];
                }
            }
            if ($grad || $img || $extras) {
                $layers = $sizes = $poss = $reps = [];
                if ($hasOv && ! $gs) {
                    $o = self::rgba($c['overlay'], $c['overlay_op']);
                    $layers[] = "linear-gradient($o,$o)"; $sizes[] = 'auto'; $poss[] = '0 0'; $reps[] = 'no-repeat';
                }
                if ($grad && ! $gs) {
                    $layers[] = $grad; $sizes[] = 'auto'; $poss[] = '0 0'; $reps[] = 'no-repeat';
                }
                $atts = array_fill(0, count($layers), $c['attach']);
                $blends = array_fill(0, count($layers), 'normal');
                foreach ($extras as [$u, $sz, $ps, $rp, $at, $bl]) {
                    $layers[] = $u; $sizes[] = $sz; $poss[] = $ps; $reps[] = $rp; $atts[] = $at; $blends[] = $bl;
                }
                if ($img) {
                    $layers[] = $img; $sizes[] = $c['size']; $poss[] = $c['pos']; $reps[] = $c['repeat']; $atts[] = $c['attach']; $blends[] = 'normal';
                }
                if ($layers) {
                $d[] = 'background-image:'.implode(',', $layers);
                $d[] = 'background-size:'.implode(',', $sizes);
                $d[] = 'background-position:'.implode(',', $poss);
                $d[] = 'background-repeat:'.implode(',', $reps);
                $d[] = 'background-attachment:'.implode(',', $atts);
                if (array_diff($blends, ['normal'])) {
                    $d[] = 'background-blend-mode:'.implode(',', $blends);
                }
                }
            }
            if ($c['text'] !== '') {
                $d[] = 'color:'.$c['text'];
                $d[] = '--fg:'.$c['text'];
            }
            if ($d) {
                $r[] = $sel.'{'.implode(';', $d).'}';
            }
            if ($c['text'] !== '') {
                $r[] = self::sfx($sel, ' :is(h1,h2,h3,h4,h5,h6,p,li,small,strong,label,span,a):not(.btn):not(.on)').'{color:'.$c['text'].'}';
            }
            if ($gs) {
                $kf = 'mbg-ga-'.preg_replace('/[^a-z0-9]/i', '', $key);
                $once = $c['anim_loop'] === 'once';
                $r[] = self::sfx($sel, ' > .mbg-grad').'{background-image:'.$grad.';background-size:300% 300%}';
                if ($hasOv) {
                    $r[] = self::sfx($sel, ' > .mbg-grad::after').'{background:'.self::rgba($c['overlay'], $c['overlay_op']).'}';
                }
                if ($c['anim_mode'] !== 'scroll') {
                    $r[] = '@keyframes '.$kf.'{from{background-position:0% 0%}to{background-position:100% 100%}}';
                    $r[] = '@media (prefers-reduced-motion:no-preference){'.self::sfx($sel, ' > .mbg-grad:not(.mbg-scroll)').'{animation:'.$kf.' '.(int) $c['anim_speed'].'s ease-in-out '.($once ? '1 normal forwards' : 'infinite alternate').'}'
                        .self::sfx($sel, ' > .mbg-grad.mbg-wait').'{animation-play-state:paused}}';
                }
            }
            if (self::imgAnimated($c)) {
                // the JS layer .mbg-img carries the image; gradient and overlay move onto it so they stay above the image
                $g1 = $grad && ! $gs;
                $bl = $g1 ? [$grad] : [];
                $bs = $g1 ? ['auto'] : []; $bp = $g1 ? ['0 0'] : []; $br = $g1 ? ['no-repeat'] : []; $bb = $g1 ? ['normal'] : [];
                foreach ($extras as [$u, $sz, $ps, $rp, $at, $bm]) {
                    $bl[] = $u; $bs[] = $sz; $bp[] = $ps; $br[] = $rp; $bb[] = $bm;
                }
                if ($bl) {
                    $r[] = self::sfx($sel, ' > .mbg-img::before').'{background-image:'.implode(',', $bl).';background-size:'.implode(',', $bs).';background-position:'.implode(',', $bp).';background-repeat:'.implode(',', $br).(array_diff($bb, ['normal']) ? ';background-blend-mode:'.implode(',', $bb) : '').'}';
                }
                if ($hasOv && ! $gs) {
                    $r[] = self::sfx($sel, ' > .mbg-img::after').'{background:'.self::rgba($c['overlay'], $c['overlay_op']).'}';
                }
                $r[] = self::sfx($sel, '.has-mbg-img').'{background-image:none!important}';
            }
            if ($c['video_path'] !== '' && $c['overlay'] !== '' && $c['overlay_op'] !== '0') {
                $r[] = self::sfx($sel, ' > .mbg-video::after').'{background:'.self::rgba($c['overlay'], $c['overlay_op']).'}';
            }
        }

        return $r;
    }

    /** Rules for the GDPR cookie banner (radius, width, font, glass, page dimming, button and link colours). */
    private static function ccCss(): array
    {
        $r = [];
        $n = fn (string $k, int $min, int $max) => ctype_digit(self::get($k)) && (int) self::get($k) >= $min && (int) self::get($k) <= $max ? (int) self::get($k) : null;
        if (($v = $n('gd_radius', 0, 40)) !== null) {
            $r[] = '.cc{border-radius:'.$v.'px}';
        }
        if (($v = $n('gd_width', 280, 900)) !== null) {
            $r[] = '.cc-bottom-left,.cc-bottom-right,.cc-center{width:min('.$v.'px,calc(100% - 2rem))}';
        }
        if (($v = $n('gd_font', 12, 20)) !== null) {
            $r[] = '.cc .cc-box{font-size:'.$v.'px}.cc .cc-box p{font-size:1em}.cc .cc-box h2{font-size:1.2em}';
        }
        if (self::get('gd_glass') === '1') {
            $bg = self::backgrounds()['cc']['color'] ?? '';
            $r[] = '.cc{background-color:color-mix(in srgb,'.($bg !== '' ? $bg : 'var(--bg,#fff)').' 72%,transparent);-webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px)}';
        }
        if (self::get('gd_dim') === '1') {
            $r[] = 'body:has(#cc-banner:not([hidden]))::before{content:"";position:fixed;inset:0;z-index:199;background:rgba(0,0,0,.45)}';
        }
        $bb = self::color('gd_btn_bg');
        $bf = self::color('gd_btn_fg');
        if ($bb || $bf) {
            $r[] = '.cc .btn.primary{'.($bb ? 'background:'.$bb.';border-color:'.$bb.';' : '').($bf ? 'color:'.$bf : '').'}';
        }
        $sb = self::color('gd_btn2_bg');
        $sf = self::color('gd_btn2_fg');
        $ghost = self::get('gd_btn2_ghost') === '1';
        if ($sb || $sf || $ghost) {
            $r[] = '.cc .btn:not(.primary):not(.link){'.($ghost ? 'background:transparent;' : ($sb ? 'background:'.$sb.';' : '')).($sf ? 'color:'.$sf.';border-color:'.$sf : '').'}';
        }
        if (($lk = self::color('gd_link')) !== null) {
            $r[] = '.cc a,.cc .btn.link{color:'.$lk.'}';
        }
        if (($rb = self::color('gd_reopen_bg')) !== null) {
            $r[] = '.cc-reopen{background:'.$rb.'}';
        }

        return $r;
    }

    /** Does this background use an animated image layer (Ken Burns, pan, drift, parallax)? */
    public static function imgAnimated(array $c): bool
    {
        return $c['image_path'] !== '' && in_array($c['anim'], self::BG_IMG_ANIMS, true) && $c['video_path'] === '';
    }

    /** Video backgrounds for the front-end script: [{s: selector, v: url, p: poster url}]. */
    public static function bgVideos(): array
    {
        $out = [];
        foreach (self::backgrounds() as $key => $c) {
            if (($sel = self::bgSelector($key, $c)) === null) {
                continue;
            }
            if (self::imgAnimated($c)) {
                $out[] = ['s' => $sel, 'g' => self::uploadUrl($c['image_path']), 'a' => $c['anim'], 'd' => (int) $c['anim_speed'], 'z' => $c['size'], 'o' => $c['pos'], 'sc' => $c['anim_mode'] === 'scroll' ? 1 : 0, 'l' => $c['anim_loop'] === 'once' ? 1 : 0];
            }
            if ($c['anim'] === 'gradient-shift' && self::bgGradient($c)) {
                $out[] = ['s' => $sel, 'a' => 'gradient-shift', 'sc' => $c['anim_mode'] === 'scroll' ? 1 : 0, 'l' => $c['anim_loop'] === 'once' ? 1 : 0];
            }
            if ($c['video_path'] === '') {
                continue;
            }
            if ($c['video_path'] === '') {
                continue;
            }
            $out[] = ['s' => $sel, 'v' => self::uploadUrl($c['video_path']), 'p' => $c['poster_path'] !== '' ? self::uploadUrl($c['poster_path']) : ''];
        }

        return $out;
    }

    private static function uploadUrl(string $rel): string
    {
        return function_exists('upload_url') ? (string) upload_url($rel) : '/uploads/'.ltrim($rel, '/');
    }

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

        // animated H1 underline colour -----------------------------------
        if (($u = self::color('h1_underline_color')) !== null) {
            $r[] = ':root{--h1u:'.$u.'}';
        }

        // per-element backgrounds (colour / gradient / image / video overlay) ---
        foreach (self::bgCss() as $rule) {
            $r[] = $rule;
        }

        // GDPR cookie banner look --------------------------------------------
        foreach (self::ccCss() as $rule) {
            $r[] = $rule;
        }

        // progressive blur at the bottom of the viewport ------------------
        if (self::get('blur_on') === '1') {
            $r[] = ':root{--pblur-h:'.max(5, min(50, (int) self::get('blur_height'))).'vh}';
        }

        // one-page mode ----------------------------------------------------
        if (self::get('op_on') === '1') {
            $r[] = ':root{--op-ms:'.max(200, min(2000, (int) self::get('op_duration'))).'ms;--op-stagger:'.max(0, min(400, (int) self::get('op_stagger'))).'ms}';
        }

        // own fonts (@font-face) ------------------------------------------
        array_unshift($r, ...self::fontFaceCss());

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
        if (preg_match('/^f([1-6])$/', $k, $m)) {
            $f = self::customFonts()[(int) $m[1]] ?? null;

            return $f ? '"'.$f['name'].'",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif' : null;
        }

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
