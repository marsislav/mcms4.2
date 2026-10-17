<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Revision;
use App\Support\ContentSaver;
use App\Support\Taxonomy;
use App\Support\Trash;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    private function type(Request $r): string
    {
        $t = $r->route('type');
        abort_unless(in_array($t, ['page', 'post'], true), 404);

        return $t;
    }

    private function authorize(Request $r, Content $content): void
    {
        abort_unless($r->user()->allows('content.all') || $content->author_id === $r->user()->id, 403);
    }

    public function index(Request $request)
    {
        $type = $this->type($request);
        $user = $request->user();

        $trash = Trash::ready() && $request->boolean('trash');
        $q = Content::with(['translations', 'author', 'category.translations'])->where('type', $type)
            ->when($trash, fn ($q) => $q->onlyTrashed())
            ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))
            ->when($request->filled('status') && in_array($request->status, ['draft', 'published'], true), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->string('q')->limit(100), '%_\\').'%';
                $q->whereHas('translations', fn ($t) => $t->where('title', 'like', $term));
            });

        $items = $trash
            ? $q->orderByDesc('deleted_at')
            : ($type === 'page' ? $q->orderBy('sort')->orderByDesc('id') : $q->orderByDesc('publish_at')->orderByDesc('id'));

        $trashCount = Trash::ready()
            ? Content::onlyTrashed()->where('type', $type)
                ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))->count()
            : null;

        return view('admin.content.index', [
            'type' => $type, 'trash' => $trash, 'trashCount' => $trashCount,
            'items' => $items->paginate(20)->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        $type = $this->type($request);

        return view('admin.content.form', [
            'type' => $type,
            'content' => new Content(['status' => 'draft']),
            'categories' => Category::with('translations')->orderBy('sort')->get(),
            'languages' => mcms_languages(),
        ] + $this->taxonomyData(null));
    }

    public function store(Request $request)
    {
        $type = $this->type($request);
        $content = ContentSaver::save(null, $type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        $this->saveTaxonomy($request, $content);
        audit($type.'.create', (string) $content->id, $content->adminTitle());

        return redirect()->route("admin.{$type}s.edit", $content)->with('status', 'Записано.');
    }

    public function edit(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        $content->load('translations.revisions', 'featured');

        return view('admin.content.form', [
            'type' => $content->type,
            'content' => $content,
            'categories' => Category::with('translations')->orderBy('sort')->get(),
            'languages' => mcms_languages(),
        ] + $this->taxonomyData($content));
    }

    public function update(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        ContentSaver::save($content, $content->type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        $this->saveTaxonomy($request, $content);
        audit($content->type.'.update', (string) $content->id, $content->adminTitle());

        return back()->with('status', 'Промените са записани.');
    }

    public function destroy(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        abort_if($content->is_home, 422, 'Началната страница не може да се изтрие. Първо задайте друга.');
        $title = $content->adminTitle();
        $type = $content->type;
        $content->delete();
        audit($type.'.delete', (string) $content->id, $title);

        return redirect()->route("admin.{$type}s.index")
            ->with('status', Trash::ready() ? 'Преместено в кошчето.' : 'Изтрито.');
    }

    private function trashed(Request $request, int $id): Content
    {
        abort_unless(Trash::ready(), 404);
        $content = Content::onlyTrashed()->where('type', $this->type($request))->findOrFail($id);
        $this->authorize($request, $content);

        return $content;
    }

    public function untrash(Request $request, int $id)
    {
        $content = $this->trashed($request, $id);
        $content->restore();
        \Illuminate\Support\Facades\Cache::forget('mcms.sitemap');
        audit($content->type.'.restore', (string) $content->id, $content->adminTitle());

        return back()->with('status', 'Възстановено от кошчето.');
    }

    public function purge(Request $request, int $id)
    {
        $content = $this->trashed($request, $id);
        audit($content->type.'.purge', (string) $content->id, $content->adminTitle());
        $content->forceDelete(); // translations, revisions, tag/related links cascade in the database

        return back()->with('status', 'Изтрито завинаги.');
    }

    public function emptyTrash(Request $request)
    {
        abort_unless(Trash::ready(), 404);
        $type = $this->type($request);
        $user = $request->user();
        $items = Content::onlyTrashed()->where('type', $type)->with('translations')
            ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))->get();
        foreach ($items as $c) {
            $c->forceDelete();
        }
        audit($type.'.trash.empty', null, (string) $items->count());

        return redirect()->route("admin.{$type}s.index")->with('status', 'Кошчето е изпразнено ('.$items->count().').');
    }

    public function restore(Request $request, Revision $revision)
    {
        $translation = \App\Models\ContentTranslation::with('content')->findOrFail($revision->content_translation_id);
        abort_unless($translation->content, 404);
        $this->authorize($request, $translation->content);

        // Current state becomes a revision too, so restoring is reversible
        Revision::create([
            'content_translation_id' => $translation->id,
            'user_id' => $request->user()->id,
            'title' => $translation->title,
            'body' => $translation->body,
        ]);
        $translation->update(['title' => $revision->title, 'body' => $revision->body]);
        audit('revision.restore', (string) $translation->content_id);

        return back()->with('status', 'Версията е възстановена.');
    }

    /** Tags + related posts apply to posts only and need the "tags" migration. */
    private function saveTaxonomy(Request $request, Content $content): void
    {
        if ($content->type !== 'post' || ! Taxonomy::ready()) {
            return;
        }
        $request->validate([
            'tags' => 'nullable|string|max:1500',
            'related' => 'nullable|array|max:'.Taxonomy::MAX_RELATED,
            'related.*' => 'integer',
        ]);
        Taxonomy::syncTags($content, (string) $request->input('tags', ''));
        Taxonomy::syncRelated($content, (array) $request->input('related', []));
    }

    /** Extra data for the editor sidebar. */
    private function taxonomyData(?Content $content): array
    {
        if (! Taxonomy::ready()) {
            return ['taxReady' => false, 'tagsValue' => '', 'allTags' => collect(), 'relatedSelected' => collect(), 'postChoices' => collect()];
        }
        $def = mcms_default_locale();
        $tagsValue = $content?->exists
            ? $content->tags()->with('translations')->get()->map(fn ($t) => $t->adminName())->implode(', ')
            : '';

        return [
            'taxReady' => true,
            'tagsValue' => $tagsValue,
            'allTags' => \App\Models\Tag::with('translations')->withCount('contents')->orderByDesc('contents_count')->limit(40)->get(),
            'relatedSelected' => $content?->exists ? $content->related()->with('translations')->get() : collect(),
            'postChoices' => Content::where('type', 'post')->with('translations')
                ->when($content?->exists, fn ($q) => $q->where('id', '!=', $content->id))
                ->orderByDesc('id')->limit(500)->get(),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'status' => 'required|in:draft,published',
            'publish_at' => 'nullable|date',
            'category_id' => 'nullable|integer|exists:categories,id',
            'featured_media_id' => 'nullable|integer|exists:media,id',
            'sort' => 'nullable|integer|min:0|max:100000',
            'is_home' => 'nullable|boolean',
            'noindex' => 'nullable|boolean',
            'comments_open' => 'nullable|in:0,1',
            'tr' => 'required|array',
            'tr.*.title' => 'nullable|string|max:190',
            'tr.*.slug' => 'nullable|string|max:150',
            'tr.*.excerpt' => 'nullable|string|max:1000',
            'tr.*.body' => 'nullable|string|max:500000',
            'tr.*.meta_title' => 'nullable|string|max:190',
            'tr.*.meta_description' => 'nullable|string|max:300',
        ]);
        unset($data['tr']);
        // per-content comment override: '' = site default (NULL). Column exists only after the database update.
        if (\App\Support\Discussion::ready()) {
            $data['comments_open'] = ($data['comments_open'] ?? '') === '' ? null : (bool) (int) $data['comments_open'];
        } else {
            unset($data['comments_open']);
        }

        return $data;
    }
}
