<?php

namespace App\Support;

/**
 * Extra fields of the public contact form (name, email, subject and message stay built-in).
 * Stored as JSON in settings `contact.fields`; submitted values are appended to the message body as
 * "Label: value" lines, so no database migration is needed. clean()/rules()/lines() are pure PHP (unit-tested).
 */
class ContactFields
{
    public const TYPES = [
        'text' => 'Текст', 'textarea' => 'Дълъг текст', 'email' => 'Имейл', 'tel' => 'Телефон', 'url' => 'Уебсайт',
        'number' => 'Число', 'date' => 'Дата', 'time' => 'Час', 'select' => 'Падащ списък', 'radio' => 'Избор (радио бутони)',
        'checkbox' => 'Отметка', 'checkboxes' => 'Няколко отметки', 'heading' => 'Заглавие / разделител (без поле)',
    ];

    /** Types that take a list of options (one per line in the admin). */
    public const CHOICE = ['select', 'radio', 'checkboxes'];
    /** Types that show a free-text placeholder. */
    public const PLACEHOLDER = ['text', 'textarea', 'email', 'tel', 'url', 'number'];
    public const WIDTHS = ['full' => 'Цяла ширина', 'half' => '1/2', 'third' => '1/3', 'twothirds' => '2/3'];

    public const MAX = 30;
    public const MAX_JSON = 40000;
    public const PREFIX = 'f_';

    public static function decode(?string $json): array
    {
        $a = $json ? json_decode($json, true) : null;

        return is_array($a) ? array_values(array_filter($a, fn ($f) => is_array($f) && isset($f['key'], $f['type']))) : [];
    }

    public static function all(): array
    {
        return self::decode((string) setting('contact.fields'));
    }

    /** Label in the given locale, then the default language, then any. */
    public static function label(array $f, ?string $locale = null): string
    {
        $v = $f['label'] ?? [];
        if (! is_array($v)) {
            return (string) $v;
        }
        foreach ([$locale ?? app()->getLocale(), function_exists('mcms_default_locale') ? mcms_default_locale() : 'bg'] as $l) {
            if (isset($v[$l]) && trim((string) $v[$l]) !== '') {
                return (string) $v[$l];
            }
        }
        foreach ($v as $x) {
            if (trim((string) $x) !== '') {
                return (string) $x;
            }
        }

        return '';
    }

    public static function options(array $f): array
    {
        $o = $f['options'] ?? [];

        return is_array($o) ? array_values($o) : [];
    }

    /** Fills defaults for fields saved by an older version (placeholder, help, width ...). */
    public static function normalize(array $f): array
    {
        return $f + ['label' => [], 'placeholder' => [], 'help' => [], 'default' => '', 'required' => false, 'options' => [],
            'width' => 'full', 'minlen' => null, 'maxlen' => null, 'min' => '', 'max' => '', 'rows' => 4, 'class' => ''];
    }

    private static function perLocale(mixed $v, array $locales, int $max): array
    {
        $out = [];
        foreach ($locales as $l) {
            $t = trim(strip_tags((string) (is_array($v) ? ($v[$l] ?? '') : '')));
            if ($t !== '') {
                $out[$l] = mb_substr($t, 0, $max);
            }
        }

        return $out;
    }

    /**
     * Admin rows (in display order) -> clean list.
     * Row: key?, type, label[l], placeholder[l], help[l], default, required, options (text, one per line), width,
     *      minlen/maxlen (text), min/max (number, date), rows (textarea), class, del.
     *
     * @return array<int,array>
     */
    public static function clean(array $rows, array $locales): array
    {
        $out = [];
        $used = [];
        foreach ($rows as $r) {
            if (! is_array($r) || ! empty($r['del'])) {
                continue;
            }
            $label = self::perLocale($r['label'] ?? [], $locales, 100);
            if (! $label) {
                continue; // no label in any language = empty row
            }
            $type = isset(self::TYPES[$r['type'] ?? '']) ? $r['type'] : 'text';
            $key = (string) ($r['key'] ?? '');
            if (! preg_match('/^[a-z0-9]{3,12}$/', $key) || isset($used[$key])) {
                do {
                    $key = substr(md5(uniqid('', true)), 0, 6);
                } while (isset($used[$key]));
            }
            $used[$key] = true;

            $opts = [];
            if (in_array($type, self::CHOICE, true)) {
                foreach (preg_split('/\R/', (string) ($r['options'] ?? '')) ?: [] as $o) {
                    $o = trim(strip_tags($o));
                    if ($o !== '') {
                        $opts[$o] = mb_substr($o, 0, 100);
                    }
                }
                $opts = array_slice(array_values($opts), 0, 30);
                if (count($opts) < 1) {
                    $type = 'text'; // a list without options is useless
                }
            }
            $int = fn ($v, $min, $max) => is_numeric($v) && $v !== '' ? max($min, min($max, (int) $v)) : null;
            $num = fn ($v) => is_numeric($v) ? (string) (0 + $v) : '';
            $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? (string) $v : '';
            $isNum = $type === 'number';
            $isDate = $type === 'date';
            $default = mb_substr(trim(strip_tags((string) ($r['default'] ?? ''))), 0, 200);
            if ($type === 'checkbox') {
                $default = ! empty($r['default']) && $r['default'] !== '0' ? '1' : '';
            } elseif (in_array($type, self::CHOICE, true)) {
                $default = array_intersect(preg_split('/\R/', $default) ?: [], $opts) ? $default : '';
            }
            $class = trim(preg_replace('/\s+/', ' ', (string) ($r['class'] ?? '')));
            $class = preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*( [A-Za-z_][A-Za-z0-9_\-]*)*$/', $class) && strlen($class) <= 80 ? $class : '';

            $out[] = [
                'key' => $key, 'type' => $type, 'label' => $label,
                'placeholder' => in_array($type, self::PLACEHOLDER, true) ? self::perLocale($r['placeholder'] ?? [], $locales, 120) : [],
                'help' => self::perLocale($r['help'] ?? [], $locales, 300),
                'default' => $type === 'heading' ? '' : $default,
                'required' => $type !== 'heading' && ! empty($r['required']),
                'options' => $opts,
                'width' => isset(self::WIDTHS[$r['width'] ?? '']) ? $r['width'] : 'full',
                'minlen' => in_array($type, ['text', 'textarea'], true) ? $int($r['minlen'] ?? null, 0, 2000) : null,
                'maxlen' => in_array($type, ['text', 'textarea'], true) ? $int($r['maxlen'] ?? null, 1, 2000) : null,
                'min' => $isNum ? $num($r['min'] ?? '') : ($isDate ? $date($r['min'] ?? '') : ''),
                'max' => $isNum ? $num($r['max'] ?? '') : ($isDate ? $date($r['max'] ?? '') : ''),
                'rows' => $type === 'textarea' ? ($int($r['rows'] ?? null, 2, 20) ?? 4) : 4,
                'class' => $class,
            ];
            if (count($out) >= self::MAX) {
                break;
            }
        }

        return $out;
    }

