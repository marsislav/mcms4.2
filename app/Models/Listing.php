<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Listing extends Model
{
    // user_id / status / dates are intentionally NOT mass-assignable
    protected $fillable = ['title', 'body', 'price', 'currency', 'city', 'contact_name', 'contact_phone', 'contact_email', 'days', 'category_id'];

    protected function casts(): array
    {
        return [
            'media_ids' => 'array',
            'price' => 'decimal:2',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Live on the public site: approved and not expired. */
    public function scopeVisible($q)
    {
        return $q->where('status', 'published')->where('expires_at', '>', now());
    }

    public function category()
    {
        return $this->belongsTo(ListingCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->status === 'published' && $this->expires_at && $this->expires_at->isPast();
    }

    /** pending | published | expired | rejected */
    public function state(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }

    public function stateLabel(): string
    {
        return match ($this->state()) {
            'pending' => t('listings.st_pending', 'Чака одобрение'),
            'published' => t('listings.st_published', 'Активна'),
            'expired' => t('listings.st_expired', 'Изтекла'),
            'rejected' => t('listings.st_rejected', 'Отхвърлена'),
            default => $this->status,
        };
    }

    public function slugFor(): string
    {
        return Str::slug($this->title, '-', 'bg') ?: 'obyava';
    }

    public function url(?string $locale = null): string
    {
        return lroute('listings.show', ['id' => $this->id, 'slug' => $this->slug ?: $this->slugFor()], $locale);
    }

    public function priceLabel(): string
    {
        if ($this->price === null) {
            return t('listings.negotiable', 'По договаряне');
        }
        if ((float) $this->price == 0.0) {
            return t('listings.free', 'Безплатно');
        }
        $n = preg_replace('/,00$/', '', number_format((float) $this->price, 2, ',', ' '));

        return $n.' '.($this->currency === 'BGN' ? 'лв.' : '€');
    }

    /** @return \Illuminate\Support\Collection<int, Media> photos in the saved order */
    public function images()
    {
        $ids = array_values(array_filter((array) $this->media_ids, 'is_numeric'));
        if (! $ids) {
            return collect();
        }
        $found = Media::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $found->get((int) $id))->filter()->values();
    }
}
