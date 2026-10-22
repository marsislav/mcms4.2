<?php

namespace App\Support;

/**
 * schema.org JSON-LD nodes (Settings > SEO & navigation > "Structured data"). The builders are pure
 * (arrays in, arrays out) so they can be unit-tested; graph() gathers the data from the request.
 */
class StructuredData
{
    /** @param array<int,array{label:string,url:?string}> $crumbs */
    public static function breadcrumbList(array $crumbs): ?array
    {
        if (count($crumbs) < 2) {
            return null;
        }
        $items = [];
        foreach (array_values($crumbs) as $i => $c) {
            $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => (string) $c['label']];
            if (! empty($c['url'])) {
                $item['item'] = $c['url'];
            }
            $items[] = $item;
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    public static function website(string $name, string $url, ?string $searchUrlTemplate): array
    {
        $n = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $name, 'url' => $url];
        if ($searchUrlTemplate) {
            $n['potentialAction'] = ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $searchUrlTemplate], 'query-input' => 'required name=search_term_string'];
        }

        return $n;
    }

    /** BlogPosting for posts, WebPage for pages. $a: title, url, description, image, published, modified, author_name, author_url, type. */
    public static function entry(array $a): array
    {
        $isPost = ($a['type'] ?? 'post') === 'post';
        $n = [
            '@context' => 'https://schema.org',
            '@type' => $isPost ? 'BlogPosting' : 'WebPage',
            'headline' => mb_substr((string) $a['title'], 0, 110),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => (string) $a['url']],
        ];
        foreach (['description' => 'description', 'image' => 'image'] as $from => $to) {
            if (! empty($a[$from])) {
                $n[$to] = $a[$from];
            }
        }
        if ($isPost) {
            if (! empty($a['published'])) {
                $n['datePublished'] = $a['published'];
            }
            if (! empty($a['modified'])) {
                $n['dateModified'] = $a['modified'];
            }
            if (! empty($a['author_name'])) {
                $n['author'] = array_filter(['@type' => 'Person', 'name' => $a['author_name'], 'url' => $a['author_url'] ?? null]);
            }
        }

        return $n;
    }

    /** All nodes for the current page. */
    public static function graph(array $seo, $content, array $crumbs): array
    {
        $nodes = [];
        $isHome = request()->path() === '/' || request()->routeIs('home');
        if ($isHome) {
            $search = lroute('search').'?q={search_term_string}';
            $nodes[] = self::website((string) setting('site_name', config('app.name')), lroute('home'), $search);
        }
        if ($crumbs) {
            if ($n = self::breadcrumbList($crumbs)) {
                $nodes[] = $n;
            }
        }
        if ($content && ! $isHome) {
            $tr = $content->tr();
            if ($tr) {
                $author = $content->author;
                $nodes[] = self::entry([
                    'type' => $content->type,
                    'title' => $seo['title'] ?? $tr->title,
                    'url' => $seo['canonical'] ?? $tr->url(),
                    'description' => $seo['description'] ?? null,
                    'image' => $seo['image'] ?? null,
                    'published' => ($content->publish_at ?? $content->created_at)?->toAtomString(),
                    'modified' => $content->updated_at?->toAtomString(),
                    'author_name' => $author?->name,
                    'author_url' => $author ? Members::authorUrl($author) : null,
                ]);
            }
        }

        return $nodes;
    }
}
