<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public $timestamps = false;

    public function translations()
    {
        return $this->hasMany(TagTranslation::class);
    }

    public function contents()
    {
        return $this->belongsToMany(Content::class, 'content_tag');
    }

    /** Exact translation for a locale (or null). */
    public function tr(?string $locale = null): ?TagTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }

    /** Translation to show on the site: current language, else default language, else any. */
    public function display(?string $locale = null): ?TagTranslation
    {
        return $this->tr($locale) ?? $this->tr(mcms_default_locale()) ?? $this->translations->first();
    }

    public function adminName(): string
    {
        return $this->tr(mcms_default_locale())?->name ?? $this->translations->first()?->name ?? '#'.$this->id;
    }
}
