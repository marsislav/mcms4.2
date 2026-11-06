<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Media;
use App\Support\Listings;
use App\Support\Members;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/** Public classified ads: list, view, submit / edit / renew / delete own ads. */
class ListingController extends Controller
{
    /* ------------------------------------------------------------ public */

    public function index(Request $request)
    {
        Listings::abortUnlessReady();
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 80));

        $cats = Listings::categories();
        $cat = null;
        if (($slug = (string) $request->query('cat', '')) !== '') {
            $cat = $cats->firstWhere('slug', $slug);
            abort_unless($cat, 404);
        }

        $items = Listing::visible()->with('category')
            ->when($cat, fn ($w) => $w->where('category_id', $cat->id))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('body', 'like', $like)->orWhere('city', 'like', $like));
            })
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(Listings::perPage())->withQueryString();

        \App\Support\Stats::hit('p:listings', t('listings.title', 'Обяви'));

        return view('site.listings.index', [
            'items' => $items,
            'thumbs' => Listings::thumbs($items->getCollection()),
            'q' => $q,
            'cats' => $cats,
            'cat' => $cat,
            'seo' => [
                'title' => $cat ? $cat->name.' — '.t('listings.title', 'Обяви') : t('listings.title', 'Обяви'),
                'description' => t('listings.intro', 'Малки обяви от нашите читатели.'),
                'canonical' => $cat ? lroute('listings').'?cat='.$cat->slug : lroute('listings'),
                'alternates' => Listings::alternates('listings'),
                'robots' => ($q !== '' || $items->currentPage() > 1) ? 'noindex, follow' : null,
            ],
            'switch' => Listings::alternates('listings'),
            'crumbs' => Listings::crumbs($cat ? [[t('listings.title', 'Обяви'), lroute('listings')], [$cat->name, null]] : [[t('listings.title', 'Обяви'), null]]),
        ]);
    }

    public function show(Request $request, int $id, ?string $slug = null)
    {
        Listings::abortUnlessReady();
        $l = Listing::with('category')->find($id);
        $me = $this->user();
        $mine = $me && ($l?->user_id === $me->id || $me->allows('listings'));
        abort_unless($l && ($l->isLive() || $mine), 404);

        if (($l->slug ?: $l->slugFor()) !== (string) $slug) {
            return redirect($l->url(), 301);
        }
        if ($l->isLive() && ! $mine) {
            $l->increment('views');
        }

        $images = $l->images();

        return view('site.listings.show', [
            'l' => $l,
            'images' => $images,
            'preview' => ! $l->isLive(),
            'mine' => $mine,
            'seo' => [
                'title' => $l->title,
                'description' => excerpt($l->body, 160),
                'canonical' => $l->url(),
                'image' => $images->first()?->url(),
                'type' => 'article',
                'robots' => $l->isLive() ? null : 'noindex, nofollow',
                'alternates' => [],
            ],
            'switch' => [],
            'crumbs' => Listings::crumbs([[t('listings.title', 'Обяви'), lroute('listings')], [$l->title, null]]),
        ]);
    }

    /* ------------------------------------------------------- own listings */

    public function mine()
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }

        $items = Listing::where('user_id', $me->id)->orderByDesc('id')->paginate(20);

        return view('site.listings.mine', [
            'items' => $items,
            'thumbs' => Listings::thumbs($items->getCollection()),
            'seo' => ['title' => t('listings.mine', 'Моите обяви'), 'canonical' => lroute('listings.mine'), 'robots' => 'noindex, nofollow', 'alternates' => []],
            'switch' => [],
            'crumbs' => Listings::crumbs([[t('listings.title', 'Обяви'), lroute('listings')], [t('listings.mine', 'Моите обяви'), null]]),
        ]);
    }

    public function create()
    {
        Listings::abortUnlessReady();
        if (! $this->user()) {
            return $this->guest();
        }

        return $this->form(new Listing(['days' => 30, 'currency' => 'EUR']));
    }

    public function store(Request $request)
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }
        if ($request->filled('website')) { // honeypot
            return redirect(lroute('listings.mine'));
        }
        if (Listing::where('user_id', $me->id)->count() >= Listings::perUser() && ! $me->allows('listings')) {
            return back()->withInput()->with('ls_error', t('listings.limit', 'Достигнахте максималния брой обяви.'));
        }

        $data = $this->validated($request, 0);
        $l = new Listing($data);
        $l->user_id = $me->id;
        $l->slug = $l->slugFor();
        $l->media_ids = [];
        if ($err = $this->savePhotos($request, $l, $me->id)) {
            return back()->withInput()->with('ls_error', $err);
        }

        $auto = $me->allows('listings') || ! Listings::approval();
        if ($auto) {
            Listings::publish($l);
        } else {
            $l->status = 'pending';
        }
        $l->save();

        audit('listing.create', $l->title, ['id' => $l->id, 'status' => $l->status]);
        if (! $auto) {
            Listings::notifyAdmin($l);
        }

        return redirect(lroute('listings.mine'))->with('ls_ok', $auto
            ? t('listings.created_live', 'Обявата е публикувана.')
            : t('listings.created_pending', 'Обявата е изпратена и чака одобрение.'));
    }

    public function edit(int $id)
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }

        return $this->form($this->owned($id, $me));
    }

    public function update(Request $request, int $id)
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }
        $l = $this->owned($id, $me);

        $data = $this->validated($request, count($l->images()) - count($this->removeIds($request, $l)));
        $l->fill($data);
        $l->slug = $l->slugFor();

        foreach ($this->removeIds($request, $l) as $mid) {
            if ($m = Media::find($mid)) {
                Uploader::delete($m);
            }
        }
        $l->media_ids = array_values(array_diff((array) $l->media_ids, $this->removeIds($request, $l)));
        if ($err = $this->savePhotos($request, $l, $me->id)) {
            return back()->withInput()->with('ls_error', $err);
        }

        // an edited ad goes through moderation again (prevents bait-and-switch after approval)
        $resub = Listings::approval() && ! $me->allows('listings') && $l->status !== 'pending';
        if ($resub) {
            $l->status = 'pending';
        }
        $l->save();
        audit('listing.update', $l->title, ['id' => $l->id]);
        if ($resub) {
            Listings::notifyAdmin($l);
        }

        return redirect(lroute('listings.mine'))->with('ls_ok', $resub
            ? t('listings.updated_pending', 'Промените са запазени и обявата чака повторно одобрение.')
            : t('listings.updated', 'Обявата е обновена.'));
    }

    /** Extend an already approved ad for another period. */
    public function renew(int $id)
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }
        $l = $this->owned($id, $me);
        if ($l->status === 'published') {
            $l->expires_at = now()->addDays(max(1, (int) $l->days));
            $l->save();
            audit('listing.renew', $l->title, ['id' => $l->id]);
        }

        return redirect(lroute('listings.mine'))->with('ls_ok', t('listings.renewed', 'Валидността е удължена.'));
    }

    public function destroy(int $id)
    {
        Listings::abortUnlessReady();
        if (! $me = $this->user()) {
            return $this->guest();
        }
        $l = $this->owned($id, $me);
        foreach ($l->images() as $m) {
            Uploader::delete($m);
        }
        audit('listing.delete', $l->title, ['id' => $l->id]);
        $l->delete();

        return redirect(lroute('listings.mine'))->with('ls_ok', t('listings.deleted', 'Обявата е изтрита.'));
    }

    /* ----------------------------------------------------------- helpers */

    private function user()
    {
        $u = Auth::user();

        return ($u && $u->active) ? $u : null;
    }

    private function owned(int $id, $me): Listing
    {
        $l = Listing::find($id);
        abort_unless($l && ($l->user_id === $me->id || $me->allows('listings')), 404);

        return $l;
    }

    private function guest()
    {
        return response()->view('site.listings.guest', [
            'canRegister' => Members::registrationOpen(),
            'seo' => ['title' => t('listings.add', 'Подай обява'), 'canonical' => lroute('listings.create'), 'robots' => 'noindex, nofollow', 'alternates' => []],
            'switch' => [],
            'crumbs' => Listings::crumbs([[t('listings.title', 'Обяви'), lroute('listings')], [t('listings.add', 'Подай обява'), null]]),
        ]);
    }

    private function form(Listing $l)
    {
        $edit = $l->exists;

        return view('site.listings.form', [
            'l' => $l,
            'edit' => $edit,
            'images' => $edit ? $l->images() : collect(),
            'cats' => Listings::categories(),
            'ts' => time(),
            'seo' => ['title' => $edit ? t('listings.edit', 'Редакция на обява') : t('listings.add', 'Подай обява'), 'canonical' => lroute('listings.create'), 'robots' => 'noindex, nofollow', 'alternates' => []],
            'switch' => [],
            'crumbs' => Listings::crumbs([[t('listings.title', 'Обяви'), lroute('listings')], [$edit ? t('listings.edit', 'Редакция на обява') : t('listings.add', 'Подай обява'), null]]),
        ]);
    }

    private function validated(Request $request, int $existingImages): array
    {
        $key = 'listing-form:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429);
        }
        RateLimiter::hit($key, 3600);

        $maxKb = (int) config('mcms.max_upload_mb') * 1024;
        $data = $request->validate([
            'title' => 'required|string|min:5|max:120',
            'body' => 'required|string|min:20|max:5000',
            'price' => 'nullable|numeric|min:0|max:99999999',
            'currency' => 'required|in:EUR,BGN',
            'city' => 'nullable|string|max:80',
            'contact_name' => 'required|string|min:2|max:80',
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,30}$/'],
            'contact_email' => 'nullable|email:rfc|max:120',
            'days' => 'required|integer|in:'.implode(',', Listings::dayOptions()),
            'category_id' => ['nullable', 'integer', Listings::categoriesReady() ? 'exists:listing_categories,id' : 'in:0'],
            'photos' => 'nullable|array|max:'.max(1, Listings::maxImages()),
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:'.$maxKb,
        ]);

        if (trim((string) ($data['contact_phone'] ?? '')) === '' && trim((string) ($data['contact_email'] ?? '')) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['contact_phone' => t('listings.need_contact', 'Въведете телефон или имейл за връзка.')]);
        }
        if ($existingImages + count((array) $request->file('photos', [])) > Listings::maxImages()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['photos' => t('listings.too_many', 'Максимум :n снимки.', ['n' => Listings::maxImages()])]);
        }

        foreach (['title', 'body', 'city', 'contact_name', 'contact_phone', 'contact_email'] as $f) {
            $data[$f] = isset($data[$f]) ? trim(strip_tags((string) $data[$f])) : null;
            if ($data[$f] === '') {
                $data[$f] = null;
            }
        }
        $data['price'] = ($data['price'] ?? '') === '' ? null : $data['price'];
        if (Listings::categoriesReady() && Listings::categories()->isNotEmpty() && empty($data['category_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['category_id' => t('listings.need_category', 'Изберете категория.')]);
        }
        $data['category_id'] = ! empty($data['category_id']) ? (int) $data['category_id'] : null;
        unset($data['photos']);

        return $data;
    }

    /** @return int[] ids of this listing's photos ticked for removal */
    private function removeIds(Request $request, Listing $l): array
    {
        $own = array_map('intval', (array) $l->media_ids);

        return array_values(array_intersect(array_map('intval', (array) $request->input('remove', [])), $own));
    }

    private function savePhotos(Request $request, Listing $l, int $userId): ?string
    {
        $ids = array_map('intval', (array) $l->media_ids);
        foreach ((array) $request->file('photos', []) as $file) {
            if (! $file || count($ids) >= Listings::maxImages()) {
                break;
            }
            try {
                $m = Uploader::store($file, $userId);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }
            if (! $m->isImage()) {
                Uploader::delete($m);

                continue;
            }
            $ids[] = $m->id;
        }
        $l->media_ids = $ids;

        return null;
    }
}
