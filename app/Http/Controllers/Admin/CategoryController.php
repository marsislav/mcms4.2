<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Support\ContentSaver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::with('translations')->withCount('contents')->orderBy('sort')->get(),
            'languages' => mcms_languages(),
        ]);
    }

    public function store(Request $request)
    {
        $category = $this->persist(new Category, $request);
        audit('category.create', (string) $category->id);

        return back()->with('status', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $this->persist($category, $request);
        audit('category.update', (string) $category->id);

        return back()->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        audit('category.delete', (string) $category->id, $category->adminName());
        $category->delete(); // posts keep existing (category_id -> NULL)

        return back()->with('status', 'Category deleted.');
    }

    private function persist(Category $category, Request $request): Category
    {
        $request->validate([
            'sort' => 'nullable|integer|min:0|max:65000',
            'tr' => 'required|array',
            'tr.*.name' => 'nullable|string|max:150',
            'tr.*.slug' => 'nullable|string|max:150',
            'tr.*.description' => 'nullable|string|max:1000',
        ]);

        $default = mcms_default_locale();
        $tr = $request->input('tr', []);
        if (trim((string) ($tr[$default]['name'] ?? '')) === '') {
            throw ValidationException::withMessages(["tr.$default.name" => 'A name in the default language is required.']);
        }

        return DB::transaction(function () use ($category, $request, $tr) {
            $category->sort = (int) $request->input('sort', 0);
            $category->save();

            foreach (mcms_locales() as $loc) {
                $name = trim(strip_tags((string) ($tr[$loc]['name'] ?? '')));
                $existing = CategoryTranslation::where('category_id', $category->id)->where('locale', $loc)->first();
                if ($name === '') {
                    $existing?->delete();
                    continue;
                }
                $slug = ContentSaver::uniqueSlug(trim((string) ($tr[$loc]['slug'] ?? '')) ?: $name, $loc, $existing?->id, 'category');
                $fields = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => trim(strip_tags((string) ($tr[$loc]['description'] ?? ''))) ?: null,
                ];
                $existing
                    ? $existing->update($fields)
                    : CategoryTranslation::create($fields + ['category_id' => $category->id, 'locale' => $loc]);
            }

            return $category;
        });
    }
}
