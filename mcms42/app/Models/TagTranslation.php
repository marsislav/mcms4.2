<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['tag_id', 'locale', 'name', 'slug'];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    /** URL in the *current* site language (the page itself falls back to this slug in any language). */
    public function url(?string $locale = null): string
    {
        return lroute('tag', ['slug' => $this->slug], $locale ?? app()->getLocale());
    }
}
