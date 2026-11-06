<?php

namespace App\Support;

use App\Models\Listing;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/** Settings and helpers for the classified ads module (config/mcms.php → 'listings'). */
class Listings
{
    public static function ready(): bool
    {
        return Tables::has('listings');
    }

    public static function categoriesReady(): bool
    {
        return Tables::has('listing_categories') && Tables::column('listings', 'category_id');
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\ListingCategory> */
    public static function categories()
    {
        return self::categoriesReady() ? \App\Models\ListingCategory::orderBy('sort')->orderBy('name')->get() : collect();
    }

    public static function abortUnlessReady(): void
    {
        abort_unless(self::enabled(), 404);
    }

    /** Module switch (Settings > Обяви) and a migrated database. */
    public static function enabled(): bool
    {
        return Options::on('listings_on') && self::ready();
    }

    public static function approval(): bool
    {
        return Options::on('listings_approval');
    }

    /** @return int[] allowed validity periods in days */
    public static function dayOptions(): array
    {
        $d = array_values(array_filter(array_map('intval', explode(',', Options::get('listings_days'))), fn ($x) => $x > 0));

        return $d ?: [30];
    }

    public static function maxImages(): int
    {
        return max(0, (int) Options::get('listings_images'));
    }

    public static function perPage(): int
    {
        return max(3, (int) Options::get('listings_per_page'));
    }

    public static function perUser(): int
    {
        return max(1, (int) config('mcms.listings.per_user', 30));
    }

    /** First photo of every listing in one query: [listing id => Media]. */
    public static function thumbs(Collection $listings): array
    {
        $first = [];
        foreach ($listings as $l) {
            $ids = array_values(array_filter((array) $l->media_ids, 'is_numeric'));
            if ($ids) {
                $first[$l->id] = (int) $ids[0];
            }
        }
        if (! $first) {
            return [];
        }
        $media = Media::whereIn('id', array_unique($first))->get()->keyBy('id');
        $out = [];
        foreach ($first as $lid => $mid) {
            if ($media->has($mid)) {
                $out[$lid] = $media->get($mid);
            }
        }

        return $out;
    }

    public static function alternates(string $name, array $params = []): array
    {
        return collect(mcms_locales())->mapWithKeys(fn ($l) => [$l => lroute($name, $params, $l)])->all();
    }

    public static function crumbs(array $trail): array
    {
        $home = Options::loc('bc_home', __('Начало'));

        return Breadcrumbs::make(array_map(fn ($c) => ['label' => $c[0], 'url' => $c[1] ?? null], $trail), $home, lroute('home'));
    }

    /** Make a listing live for its chosen number of days (approval, or auto-publish for moderators). */
    public static function publish(Listing $l): void
    {
        $l->status = 'published';
        $l->published_at = $l->published_at ?: now();
        $l->expires_at = now()->addDays(max(1, (int) $l->days));
    }

    public static function mail(?string $to, string $subject, string $body): void
    {
        if (! $to) {
            return;
        }
        try {
            Mail::raw($body, function ($m) use ($to, $subject) {
                $m->to($to)->subject('['.setting('site_name', 'mCMS').'] '.$subject);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function notifyAdmin(Listing $l): void
    {
        if (! Options::on('listings_notify')) {
            return;
        }
        $to = setting('contact_email') ?: User::where('role', 'admin')->where('active', true)->value('email');
        self::mail($to, __('Нова обява'), __('Нова обява: :t', ['t' => $l->title])."\n".admin_url('listings/'.$l->id.'/edit'));
    }
}
