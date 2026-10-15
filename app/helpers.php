<?php

use App\Models\AuditLog;
use App\Models\Language;
use App\Models\Setting;
use App\Models\Translation;
use App\Support\Hooks;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

if (! function_exists('mcms_installed')) {
    function mcms_installed(): bool
    {
        return is_file(storage_path('installed.lock'));
    }
}

if (! function_exists('mcms_languages')) {
    /** Active languages as plain objects (code, name, native_name, is_default). */
    function mcms_languages(): Collection
    {
        if (! mcms_installed()) {
            return collect();
        }

        try {
            $rows = Cache::rememberForever('mcms.languages', fn () => Language::where('is_active', true)
                ->orderBy('sort')->orderBy('id')->get(['code', 'name', 'native_name', 'is_default'])->toArray());
        } catch (\Throwable) {
            return collect(); // database unavailable: never break error pages
        }

        return collect($rows)->map(fn ($r) => (object) $r);
    }
}

if (! function_exists('mcms_default_locale')) {
    function mcms_default_locale(): string
    {
        $langs = mcms_languages();

        return $langs->firstWhere('is_default', true)->code
            ?? $langs->first()->code
            ?? config('app.locale', 'en');
    }
}

if (! function_exists('mcms_locales')) {
    function mcms_locales(): array
    {
        return mcms_languages()->pluck('code')->all();
    }
}

if (! function_exists('mcms_tr')) {
    function mcms_tr(string $locale): array
    {
        if (! mcms_installed()) {
            return [];
        }

        try {
            return Cache::rememberForever('mcms.tr.'.$locale, fn () => Translation::where('locale', $locale)->pluck('value', 'key')->all());
        } catch (\Throwable) {
            return [];
        }
    }
}

if (! function_exists('t')) {
    /** Database-driven UI string with fallback to the default language. */
    function t(string $key, ?string $default = null, array $replace = []): string
    {
        $str = mcms_tr(app()->getLocale())[$key]
            ?? mcms_tr(mcms_default_locale())[$key]
            ?? $default
            ?? $key;

        foreach ($replace as $k => $v) {
            $str = str_replace(':'.$k, (string) $v, $str);
        }

        return $str;
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        if (! mcms_installed()) {
            return $default;
        }

        return Setting::get($key, $default);
    }
}

if (! function_exists('setting_l')) {
    /** Translatable setting: tries key.{locale}, then key.{default}, then key. */
    function setting_l(string $key, mixed $default = null): mixed
    {
        $v = setting($key.'.'.app()->getLocale());
        if ($v === null || $v === '') {
            $v = setting($key.'.'.mcms_default_locale());
        }

        return ($v === null || $v === '') ? (setting($key) ?? $default) : $v;
    }
}

if (! function_exists('lroute')) {
    /** Locale-aware public URL. Default language has no prefix. */
    function lroute(string $name, array $params = [], ?string $locale = null, bool $absolute = true): string
    {
        $locale ??= app()->getLocale();

        if ($locale === mcms_default_locale()) {
            return route('site.'.$name, $params, $absolute);
        }

        return route('site.l.'.$name, ['locale' => $locale] + $params, $absolute);
    }
}

if (! function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return url(config('mcms.admin_path').($path !== '' ? '/'.ltrim($path, '/') : ''));
    }
}

if (! function_exists('upload_url')) {
    function upload_url(?string $path): ?string
    {
        return $path ? asset('uploads/'.ltrim($path, '/')) : null;
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return app()->bound('csp_nonce') ? app('csp_nonce') : '';
    }
}

if (! function_exists('audit')) {
    function audit(string $action, ?string $subject = null, array|string|null $details = null): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => mb_substr($action, 0, 60),
                'subject' => $subject ? mb_substr($subject, 0, 190) : null,
                'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 250),
            ]);

            // Housekeeping: drop entries older than 180 days (1% of writes)
            if (random_int(1, 100) === 1) {
                AuditLog::where('created_at', '<', now()->subDays(180))->delete();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}

if (! function_exists('excerpt')) {
    function excerpt(?string $html, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return \Illuminate\Support\Str::limit($text, $limit);
    }
}

/* ---- Hook API (WordPress-like) ---- */

if (! function_exists('add_action')) {
    function add_action(string $tag, callable $cb, int $priority = 10): void
    {
        Hooks::addAction($tag, $cb, $priority);
    }
}
if (! function_exists('do_action')) {
    function do_action(string $tag, mixed ...$args): void
    {
        Hooks::doAction($tag, ...$args);
    }
}
if (! function_exists('add_filter')) {
    function add_filter(string $tag, callable $cb, int $priority = 10): void
    {
        Hooks::addFilter($tag, $cb, $priority);
    }
}
if (! function_exists('apply_filters')) {
    function apply_filters(string $tag, mixed $value, mixed ...$args): mixed
    {
        return Hooks::applyFilters($tag, $value, ...$args);
    }
}
