<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Revision;
use App\Support\ContentSaver;
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

        $q = Content::with(['translations', 'author', 'category.translations'])->where('type', $type)
            ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))
            ->when($request->filled('status') && in_array($request->status, ['draft', 'published'], true), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->string('q')->limit(100), '%_\\').'%';
                $q->whereHas('translations', fn ($t) => $t->where('title', 'like', $term));
            });

        $items = $type === 'page' ? $q->orderBy('sort')->orderByDesc('id') : $q->orderByDesc('publish_at')->orderByDesc('id');

        return view('admin.content.index', ['type' => $type, 'items' => $items->paginate(20)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $type = $this->type($request);

        return view('admin.content.form', [
            'type' => $type,
            'content' => new Content(['status' => 'draft']),
            'categories' => Category::with('translations')->orderBy('sort')->get(),
            'languages' => mcms_languages(),
        ]);
    }

    public function store(Request $request)
    {
        $type = $this->type($request);
        $content = ContentSaver::save(null, $type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        audit($type.'.create', (string) $content->id, $content->adminTitle());

        return redirect()->route("admin.{$type}s.edit", $content)->with('status', 'Saved.');
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
        ]);
    }

    public function update(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        ContentSaver::save($content, $content->type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        audit($content->type.'.update', (string) $content->id, $content->adminTitle());

        return back()->with('status', 'Changes saved.');
    }

    public function destroy(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        abort_if($content->is_home, 422, 'The home page cannot be deleted. Set another one first.');
        $title = $content->adminTitle();
        $type = $content->type;
        $content->delete();
        audit($type.'.delete', (string) $content->id, $title);

        return redirect()->route("admin.{$type}s.index")->with('status', 'Deleted.');
    }

    public function restore(Request $request, Revision $revision)
    {
        $translation = \App\Models\ContentTranslation::with('content')->findOrFail($revision->content_translation_id);
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

        return back()->with('status', 'Revision restored.');
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
            'tr' => 'required|array',
            'tr.*.title' => 'nullable|string|max:190',
            'tr.*.slug' => 'nullable|string|max:150',
            'tr.*.excerpt' => 'nullable|string|max:1000',
            'tr.*.body' => 'nullable|string|max:500000',
            'tr.*.meta_title' => 'nullable|string|max:190',
            'tr.*.meta_description' => 'nullable|string|max:300',
        ]);
        unset($data['tr']);

        return $data;
    }
}
