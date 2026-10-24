<?php

namespace App\Support;

/**
 * Content effects, all configured in Settings > Design (no migration, values live in `settings`):
 *  - progress bars: up to 5 named sets, inserted anywhere with  [bars my-slug]  (content body, HTML widget, blocks)
 *      rows are typed one per line:  Label | 85 | #ff6600 | optional note
 *  - rotating text:  [rotate First text | Second text | Third text]
 * Pure PHP except setting()/Design::get(), so it is testable on its own (tests/standalone.php).
 */
class Fx
{
    public const SETS = 5;
    public const MAX_ROWS = 20;
    public const STYLES = [
        'bar' => 'Лента с райета и блясък',
        'wave' => 'Лента с вълна',
        'ring' => 'Кръг',
        'dots' => 'Точки (светват една по една)',
    ];

    /* ------------------------------------------------------------ parsing */

    public static function slug(?string $s): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $s)), '-');
    }

    private static function text(?string $s, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $s))), 0, $max);
    }

    /** "Label | 85 | #ff6600 | note" per line -> [['label','pct','color','note'], ...] */
    public static function parseRows(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            $p = array_map('trim', explode('|', $line));
            $label = self::text($p[0] ?? '', 80);
            if ($label === '') {
                continue;
            }
            $pct = isset($p[1]) && is_numeric(str_replace(['%', ','], ['', '.'], $p[1])) ? (float) str_replace(['%', ','], ['', '.'], $p[1]) : 0;
            $rows[] = [
                'label' => $label,
                'pct' => (string) max(0, min(100, (int) round($pct))),
                'color' => Design::normalizeColor($p[2] ?? '') ?? '',
                'note' => self::text($p[3] ?? '', 80),
            ];
            if (count($rows) >= self::MAX_ROWS) {
                break;
            }
        }

        return $rows;
    }

    public static function rowsText(array $rows): string
    {
        return implode("\n", array_map(fn ($r) => $r['label'].' | '.$r['pct'].(($r['color'] ?? '') !== '' || ($r['note'] ?? '') !== '' ? ' | '.($r['color'] ?? '') : '').(($r['note'] ?? '') !== '' ? ' | '.$r['note'] : ''), $rows));
    }

    /** Whitelist-clean the posted sets (keys 1..SETS). Rows may come as text ("rows_text") or as parsed arrays. */
    public static function normalizeBars(array $in): array
    {
        $out = [];
        for ($i = 1; $i <= self::SETS; $i++) {
            $c = is_array($in[$i] ?? null) ? $in[$i] : [];
            $rows = is_array($c['rows'] ?? null) ? $c['rows'] : self::parseRows((string) ($c['rows_text'] ?? ''));
            $out[(string) $i] = [
                'slug' => self::slug($c['slug'] ?? ''),
                'title' => self::text($c['title'] ?? '', 100),
                'style' => is_string($c['style'] ?? null) && isset(self::STYLES[$c['style']]) ? $c['style'] : 'bar',
                'color' => Design::normalizeColor((string) ($c['color'] ?? '')) ?? '',
                'track' => Design::normalizeColor((string) ($c['track'] ?? '')) ?? '',
                'speed' => (string) max(400, min(5000, is_numeric($c['speed'] ?? null) ? (int) $c['speed'] : 1400)),
                'counter' => in_array((string) ($c['counter'] ?? '1'), ['0', ''], true) ? '0' : '1',   // on by default; the form posts 0 when unticked
                'rows' => $rows,
            ];
        }

        return $out;
    }

    public static function used(array $set): bool
    {
        return $set['slug'] !== '' && $set['rows'] !== [];
    }

    /** Saved sets (normalised). */
    public static function bars(): array
    {
        $raw = function_exists('setting') ? setting('design.fx_bars') : null;
        $arr = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return self::normalizeBars(is_array($arr) ? $arr : []);
    }

    /* ----------------------------------------------------------- rendering */

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function renderBars(array $set): string
    {
        $style = $set['style'];
        $vars = '--pb-ms:'.(int) $set['speed'].'ms'.($set['color'] !== '' ? ';--pb-c:'.$set['color'] : '').($set['track'] !== '' ? ';--pb-t:'.$set['track'] : '');
        $h = '<div class="pbars pbs-'.$style.'" data-pbars style="'.$vars.'">';
        if ($set['title'] !== '') {
            $h .= '<div class="pb-title">'.self::e($set['title']).'</div>';
        }
        $h .= '<div class="pb-list">';
        foreach ($set['rows'] as $n => $r) {
            $p = (int) $r['pct'];
            $item = '--p:'.$p.';--i:'.$n.($r['color'] !== '' ? ';--pb-c:'.$r['color'] : '');
            $val = $set['counter'] === '1' ? '<span class="pb-val" data-to="'.$p.'">'.$p.'%</span>' : '';
            $note = $r['note'] !== '' ? '<span class="pb-note">'.self::e($r['note']).'</span>' : '';
            $label = '<span class="pb-label">'.self::e($r['label']).'</span>';
            $h .= '<div class="pb-item" style="'.$item.'" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.$p.'" aria-label="'.self::e($r['label']).'">';
            if ($style === 'ring') {
                $h .= '<svg class="pb-svg" viewBox="0 0 36 36" aria-hidden="true"><circle class="pb-rt" cx="18" cy="18" r="15.9155"/><circle class="pb-rf" cx="18" cy="18" r="15.9155" pathLength="100"/></svg>'
                    .'<div class="pb-mid">'.$val.'</div><div class="pb-cap">'.$label.$note.'</div>';
            } elseif ($style === 'dots') {
                $on = (int) round($p / 5);
                $dots = '';
                for ($d = 0; $d < 20; $d++) {
                    $dots .= '<i'.($d < $on ? ' class="on"' : '').' style="--d:'.$d.'"></i>';
                }
                $h .= '<div class="pb-head">'.$label.$note.$val.'</div><div class="pb-dots" aria-hidden="true">'.$dots.'</div>';
            } else {
                $h .= '<div class="pb-head">'.$label.$note.$val.'</div><div class="pb-track" aria-hidden="true"><div class="pb-fill"></div></div>';
            }
            $h .= '</div>';
        }

        return $h.'</div></div>';
    }

    public static function renderRotate(string $spec, int $interval = 2600): string
    {
        $items = array_values(array_filter(array_map(fn ($t) => self::text($t, 120), explode('|', $spec)), fn ($t) => $t !== ''));
        if (! $items) {
            return '';
        }
        $interval = max(1000, min(10000, $interval));
        $h = '<span class="fx-rotate" data-fx-rotate data-interval="'.$interval.'">';
        foreach ($items as $i => $t) {
            $h .= '<span'.($i === 0 ? ' class="is-active"' : '').'>'.self::e($t).'</span>';
        }

        return $h.'</span>';
    }

    /** Replace [bars slug] and [rotate a | b | c] in rendered content. Unknown slugs are removed. */
    public static function expand(string $html): string
    {
        if (! str_contains($html, '[bars') && ! str_contains($html, '[rotate')) {
            return $html;
        }
        if (str_contains($html, '[bars')) {
            $sets = [];
            foreach (self::bars() as $s) {
                if (self::used($s)) {
                    $sets[$s['slug']] = $s;
                }
            }
            $html = preg_replace_callback('/\[bars\s+([a-z0-9\-]{1,40})\s*\]/', fn ($m) => isset($sets[$m[1]]) ? self::renderBars($sets[$m[1]]) : '', $html) ?? $html;
        }
        if (str_contains($html, '[rotate')) {
            $iv = (int) Design::get('rotate_interval') ?: 2600;
            $html = preg_replace_callback('/\[rotate\s+([^\]]{1,600})\]/u', fn ($m) => self::renderRotate(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), $iv), $html) ?? $html;
        }

        return $html;
    }
}
