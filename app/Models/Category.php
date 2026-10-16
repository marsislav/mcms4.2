<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use \App\Models\Concerns\Trashable;

    protected $fillable = ['sort'];

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function tr(?string $locale = null): ?CategoryTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }

    /** Name for admin lists: default language first, then anything available. */
    public function adminName(): string
    {
        return $this->tr(mcms_default_locale())?->name
            ?? $this->translations->first()?->name
            ?? '#'.$this->id;
    }
}