    /** Per-language text of a field attribute (placeholder / help / label) with fallback; '' if none. */
    public static function text(array $f, string $attr, ?string $locale = null): string
    {
        return self::label(['label' => $f[$attr] ?? []], $locale);
    }

    /** Laravel validation rules, keyed by form input name. */
    public static function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $f = self::normalize($f);
            if ($f['type'] === 'heading') {
                continue;
            }
            $name = self::PREFIX.$f['key'];
            $req = ! empty($f['required']) ? 'required' : 'nullable';
            $in = function ($attr, $value, $fail) use ($f) {
                if (! in_array($value, self::options($f), true)) {
                    $fail(__('Невалидна стойност.'));
                }
            };
            switch ($f['type']) {
                case 'email':
                    $rules[$name] = [$req, 'email:rfc', 'max:190'];
                    break;
                case 'tel':
                    $rules[$name] = [$req, 'regex:/^[0-9+()\-\s.]{5,30}$/'];
                    break;
                case 'url':
                    $rules[$name] = [$req, 'url', 'max:300'];
                    break;
                case 'number':
                    $rules[$name] = array_values(array_filter([$req, 'numeric', $f['min'] !== '' ? 'min:'.$f['min'] : null, $f['max'] !== '' ? 'max:'.$f['max'] : null]));
                    break;
                case 'date':
                    $rules[$name] = array_values(array_filter([$req, 'date', $f['min'] !== '' ? 'after_or_equal:'.$f['min'] : null, $f['max'] !== '' ? 'before_or_equal:'.$f['max'] : null]));
                    break;
                case 'time':
                    $rules[$name] = [$req, 'date_format:H:i'];
                    break;
                case 'textarea':
                case 'text':
                    $rules[$name] = array_values(array_filter([$req, 'string', 'min:'.(int) ($f['minlen'] ?? 0), 'max:'.(int) ($f['maxlen'] ?: ($f['type'] === 'textarea' ? 2000 : 200))]));
                    break;
                case 'select':
                case 'radio':
                    $rules[$name] = [$req, 'string', $in];
                    break;
                case 'checkboxes':
                    $rules[$name] = [$req, 'array', 'max:30'];
                    $rules[$name.'.*'] = ['string', $in];
                    break;
                case 'checkbox':
                    $rules[$name] = ! empty($f['required']) ? ['accepted'] : ['nullable'];
                    break;
            }
        }

        return $rules;
    }

    /** "Label: value" lines for the message body (empty values are skipped, checkboxes show yes/no). */
    public static function lines(array $fields, array $input, ?string $locale = null): string
    {
        $out = [];
        foreach ($fields as $f) {
            if ($f['type'] === 'heading') {
                continue;
            }
            $v = $input[self::PREFIX.$f['key']] ?? null;
            if ($f['type'] === 'checkboxes') {
                $picked = array_values(array_intersect(self::options($f), array_map('strval', (array) $v)));
                if (! $picked) {
                    continue;
                }
                $v = implode(', ', $picked);
            } elseif ($f['type'] === 'checkbox') {
                $v = ! empty($v) && $v !== '0' ? __('Да') : __('Не');
            } else {
                $v = trim(strip_tags((string) $v));
                if ($f['type'] !== 'textarea') {
                    $v = trim(preg_replace('/\s+/u', ' ', $v));
                }
                if ($v === '') {
                    continue;
                }
            }
            $out[] = self::label($f, $locale).': '.$v;
        }

        return implode("\n", $out);
    }
}
