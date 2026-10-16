<?php

namespace App\Support;

use App\Models\Content;
use App\Models\Tag;
use App\Models\TagTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tags and related posts: parsing, saving and the "what to show under a post" algorithm. */
class Taxonomy
{
    public const MAX_TAGS = 20;
    public const MAX_RELATED = 12;

    /** False until the "tags" migration has been run (existing installs after an upgrade). */
    public static function ready(): bool
    {
        return Cache::remember('mcms.tags_ready', 60, function () {
            try {
                return Schema::hasTable('tags') && Schema::hasTable('content_related');
            } catch (\Throwable) {
                return false;
            }
        }) === true;
    }

    /** "php,  Laravel, php" -> ['php', 'Laravel'] (trimmed, no HTML, case-insensitive unique, max 20). */
    public static function parse(string $input): array
    {
        $out = [];
        foreach (preg_split('/[,;\n]+/u', $input) ?: [] as $part) {
            $name = trim(preg_replace('/\s+/u', ' ', strip_tags($part)) ?? '');
            $name = mb_substr($name, 0, 80);
            if ($name === '') {
                continue;
            }
            $out[mb_strtolower($name)] ??= $name;
        }

        return array_slice(array_values($out), 0, self::MAX_TAGS);
    }

    /** Replace the tags of a post; unknown names create new tags (in the default language). */
    public static function syncTags(Content $content, string $input): void
    {
        $default = mcms_default_locale();
        $known = TagTranslation::where('locale', $default)->get()->keyBy(fn ($t) => mb_strtolower($t->name));

        $ids = [];
        foreach (self::parse($input) as $name) {
            $hit = $known->get(mb_strtolower($name));
            if ($hit) {
                $ids[] = $hit->tag_id;
                continue;
            }
            $tag = DB::transaction(function () use ($name, $default) {
                $tag = Tag::create();
                TagTranslation::create([
                    'tag_id' => $tag->id, 'locale' => $default, 'name' => $name,
                    'slug' => ContentSaver::uniqueSlug($name, $default, null, 'tag'),
                ]);

                return $tag;
            });
            $ids[] = $tag->id;
        }

        $content->tags()->sync(array_values(array_unique($ids)));
    }

    /** @param array<int,mixed> $ids */
    public static function syncRelated(Content $content, array $ids): void
    {
        $ids = collect($ids)->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0 && $v !== $content->id)
            ->unique()->take(self::MAX_RELATED)->values();
        $valid = Content::where('type', 'post')->whereIn('id', $ids)->pluck('id')->all();

        $sync = [];
        foreach ($ids->filter(fn ($id) => in_array($id, $valid, true))->values() as $i => $id) {
            $sync[$id] = ['sort' => $i];
        }
        $content->related()->sync($sync);
    }

    /**
     * Related posts for the public site: manual picks first, then posts sharing the most tags,
     * then posts from the same category. Only visible posts that exist in the current language.
     *
     * @return Collection<int, Content>
     */
    public static function relatedFor(Content $post, int $limit): Collection
    {
        if ($limit < 1 || ! self::ready()) {
            return collect();
        }
        $locale = app()->getLocale();
        $base = fn () => Content::visible()->where('type', 'post')
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale))
            ->with(['translations', 'featured', 'category.translations']);

        $picked = $base()->whereIn('id', $post->related()->pluck('contents.id'))->get()
            ->sortBy(fn ($c) => $post->related->firstWhere('id', $c->id)?->pivot->sort ?? 0)->values();
        $result = $picked->take($limit);

        $skip = $result->pluck('id')->push($post->id)->all();

        if ($result->count() < $limit && ($tagIds = $post->tags()->pluck('tags.id')->all())) {
            $byTags = $base()->whereNotIn('id', $skip)
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->withCount(['tags as shared_tags' => fn ($q) => $q->whereIn('tags.id', $tagIds)])
                ->orderByDesc('shared_tags')->orderByDesc('publish_at')
                ->limit($limit - $result->count())->get();
            $result = $result->concat($byTags);
            $skip = array_merge($skip, $byTags->pluck('id')->all());
        }

        if ($result->count() < $limit && $post->category_id) {
            $byCat = $base()->whereNotIn('id', $skip)->where('category_id', $post->category_id)
                ->orderByDesc('publish_at')->limit($limit - $result->count())->get();
            $result = $result->concat($byCat);
        }

        return $result->values();
    }
}
