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
        $u = $r->user();
        abort_unless($u->allows('content.all') || $content->author_id === $u->id, 403);
        // like WordPress: a contributor can no longer edit a post once it is published
        abort_if(! $u->allows('publish') && $content->status === 'published', 403, __('Публикувана статия се редактира от автор или редактор.'));
    }

    public function index(Request $request)
    {
        $type = $this->type($request);
        $user = $request->user();

        $trash = Trash::ready() && $request->boolean('trash');
        $q = Content::with(['translations', 'author', 'category.translations'])->where('type', $type)
            ->when($trash, fn ($q) => $q->onlyTrashed())
            ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))
            ->when($request->filled('status') && in_array($request->status, ['draft', 'pending', 'published'], true), fn ($q) => $q->where('status', $request->status))
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
        $content = new Content(['status' => \App\Support\Options::get('default_status'), 'category_id' => $type === 'post' ? (\App\Support\Options::get('default_category') ?: null) : null]);

        // start from a template (Blocks & templates): pre-fill the default-language body
        $templates = \App\Support\Blocks::templatesFor($type);
        $picked = collect($templates)->firstWhere('id', (int) $request->query('template'));
        $content->setRelation('translations', collect($picked ? [new \App\Models\ContentTranslation(['locale' => mcms_default_locale(), 'body' => (string) $picked->body])] : []));

        return view('admin.content.form', [
            'type' => $type,
            'templates' => $templates,
            'pickedTemplate' => $picked?->id,
            'content' => $content,
            'categories' => Category::with('translations')->orderBy('sort')->get(),
            'languages' => mcms_languages(),
        ] + $this->taxonomyData(null));
    }

    public function store(Request $request)
    {
        $type = $this->type($request);
        abort_if($type === 'page' && ! $request->user()->allows('pages'), 403);
        $content = ContentSaver::save(null, $type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        if ($content->status === 'pending') {
            $this->notifyPending($content, $request->user());
        }
        $this->saveTaxonomy($request, $content);
        audit($type.'.create', (string) $content->id, $content->adminTitle());

        return redirect()->route("admin.{$type}s.edit", $content)->with('status', __('Записано.'));
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
        $was = $content->status;
        ContentSaver::save($content, $content->type, $this->validated($request), $request->input('tr', []), $request->user()->id);
        if ($content->fresh()->status === 'pending' && $was !== 'pending') {
            $this->notifyPending($content, $request->user());
        }
        $this->saveTaxonomy($request, $content);
        audit($content->type.'.update', (string) $content->id, $content->adminTitle());

        return back()->with('status', __('Промените са записани.'));
    }

    public function destroy(Request $request, Content $content)
    {
        $this->authorize($request, $content);
        abort_if($content->is_home, 422, __('Началната страница не може да се изтрие. Първо задайте друга.'));
        $title = $content->adminTitle();
        $type = $content->type;
        $content->delete();
        audit($type.'.delete', (string) $content->id, $title);

        return redirect()->route("admin.{$type}s.index")
            ->with('status', Trash::ready() ? __('Преместено в кошчето.') : __('Изтрито.'));
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

        return back()->with('status', __('Възстановено от кошчето.'));
    }

    public function purge(Request $request, int $id)
    {
        $content = $this->trashed($request, $id);
        audit($content->type.'.purge', (string) $content->id, $content->adminTitle());
        $content->forceDelete(); // translations, revisions, tag/related links cascade in the database

        return back()->with('status', __('Изтрито завинаги.'));
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

        return redirect()->route("admin.{$type}s.index")->with('status', __('Кошчето е изпразнено (:n).', ['n' => $items->count()]));
    }

    /** Compare two versions of one translation: ?a=<revision id|current>&b=<revision id|current>. */
    public function compare(Request $request, \App\Models\ContentTranslation $translation)
    {
        $translation->load('content');
        abort_unless($translation->content, 404);
        $this->authorize($request, $translation->content);

        $revs = Revision::where('content_translation_id', $translation->id)->orderByDesc('id')->with('user')->get();
        $pick = function (?string $id) use ($revs, $translation) {
            if ($id !== null && ctype_digit($id) && ($r = $revs->firstWhere('id', (int) $id))) {
                return ['id' => (string) $r->id, 'label' => $r->created_at?->format('d.m.Y H:i').($r->user ? ' · '.$r->user->name : ''), 'title' => $r->title, 'body' => $r->body, 'rev' => $r];
            }

            return ['id' => 'current', 'label' => __('Текуща версия'), 'title' => $translation->title, 'body' => $translation->body, 'rev' => null];
        };
        $a = $pick($request->query('a', (string) ($revs->first()->id ?? 'current')));
        $b = $pick($request->query('b', 'current'));

        $ops = \App\Support\Diff::lines(
            array_merge(['# '.$a['title']], \App\Support\Diff::toLines((string) $a['body'])),
            array_merge(['# '.$b['title']], \App\Support\Diff::toLines((string) $b['body']))
        );

        return view('admin.content.compare', ['t' => $translation, 'revs' => $revs, 'a' => $a, 'b' => $b, 'ops' => $ops, 'stats' => \App\Support\Diff::stats($ops)]);
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

        return back()->with('status', __('Версията е възстановена.'));
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

    private function notifyPending(Content $content, $user): void
    {
        if (! \App\Support\Options::on('reg_notify')) {
            return;
        }
        $to = setting('contact_email') ?: \App\Models\User::where('role', 'admin')->where('active', true)->value('email');
        if (! $to) {
            return;
        }
        try {
            \Illuminate\Support\Facades\Mail::raw(__(':name изпрати статия за одобрение: :title', ['name' => $user->name, 'title' => $content->adminTitle()])."\n".route('admin.posts.edit', $content), function ($m) use ($to) {
                $m->to($to)->subject('['.setting('site_name', 'mCMS').'] '.__('Статия чака одобрение'));
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'status' => 'required|in:draft,pending,published',
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
        // no 'publish' permission (contributor): "published" becomes "pending review"
        if ($data['status'] === 'published' && ! $request->user()->allows('publish')) {
            $data['status'] = 'pending';
        }
        // per-content comment override: '' = site default (NULL). Column exists only after the database update.
        if (\App\Support\Discussion::ready()) {
            $data['comments_open'] = ($data['comments_open'] ?? '') === '' ? null : (bool) (int) $data['comments_open'];
        } else {
            unset($data['comments_open']);
        }

        return $data;
    }
}
