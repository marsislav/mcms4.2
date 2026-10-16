<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use \App\Models\Concerns\Trashable;

    protected $fillable = [
        'type', 'status', 'publish_at', 'category_id', 'featured_media_id',
        'is_home', 'noindex', 'sort', 'author_id',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'is_home' => 'boolean',
        'noindex' => 'boolean',
    ];

    public function translations()
    {
        return $this->hasMany(ContentTranslation::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'content_tag');
    }

    /** Manually chosen related posts (ordered). */
    public function related()
    {
        return $this->belongsToMany(self::class, 'content_related', 'content_id', 'related_id')
            ->withPivot('sort')->orderBy('content_related.sort');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function featured()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not scheduled for the future. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('publish_at')->orWhere('publish_at', '<=', now()));
    }

    public function isVisible(): bool
    {
        return $this->status === 'published' && (! $this->publish_at || $this->publish_at->lte(now()));
    }

    public function tr(?string $locale = null): ?ContentTranslation
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale);
    }

    public function adminTitle(): string
    {
        return $this->tr(mcms_default_locale())?->title
            ?? $this->translations->first()?->title
            ?? '#'.$this->id;
    }
}
