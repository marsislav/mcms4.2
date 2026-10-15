<?php

namespace App\Support;

use App\Models\CategoryTranslation;
use App\Models\Content;
use App\Models\ContentTranslation;
use App\Models\Revision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Shared create/update logic for pages and posts (translations, slugs, revisions, sanitising). */
class ContentSaver
{
    /**
     * @param  array<string, mixed>  $data   validated content-level fields
     * @param  array<string, array>  $tr     translations keyed by locale
     */
    public static function save(?Content $content, string $type, array $data, array $tr, ?int $userId): Content
    {
        $locales = mcms_locales();
        $default = mcms_default_locale();

        // Keep only submitted, non-empty translations of active languages
        $clean = [];
        foreach ($locales as $loc) {
            $row = $tr[$loc] ?? [];
            $title = trim((string) ($row['title'] ?? ''));
            $body = trim((string) ($row['body'] ?? ''));
            if ($title === '' && $body === '' && $loc !== $default) {
                continue;
            }
            if ($title === '') {
                throw ValidationException::withMessages(["tr.$loc.title" => 'Заглавието е задължително.']);
            }
            $clean[$loc] = $row + ['title' => $title];
        }
        if (! isset($clean[$default])) {
            throw ValidationException::withMessages(["tr.$default.title" => 'Заглавие на основния език е задължително.']);
        }

        return DB::transaction(function () use ($content, $type, $data, $clean, $userId) {
            $data['type'] = $type;
            $data['is_home'] = $type === 'page' && ! empty($data['is_home']);
            $data['noindex'] = ! empty($data['noindex']);
            $data['sort'] = (int) ($data['sort'] ?? $content?->sort ?? 0);
            if ($type !== 'post') {
                $data['category_id'] = null;
            }
            if ($data['status'] === 'published' && empty($data['publish_at'])) {
                $data['publish_at'] = $content?->publish_at ?? now();
            }
            if ($data['status'] === 'draft') {
                $data['publish_at'] = $data['publish_at'] ?? null;
            }

            if ($content) {
                $content->update($data);
            } else {
                $content = Content::create($data + ['author_id' => $userId]);
            }

            if ($content->is_home) {
                Content::where('id', '!=', $content->id)->where('is_home', true)->update(['is_home' => false]);
            }

            foreach ($clean as $loc => $row) {
                $slugSource = trim((string) ($row['slug'] ?? '')) ?: $row['title'];
                $existing = $content->translations()->where('locale', $loc)->first();
                $slug = self::uniqueSlug($slugSource, $loc, $existing?->id);

                $fields = [
                    'title' => mb_substr($row['title'], 0, 190),
                    'slug' => $slug,
                    'excerpt' => self::nullable(strip_tags((string) ($row['excerpt'] ?? '')), 1000),
                    'body' => Sanitizer::clean($row['body'] ?? ''),
                    'meta_title' => self::nullable(strip_tags((string) ($row['meta_title'] ?? '')), 190),
                    'meta_description' => self::nullable(strip_tags((string) ($row['meta_description'] ?? '')), 300),
                ];

                if ($existing) {
                    if ($existing->title !== $fields['title'] || $existing->body !== $fields['body']) {
                        Revision::create([
                            'content_translation_id' => $existing->id,
                            'user_id' => $userId,
                            'title' => $existing->title,
                            'body' => $existing->body,
                        ]);
                        $keep = Revision::where('content_translation_id', $existing->id)->orderByDesc('id')->offset(20)->limit(PHP_INT_MAX)->pluck('id');
                        if ($keep->isNotEmpty()) {
                            Revision::whereIn('id', $keep)->delete();
                        }
                    }
                    $existing->update($fields);
                } else {
                    ContentTranslation::create($fields + ['content_id' => $content->id, 'locale' => $loc]);
                }
            }

            // Drop translations for languages that were emptied in the form
            $content->translations()->whereNotIn('locale', array_keys($clean))->delete();

            \Illuminate\Support\Facades\Cache::forget('mcms.sitemap');
            Hooks::doAction('content.saved', $content);

            return $content;
        });
    }

    public static function uniqueSlug(string $source, string $locale, ?int $ignoreId = null, string $table = 'content'): string
    {
        $base = Str::slug($source, '-', substr($locale, 0, 2)) ?: 'item';
        $base = mb_substr($base, 0, 150);
        $reserved = config('mcms.reserved_slugs', []);
        $slug = $base;
        $i = 2;

        while (in_array($slug, $reserved, true) || preg_match('/^[a-z]{2}$/', $slug) || self::slugExists($slug, $locale, $ignoreId, $table)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private static function slugExists(string $slug, string $locale, ?int $ignoreId, string $table): bool
    {
        $model = $table === 'category' ? CategoryTranslation::class : ContentTranslation::class;

        return $model::where('locale', $locale)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
    }

    private static function nullable(string $v, int $max): ?string
    {
        $v = trim($v);

        return $v === '' ? null : mb_substr($v, 0, $max);
    }
}
