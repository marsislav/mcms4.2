<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Content;
use App\Models\ContentTranslation;
use App\Models\Message;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class SiteController extends Controller
{
    /* ------------------------------------------------------------ pages */

    public function home()
    {
        $home = $this->scope(Content::query())->where('type', 'page')->where('is_home', true)
            ->with(['translations', 'featured'])->first();

        if ($home) {
            $tr = $home->tr() ?? $home->tr(mcms_default_locale());
            if ($tr) {
                return $this->renderContent($home, $tr, 'site.page');
            }
        }

        // No home page configured: show latest posts
        return $this->blog(request(), true);
    }

    public function page(Request $request, string $slug)
    {
        $tr = $this->findTranslation($slug, 'page');
        if (! $tr) {
            return $this->notFound($request);
        }
        if ($tr->content->is_home) {
            return redirect(lroute('home'), 301);
        }

        return $this->renderContent($tr->content, $tr, 'site.page');
    }

    public function post(Request $request, string $slug)
    {
        $tr = $this->findTranslation($slug, 'post');
        if (! $tr) {
            return $this->notFound($request);
        }

        $related = collect();
        if ($tr->content->category_id) {
            $related = $this->scope(Content::query())->where('type', 'post')
                ->where('category_id', $tr->content->category_id)->where('id', '!=', $tr->content_id)
                ->whereHas('translations', fn ($q) => $q->where('locale', app()->getLocale()))
                ->with(['translations', 'featured'])->orderByDesc('publish_at')->limit(3)->get();
        }

        return $this->renderContent($tr->content, $tr, 'site.post', ['related' => $related]);
    }

    private function renderContent(Content $content, ContentTranslation $tr, string $view, array $extra = [])
    {
        $content->loadMissing('translations', 'featured', 'category.translations');

        $alternates = [];
        foreach ($content->translations as $t) {
            if (in_array($t->locale, mcms_locales(), true)) {
                $t->setRelation('content', $content);
                $alternates[$t->locale] = $t->url();
            }
        }

        $seo = [
            'title' => $tr->meta_title ?: $tr->title,
            'description' => $tr->meta_description ?: ($tr->excerpt ?: excerpt($tr->body, 160)),
            'canonical' => $tr->setRelation('content', $content)->url(),
            'image' => $content->featured?->url(),
            'type' => $content->type === 'post' ? 'article' : 'website',
            'robots' => ($content->noindex || ! $content->isVisible()) ? 'noindex, nofollow' : null,
            'alternates' => $alternates,
            'published' => $content->type === 'post' ? $content->publish_at : null,
        ];

        return response()->view($view, ['content' => $content, 'tr' => $tr, 'seo' => $seo, 'preview' => ! $content->isVisible(), 'switch' => $alternates] + $extra);
    }

    private function findTranslation(string $slug, string $type): ?ContentTranslation
    {
        return ContentTranslation::where('locale', app()->getLocale())->where('slug', $slug)
            ->whereHas('content', fn ($q) => $this->scope($q)->where('type', $type))
            ->with(['content.featured', 'content.translations', 'content.category.translations'])->first();
    }

    /** Logged-in panel users can preview drafts/scheduled items. */
    private function scope($query)
    {
        return (Auth::check() && Auth::user()->active) ? $query : $query->visible();
    }

    /* ------------------------------------------------------------- blog */

    public function blog(Request $request, bool $asHome = false)
    {
        $posts = $this->postQuery()->paginate((int) setting('posts_per_page', 9));

        return view('site.blog', [
            'posts' => $posts,
            'heading' => $asHome ? setting('site_name') : t('blog.title'),
            'seo' => [
                'title' => $asHome ? setting('site_name') : t('blog.title'),
                'description' => setting_l('meta_description'),
                'canonical' => $asHome ? lroute('home') : lroute('blog'),
                'alternates' => $this->builtinAlternates($asHome ? 'home' : 'blog'),
                'robots' => $posts->currentPage() > 1 ? 'noindex, follow' : null,
            ],
            'switch' => $this->builtinAlternates($asHome ? 'home' : 'blog'),
            'category' => null,
        ]);
    }

    public function category(Request $request, string $slug)
    {
        $ct = CategoryTranslation::where('locale', app()->getLocale())->where('slug', $slug)->first();
        if (! $ct) {
            return $this->notFound($request);
        }

        $posts = $this->postQuery()->where('category_id', $ct->category_id)->paginate((int) setting('posts_per_page', 9));

        $alternates = [];
        foreach (CategoryTranslation::where('category_id', $ct->category_id)->get() as $t) {
            if (in_array($t->locale, mcms_locales(), true)) {
                $alternates[$t->locale] = $t->url();
            }
        }

        return view('site.blog', [
            'posts' => $posts,
            'heading' => $ct->name,
            'category' => $ct,
            'seo' => [
                'title' => $ct->name,
                'description' => $ct->description ?: setting_l('meta_description'),
                'canonical' => $ct->url(),
                'alternates' => $alternates,
                'robots' => $posts->currentPage() > 1 ? 'noindex, follow' : null,
            ],
            'switch' => $alternates,
        ]);
    }

    private function postQuery()
    {
        return $this->scope(Content::query())->where('type', 'post')
            ->whereHas('translations', fn ($q) => $q->where('locale', app()->getLocale()))
            ->with(['translations', 'featured', 'category.translations'])
            ->orderByDesc('publish_at')->orderByDesc('id');
    }

    /* ----------------------------------------------------------- search */

    public function search(Request $request)
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 80));
        $results = null;

        if (mb_strlen($q) >= 3) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $results = ContentTranslation::where('locale', app()->getLocale())
                ->whereHas('content', fn ($c) => $c->visible())
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))
                ->with(['content.featured'])->orderByDesc('id')->paginate(10)->withQueryString();
        }

        return view('site.search', [
            'q' => $q,
            'results' => $results,
            'seo' => ['title' => t('nav.search'), 'canonical' => lroute('search'), 'robots' => 'noindex, follow', 'alternates' => []],
            'switch' => $this->builtinAlternates('search'),
        ]);
    }

    /* ---------------------------------------------------------- contact */

    public function contactForm()
    {
        return view('site.contact', [
            'ts' => Crypt::encryptString((string) time()),
            'seo' => ['title' => t('contact.title'), 'description' => t('contact.intro'), 'canonical' => lroute('contact'), 'alternates' => $this->builtinAlternates('contact')],
            'switch' => $this->builtinAlternates('contact'),
        ]);
    }

    public function contactSend(Request $request)
    {
        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withInput()->with('contact_error', t('contact.throttle'));
        }
        RateLimiter::hit($key, 600);

        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if ($request->filled('website')) {
            return back()->with('contact_ok', t('contact.sent'));
        }

        // Time trap: humans need at least 3 seconds (and the token must be genuine and fresh)
        try {
            $age = time() - (int) Crypt::decryptString((string) $request->input('ts'));
        } catch (\Throwable) {
            $age = -1;
        }
        if ($age < 3 || $age > 7200) {
            return back()->withInput()->with('contact_error', t('contact.error'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc|max:190',
            'subject' => 'nullable|string|max:150',
            'message' => 'required|string|min:5|max:5000',
        ]);

        $subject = trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags((string) ($data['subject'] ?? ''))));
        $name = trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($data['name'])));

        $message = Message::create([
            'name' => $name, 'email' => $data['email'], 'subject' => $subject ?: null,
            'body' => strip_tags($data['message']), 'ip' => $request->ip(), 'locale' => app()->getLocale(),
        ]);

        if ($to = setting('contact_email')) {
            try {
                Mail::raw("From: $name <{$data['email']}>\n\n".strip_tags($data['message']), function ($m) use ($to, $data, $name, $subject) {
                    $m->to($to)->replyTo($data['email'], $name)
                        ->subject('['.setting('site_name', 'mCMS').'] '.($subject ?: 'New message'));
                });
            } catch (\Throwable $e) {
                report($e); // the message is safely stored in the panel anyway
            }
        }
        do_action('contact.received', $message);

        return back()->with('contact_ok', t('contact.sent'));
    }

    /* -------------------------------------------------------- seo feeds */

    public function sitemap()
    {
        $xml = Cache::remember('mcms.sitemap', 600, function () {
            $locales = mcms_locales();
            $default = mcms_default_locale();
            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

            $entry = function (array $urls, ?string $lastmod) use (&$out, $default) {
                foreach ($urls as $loc => $url) {
                    $out .= "<url><loc>".e($url)."</loc>";
                    if ($lastmod) {
                        $out .= "<lastmod>$lastmod</lastmod>";
                    }
                    foreach ($urls as $l2 => $u2) {
                        $out .= '<xhtml:link rel="alternate" hreflang="'.e($l2).'" href="'.e($u2).'"/>';
                    }
                    if (isset($urls[$default])) {
                        $out .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.e($urls[$default]).'"/>';
                    }
                    $out .= "</url>\n";
                }
            };

            foreach (['blog'] as $name) {
                $entry(collect($locales)->mapWithKeys(fn ($l) => [$l => lroute($name, [], $l)])->all(), null);
            }

            Content::visible()->where('noindex', false)->with('translations')->orderBy('id')->chunk(200, function ($chunk) use ($entry, $locales) {
                foreach ($chunk as $content) {
                    $urls = [];
                    foreach ($content->translations as $t) {
                        if (in_array($t->locale, $locales, true)) {
                            $t->setRelation('content', $content);
                            $urls[$t->locale] = $t->url();
                        }
                    }
                    if ($urls) {
                        $entry($urls, ($content->updated_at ?? now())->toAtomString());
                    }
                }
            });

            foreach (Category::with('translations')->get() as $cat) {
                $urls = [];
                foreach ($cat->translations as $t) {
                    if (in_array($t->locale, $locales, true)) {
                        $urls[$t->locale] = $t->url();
                    }
                }
                if ($urls) {
                    $entry($urls, null);
                }
            }

            return $out.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $lines = ['User-agent: *', 'Disallow: /search', 'Disallow: /*/search', 'Disallow: /install'];
        if ($extra = setting('robots_extra')) {
            $lines[] = trim($extra);
        }
        $lines[] = 'Sitemap: '.url('sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function feed()
    {
        $posts = Content::visible()->where('type', 'post')
            ->whereHas('translations', fn ($q) => $q->where('locale', app()->getLocale()))
            ->with('translations')->orderByDesc('publish_at')->limit(20)->get();

        $site = setting('site_name', config('app.name'));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
            .'<title>'.e($site).'</title><link>'.e(lroute('home')).'</link>'
            .'<description>'.e((string) setting_l('tagline', $site)).'</description>'
            .'<language>'.e(app()->getLocale()).'</language>'
            .'<atom:link href="'.e(lroute('feed')).'" rel="self" type="application/rss+xml"/>';

        foreach ($posts as $post) {
            $tr = $post->tr();
            $tr->setRelation('content', $post);
            $xml .= '<item><title>'.e($tr->title).'</title><link>'.e($tr->url()).'</link><guid isPermaLink="true">'.e($tr->url()).'</guid>'
                .'<pubDate>'.($post->publish_at ?? $post->created_at)->toRssString().'</pubDate>'
                .'<description>'.e($tr->excerpt ?: excerpt($tr->body, 300)).'</description></item>';
        }

        return response($xml.'</channel></rss>', 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /* --------------------------------------------------------- fallback */

    public function fallback(Request $request)
    {
        $first = $request->segment(1);
        if ($first && in_array($first, mcms_locales(), true) && $first !== mcms_default_locale()) {
            app()->setLocale($first);
        }

        return $this->notFound($request);
    }

    private function notFound(Request $request)
    {
        $path = '/'.trim($request->path(), '/');
        if ($redirect = Redirect::where('from_path', $path)->first()) {
            $redirect->increment('hits');

            return redirect()->away($redirect->to_url, $redirect->code);
        }

        abort(404);
    }

    /** @return array<string,string> */
    private function builtinAlternates(string $name): array
    {
        return collect(mcms_locales())->mapWithKeys(fn ($l) => [$l => lroute($name, [], $l)])->all();
    }
}
