<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    public $timestamps = false;

    public const BUILTIN = ['home', 'blog', 'contact', 'search'];

    protected $fillable = ['menu_id', 'parent_id', 'sort', 'type', 'content_id', 'url', 'labels', 'new_tab'];

    protected $casts = ['labels' => 'array', 'new_tab' => 'boolean'];

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

    /** @return array{label:string,url:string,new_tab:bool}|null */
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

        return ['label' => $label, 'url' => $url, 'new_tab' => (bool) $this->new_tab];
    }
}
