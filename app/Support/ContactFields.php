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
        'number' => 'Число', 'date' => 'Дата', 'select' => 'Падащ списък', 'checkbox' => 'Отметка',
    ];

    public const MAX = 20;
    public const MAX_JSON = 20000;
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

    /**
     * Admin rows -> clean list. Row: key?, type, label[locale], required, options (text, one per line), del.
     *
     * @return array<int,array{key:string,type:string,label:array,required:bool,options:array}>
     */
    public static function clean(array $rows, array $locales): array
    {
        $out = [];
        $used = [];
        foreach ($rows as $r) {
            if (! is_array($r) || ! empty($r['del'])) {
                continue;
            }
            $label = [];
            foreach ($locales as $l) {
                $t = trim(strip_tags((string) ($r['label'][$l] ?? '')));
                if ($t !== '') {
                    $label[$l] = mb_substr($t, 0, 100);
                }
            }
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
            if ($type === 'select') {
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
            $out[] = ['key' => $key, 'type' => $type, 'label' => $label, 'required' => ! empty($r['required']), 'options' => $opts];
            if (count($out) >= self::MAX) {
                break;
            }
        }

        return $out;
    }

    /** Laravel validation rules, keyed by form input name. */
    public static function rules(array $fields): array
    {
        $rules = [];
        foreach ($fields as $f) {
            $req = ! empty($f['required']) ? 'required' : 'nullable';
            $rules[self::PREFIX.$f['key']] = match ($f['type']) {
                'email' => [$req, 'email:rfc', 'max:190'],
                'tel' => [$req, 'regex:/^[0-9+()\-\s.]{5,30}$/'],
                'url' => [$req, 'url', 'max:300'],
                'number' => [$req, 'numeric'],
                'date' => [$req, 'date'],
                'textarea' => [$req, 'string', 'max:2000'],
                'select' => [$req, 'string', function ($attr, $value, $fail) use ($f) {
                    if (! in_array($value, self::options($f), true)) {
                        $fail(__('Невалидна стойност.'));
                    }
                }],
                'checkbox' => ! empty($f['required']) ? ['accepted'] : ['nullable'],
                default => [$req, 'string', 'max:200'],
            };
        }

        return $rules;
    }

    /** "Label: value" lines for the message body (empty values are skipped, checkboxes show yes/no). */
    public static function lines(array $fields, array $input, ?string $locale = null): string
    {
        $out = [];
        foreach ($fields as $f) {
            $v = $input[self::PREFIX.$f['key']] ?? null;
            if ($f['type'] === 'checkbox') {
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
