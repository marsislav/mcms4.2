<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuItem extends Model
{
    public $timestamps = false;

    public const BUILTIN = ['home', 'blog', 'contact', 'search'];

    protected $fillable = ['menu_id', 'parent_id', 'sort', 'type', 'content_id', 'url', 'labels', 'new_tab', 'css_class', 'css_id'];

    protected $casts = ['labels' => 'array', 'new_tab' => 'boolean'];

    /** Have the css_class / css_id columns been created (database update applied)? */
    public static function hasExtras(): bool
    {
        try {
            return Cache::remember('mcms.menu_extras', 60, fn () => Schema::hasColumn('menu_items', 'css_class')
                && Schema::hasColumn('menu_items', 'css_id')) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    /** Safe values only (also validated on save): class list and a single id. */
    public static function cleanClass(?string $v): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string) $v));

        return ($v !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*( [A-Za-z_][A-Za-z0-9_\-]*)*$/', $v) && strlen($v) <= 200) ? $v : null;
    }

    public static function cleanId(?string $v): ?string
    {
        $v = trim((string) $v);

        return ($v !== '' && preg_match('/^[A-Za-z][A-Za-z0-9_\-:.]{0,79}$/', $v)) ? $v : null;
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function label(string $locale): ?string
    {
        $l = $this->labels[$locale] ?? null;

        return ($l !== null && $l !== '') ? $l : null;
    }

    /** @return array{label:string,url:string,new_tab:bool,css_class:?string,css_id:?string}|null */
    public function resolve(string $locale): ?array
    {
        switch ($this->type) {
            case 'content':
                $c = $this->content;
                $tr = $c?->tr($locale);
                if (! $c || ! $tr || ! $c->isVisible()) {
                    return null;
                }
                $label = $this->label($locale) ?? $tr->title;
                $url = $tr->url();
                break;

            case 'builtin':
                if (! in_array($this->url, self::BUILTIN, true)) {
                    return null;
                }
                $label = $this->label($locale) ?? t('nav.'.$this->url);
                $url = lroute($this->url, [], $locale);
                break;

            default: // custom
                $label = $this->label($locale);
                if ($label === null || ! $this->url) {
                    return null;
                }
                $url = $this->url;
        }

        return [
            'label' => $label, 'url' => $url, 'new_tab' => (bool) $this->new_tab,
            'css_class' => self::cleanClass($this->attributes['css_class'] ?? null),
            'css_id' => self::cleanId($this->attributes['css_id'] ?? null),
        ];
    }
}
