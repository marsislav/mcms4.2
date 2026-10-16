<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTranslation extends Model
{
    protected $fillable = [
        'content_id', 'locale', 'title', 'slug', 'excerpt', 'body', 'meta_title', 'meta_description',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class)->latest('id');
    }

    public function url(?bool $absolute = true): string
    {
        $c = $this->content;
        if ($c->type === 'page' && $c->is_home) {
            return lroute('home', [], $this->locale, $absolute);
        }

        return lroute($c->type === 'post' ? 'post' : 'page', ['slug' => $this->slug], $this->locale, $absolute);
    }
}
