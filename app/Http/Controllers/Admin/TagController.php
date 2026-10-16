<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Support\ContentSaver;
use App\Support\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TagController extends Controller
{
    public function index()
    {
        if (! Taxonomy::ready()) {
            return view('admin.tags.index', ['ready' => false, 'tags' => collect(), 'languages' => mcms_languages()]);
        }

        return view('admin.tags.index', [
            'ready' => true,
            'tags' => Tag::with('translations')->withCount('contents')->get()->sortBy(fn ($t) => mb_strtolower($t->adminName()))->values(),
            'languages' => mcms_languages(),
        ]);
    }

    public function store(Request $request)
    {
        $tag = $this->persist(new Tag, $request);
        audit('tag.create', (string) $tag->id);

        return back()->with('status', 'Тагът е добавен.');
    }

    public function update(Request $request, Tag $tag)
    {
        $this->persist($tag, $request);
        audit('tag.update', (string) $tag->id);

        return back()->with('status', 'Тагът е обновен.');
    }

    public function destroy(Tag $tag)
    {
        audit('tag.delete', (string) $tag->id, $tag->load('translations')->adminName());
        $tag->delete(); // pivot rows cascade; posts stay

        return back()->with('status', 'Тагът е изтрит.');
    }

    /** Move all posts from this tag to another one, then delete this tag. */
    public function merge(Request $request, Tag $tag)
    {
        $data = $request->validate(['into' => 'required|integer|exists:tags,id']);
        $target = Tag::findOrFail($data['into']);
        if ($target->id === $tag->id) {
            throw ValidationException::withMessages(['into' => 'Изберете друг таг.']);
        }

        DB::transaction(function () use ($tag, $target) {
            $ids = DB::table('content_tag')->where('tag_id', $tag->id)->pluck('content_id');
            foreach ($ids as $cid) {
                DB::table('content_tag')->insertOrIgnore(['content_id' => $cid, 'tag_id' => $target->id]);
            }
            $tag->delete();
        });
        audit('tag.merge', (string) $tag->id, '→ #'.$target->id);

        return back()->with('status', 'Таговете са обединени.');
    }

    private function persist(Tag $tag, Request $request): Tag
    {
        $request->validate([
            'tr' => 'required|array',
            'tr.*.name' => 'nullable|string|max:80',
            'tr.*.slug' => 'nullable|string|max:120|regex:/^[a-z0-9\-]*$/',
        ]);

        $default = mcms_default_locale();
        $tr = $request->input('tr', []);
        if (trim((string) ($tr[$default]['name'] ?? '')) === '') {
            throw ValidationException::withMessages(["tr.$default.name" => 'Името на основния език е задължително.']);
        }

        return DB::transaction(function () use ($tag, $tr) {
            $tag->save();

            foreach (mcms_locales() as $loc) {
                $name = trim(strip_tags((string) ($tr[$loc]['name'] ?? '')));
                $existing = TagTranslation::where('tag_id', $tag->id)->where('locale', $loc)->first();
                if ($name === '') {
                    $existing?->delete();
                    continue;
                }
                $slug = ContentSaver::uniqueSlug(trim((string) ($tr[$loc]['slug'] ?? '')) ?: $name, $loc, $existing?->id, 'tag');
                $fields = ['name' => mb_substr($name, 0, 80), 'slug' => $slug];
                $existing
                    ? $existing->update($fields)
                    : TagTranslation::create($fields + ['tag_id' => $tag->id, 'locale' => $loc]);
            }

            return $tag;
        });
    }
}
