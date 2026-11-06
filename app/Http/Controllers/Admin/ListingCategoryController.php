<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\ListingCategory;
use App\Support\Listings;
use Illuminate\Http\Request;

/** Categories of classified ads (permission "listings"). */
class ListingCategoryController extends Controller
{
    public function index()
    {
        abort_unless(Listings::categoriesReady(), 404);
        $cats = ListingCategory::orderBy('sort')->orderBy('name')->get();
        $counts = Listing::selectRaw('category_id, count(*) c')->whereNotNull('category_id')->groupBy('category_id')->pluck('c', 'category_id');

        return view('admin.listings.categories', ['cats' => $cats, 'counts' => $counts]);
    }

    public function store(Request $request)
    {
        abort_unless(Listings::categoriesReady(), 404);
        $d = $request->validate(['name' => 'required|string|min:2|max:80', 'sort' => 'nullable|integer|min:0|max:9999']);
        $name = trim(strip_tags($d['name']));
        $c = new ListingCategory(['name' => $name, 'sort' => (int) ($d['sort'] ?? 0)]);
        $c->slug = ListingCategory::slugFor($name);
        $c->save();
        audit('listing_category.create', $name);

        return back()->with('status', __('Категорията е добавена.'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(Listings::categoriesReady(), 404);
        $c = ListingCategory::findOrFail($id);
        $d = $request->validate(['name' => 'required|string|min:2|max:80', 'sort' => 'nullable|integer|min:0|max:9999']);
        $c->name = trim(strip_tags($d['name']));
        $c->sort = (int) ($d['sort'] ?? 0);
        $c->slug = ListingCategory::slugFor($c->name, $c->id);
        $c->save();
        audit('listing_category.update', $c->name);

        return back()->with('status', __('Категорията е запазена.'));
    }

    public function destroy(int $id)
    {
        abort_unless(Listings::categoriesReady(), 404);
        $c = ListingCategory::findOrFail($id);
        Listing::where('category_id', $c->id)->update(['category_id' => null]);
        audit('listing_category.delete', $c->name);
        $c->delete();

        return back()->with('status', __('Категорията е изтрита. Обявите в нея са без категория.'));
    }
}
