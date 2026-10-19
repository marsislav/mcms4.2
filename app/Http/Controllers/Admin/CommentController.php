<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Support\Discussion;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        if (! Discussion::ready()) {
            return view('admin.comments.index', ['ready' => false, 'items' => collect(), 'counts' => [], 'status' => 'pending']);
        }

        $status = $request->query('status', 'all');
        $status = in_array($status, ['all', 'pending', 'approved', 'spam', 'trash'], true) ? $status : 'all';

        $counts = Comment::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->all();
        $counts['all'] = ($counts['pending'] ?? 0) + ($counts['approved'] ?? 0);

        $items = Comment::query()
            ->with(['content.translations'])
            ->when($status === 'all', fn ($q) => $q->whereIn('status', ['pending', 'approved']), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%'.addcslashes($request->string('q')->limit(80), '%_\\').'%';
                $q->where(fn ($w) => $w->where('body', 'like', $like)->orWhere('author_name', 'like', $like)
                    ->orWhere('author_email', 'like', $like)->orWhere('author_ip', 'like', $like));
            })
            ->when($request->filled('content'), fn ($q) => $q->where('content_id', (int) $request->query('content')))
            ->latest('id')->paginate(20)->withQueryString();

        return view('admin.comments.index', ['ready' => true, 'items' => $items, 'counts' => $counts, 'status' => $status]);
    }

    /** Bulk / single status changes: approve, unapprove, spam, notspam, trash, restore, delete (permanent). */
    public function bulk(Request $request)
    {
        if (! $request->filled('action') || ! $request->input('ids')) {
            return back()->with('error', __('Изберете коментари и действие.'));
        }
        $data = $request->validate([
            'action' => 'required|in:approve,unapprove,spam,notspam,trash,restore,delete',
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer',
        ]);

        $q = Comment::whereIn('id', $data['ids']);
        if ($data['action'] === 'delete') {
            $q->whereIn('status', ['trash', 'spam']); // only trashed / spam comments can be removed for good
        }
        $n = $q->count();
        match ($data['action']) {
            'approve', 'notspam' => $q->update(['status' => 'approved']),
            'restore' => $q->update(['status' => 'approved']),
            'unapprove' => $q->update(['status' => 'pending']),
            'spam' => $q->update(['status' => 'spam']),
            'trash' => $q->update(['status' => 'trash']),
            'delete' => $q->delete(),
        };
        audit('comments.'.$data['action'], null, ['count' => $n]);

        return back()->with('status', __('Готово (:n).', ['n' => $n]));
    }

    public function emptyTrash(Request $request)
    {
        $status = $request->input('what') === 'spam' ? 'spam' : 'trash';
        $n = Comment::where('status', $status)->delete();
        audit('comments.empty_'.$status, null, ['count' => $n]);

        return back()->with('status', __('Изтрити за постоянно: :n.', ['n' => $n]));
    }

    public function edit(Comment $comment)
    {
        return view('admin.comments.edit', ['comment' => $comment->load('content.translations', 'parent')]);
    }

    public function update(Request $request, Comment $comment)
    {
        $data = $request->validate([
            'author_name' => 'required|string|max:100',
            'author_email' => 'nullable|email:rfc|max:190',
            'author_url' => 'nullable|url|max:200',
            'body' => 'required|string|min:1|max:5000',
            'status' => 'required|in:pending,approved,spam,trash',
        ]);
        $comment->update([
            'author_name' => strip_tags($data['author_name']),
            'author_email' => $data['author_email'] ?: null,
            'author_url' => $data['author_url'] ?: null,
            'body' => strip_tags($data['body']),
            'status' => $data['status'],
        ]);
        audit('comments.update', (string) $comment->id);

        return back()->with('status', __('Коментарът е записан.'));
    }

    /** Reply from the panel: published at once under the logged-in user's name. */
    public function reply(Request $request, Comment $comment)
    {
        $data = $request->validate(['body' => 'required|string|min:1|max:5000']);
        $user = $request->user();
        $reply = Comment::create([
            'content_id' => $comment->content_id,
            'parent_id' => $comment->id,
            'author_name' => $user->name,
            'author_email' => $user->email,
            'author_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'body' => strip_tags($data['body']),
            'status' => 'approved',
            'user_id' => $user->id,
        ]);
        if ($comment->status !== 'approved') {
            $comment->update(['status' => 'approved']); // answering implies approval (as in WordPress)
        }
        audit('comments.reply', (string) $reply->id);

        return redirect()->route('admin.comments.index')->with('status', __('Отговорът е публикуван.'));
    }
}
