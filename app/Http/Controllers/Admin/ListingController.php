<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Media;
use App\Support\Listings;
use App\Support\Uploader;
use Illuminate\Http\Request;

/** Moderation of classified ads (permission "listings": administrator and editor). */
class ListingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Listings::ready(), 404);
        $status = (string) $request->query('status', '');
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 80));

        $items = Listing::with('user', 'category')
            ->when($status === 'pending', fn ($w) => $w->where('status', 'pending'))
            ->when($status === 'rejected', fn ($w) => $w->where('status', 'rejected'))
            ->when($status === 'published', fn ($w) => $w->visible())
            ->when($status === 'expired', fn ($w) => $w->where('status', 'published')->where('expires_at', '<=', now()))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('contact_name', 'like', $like)->orWhere('contact_email', 'like', $like));
            })
            ->orderByRaw("status = 'pending' desc")->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.listings.index', [
            'items' => $items,
            'status' => $status,
            'q' => $q,
            'counts' => [
                'pending' => Listing::where('status', 'pending')->count(),
                'published' => Listing::visible()->count(),
                'expired' => Listing::where('status', 'published')->where('expires_at', '<=', now())->count(),
                'rejected' => Listing::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function edit(int $id)
    {
        abort_unless(Listings::ready(), 404);
        $l = Listing::with('user')->findOrFail($id);

        return view('admin.listings.edit', ['l' => $l, 'images' => $l->images(), 'cats' => Listings::categories()]);
    }

    public function update(Request $request, int $id)
    {
        abort_unless(Listings::ready(), 404);
        $l = Listing::findOrFail($id);

        $data = $request->validate([
            'title' => 'required|string|min:3|max:160',
            'body' => 'required|string|min:5|max:5000',
            'price' => 'nullable|numeric|min:0|max:99999999',
            'currency' => 'required|in:EUR,BGN',
            'city' => 'nullable|string|max:80',
            'contact_name' => 'required|string|max:80',
            'contact_phone' => 'nullable|string|max:40',
            'contact_email' => 'nullable|email:rfc|max:120',
            'days' => 'required|integer|min:1|max:365',
            'expires_at' => 'nullable|date',
            'status' => 'required|in:pending,published,rejected',
            'category_id' => ['nullable', 'integer', Listings::categoriesReady() ? 'exists:listing_categories,id' : 'in:0'],
        ]);

        $l->fill(collect($data)->only(['title', 'body', 'price', 'currency', 'city', 'contact_name', 'contact_phone', 'contact_email', 'days', 'category_id'])->map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v)->all());
        $l->price = ($data['price'] ?? '') === '' ? null : $data['price'];
        $l->category_id = ! empty($data['category_id']) ? (int) $data['category_id'] : null;
        $l->slug = $l->slugFor();

        $remove = array_intersect(array_map('intval', (array) $request->input('remove', [])), array_map('intval', (array) $l->media_ids));
        foreach ($remove as $mid) {
            if ($m = Media::find($mid)) {
                Uploader::delete($m);
            }
        }
        $l->media_ids = array_values(array_diff(array_map('intval', (array) $l->media_ids), $remove));

        $was = $l->status;
        $l->status = $data['status'];
        if ($l->status === 'published') {
            $l->published_at = $l->published_at ?: now();
            $l->expires_at = ! empty($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at'])->endOfDay() : ($l->expires_at ?: now()->addDays((int) $l->days));
        }
        $l->save();
        audit('listing.update', $l->title, ['id' => $l->id, 'status' => $l->status]);
        $this->notifyOwner($l, $was);

        return redirect()->route('admin.listings.index')->with('status', __('Обявата е запазена.'));
    }

    public function approve(int $id)
    {
        abort_unless(Listings::ready(), 404);
        $l = Listing::findOrFail($id);
        $was = $l->status;
        Listings::publish($l);
        $l->save();
        audit('listing.approve', $l->title, ['id' => $l->id]);
        $this->notifyOwner($l, $was);

        return back()->with('status', __('Обявата е одобрена и публикувана.'));
    }

    public function reject(int $id)
    {
        abort_unless(Listings::ready(), 404);
        $l = Listing::findOrFail($id);
        $was = $l->status;
        $l->status = 'rejected';
        $l->save();
        audit('listing.reject', $l->title, ['id' => $l->id]);
        $this->notifyOwner($l, $was);

        return back()->with('status', __('Обявата е отхвърлена.'));
    }

    public function destroy(int $id)
    {
        abort_unless(Listings::ready(), 404);
        $l = Listing::findOrFail($id);
        foreach ($l->images() as $m) {
            Uploader::delete($m);
        }
        audit('listing.delete', $l->title, ['id' => $l->id]);
        $l->delete();

        return redirect()->route('admin.listings.index')->with('status', __('Обявата е изтрита.'));
    }

    /** Tell the author when the decision changed. */
    private function notifyOwner(Listing $l, string $was): void
    {
        if ($l->status === $was || ! in_array($l->status, ['published', 'rejected'], true)) {
            return;
        }
        $to = $l->user?->email ?: $l->contact_email;
        $msg = $l->status === 'published'
            ? __('Вашата обява „:t“ е публикувана: :u', ['t' => $l->title, 'u' => $l->url()])
            : __('Вашата обява „:t“ не беше одобрена.', ['t' => $l->title]);
        Listings::mail($to, $l->status === 'published' ? __('Обявата е публикувана') : __('Обявата не е одобрена'), $msg);
    }
}
