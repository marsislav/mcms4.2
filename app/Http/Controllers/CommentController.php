<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Support\Discussion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/** Public comment form handler (Settings > Дискусии). */
class CommentController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(Discussion::enabled(), 404);

        // Rate limit per IP (on top of the route throttle)
        $key = 'comment:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withInput()->with('comment_error', t('comments.throttle', __('Твърде много опити. Опитайте по-късно.')));
        }
        RateLimiter::hit($key, 600);

        // Honeypot: bots fill the hidden field — pretend success, store nothing
        if ($request->filled('hp_site')) {
            return back()->with('comment_ok', t('comments.held', __('Коментарът ви чака одобрение.')));
        }

        // Time trap: a human needs at least 3 s (genuine, fresh token)
        try {
            $age = time() - (int) Crypt::decryptString((string) $request->input('ts'));
        } catch (\Throwable) {
            $age = -1;
        }
        if ($age < 3 || $age > 86400) {
            return back()->withInput()->with('comment_error', t('comments.error', __('Моля, проверете полетата и опитайте отново.')));
        }

        $need = Discussion::on('require_name_email');
        $data = $request->validate([
            'content_id' => 'required|integer',
            'parent_id' => 'nullable|integer',
            'author' => ($need ? 'required' : 'nullable').'|string|max:100',
            'email' => ($need ? 'required' : 'nullable').'|email:rfc|max:190',
            'url' => 'nullable|url|max:200',
            'comment' => 'required|string|min:2|max:5000',
        ]);

        $content = Content::find($data['content_id']);
        if (! $content || ! $content->isVisible() || ! Discussion::isOpen($content)) {
            return back()->withInput()->with('comment_error', t('comments.closed', __('Коментарите са затворени.')));
        }

        $user = $request->user();
        $trusted = $user && $user->active;
        $name = trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags((string) ($data['author'] ?? ''))));
        $email = trim((string) ($data['email'] ?? ''));
        if ($trusted) {
            $name = $name ?: $user->name;
            $email = $email ?: $user->email;
        }
        $name = $name !== '' ? $name : t('comments.anonymous', __('Анонимен'));
        $url = Discussion::on('allow_url') ? trim((string) ($data['url'] ?? '')) : '';
        if ($url !== '' && ! preg_match('~^https?://~i', $url)) {
            $url = '';
        }
        $body = trim(strip_tags((string) $data['comment']));

        // Flood control and duplicate detection
        if (! $trusted) {
            $flood = max(0, (int) Discussion::get('flood_seconds'));
            $fk = 'comment-flood:'.$request->ip();
            if ($flood > 0) {
                if (RateLimiter::tooManyAttempts($fk, 1)) {
                    return back()->withInput()->with('comment_error', t('comments.throttle', __('Твърде много опити. Опитайте по-късно.')));
                }
                RateLimiter::hit($fk, $flood);
            }
            $dup = Comment::where('content_id', $content->id)->where('author_name', $name)->where('body', $body)->exists();
            if ($dup) {
                return back()->withInput()->with('comment_error', t('comments.duplicate', __('Този коментар вече е изпратен.')));
            }
        }

        $parentId = null;
        if (Discussion::on('threaded') && ! empty($data['parent_id'])) {
            $parentId = Comment::where('id', $data['parent_id'])->where('content_id', $content->id)->where('status', 'approved')->value('id');
        }

        $prior = $email !== '' && Comment::where('status', 'approved')->where('author_email', $email)->where('author_name', $name)->exists();
        $status = Discussion::decide(
            ['author' => $name, 'email' => $email, 'url' => $url, 'body' => $body, 'ip' => (string) $request->ip()],
            Discussion::all(),
            $prior,
            (bool) $trusted,
        );

        $comment = Comment::create([
            'content_id' => $content->id,
            'parent_id' => $parentId,
            'author_name' => mb_substr($name, 0, 100),
            'author_email' => $email !== '' ? $email : null,
            'author_url' => $url !== '' ? $url : null,
            'author_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'body' => $body,
            'status' => $status,
            'user_id' => $trusted ? $user->id : null,
        ]);

        if ($status !== 'spam' && ! $trusted) {
            $this->notify($comment, $content, $status);
        }
        do_action('comment.created', $comment);

        $tr = $content->translations->firstWhere('locale', app()->getLocale()) ?? $content->translations->first();
        $url = $tr ? $tr->setRelation('content', $content)->url() : url('/');

        if ($status === 'approved') {
            return redirect($url.'#comment-'.$comment->id)->with('comment_ok', t('comments.posted', __('Благодарим! Коментарът е публикуван.')));
        }

        // spam looks like "held" to the sender (do not teach bots what worked)
        return redirect($url.'#comments')->with('comment_ok', t('comments.held', __('Коментарът ви чака одобрение.')));
    }

    private function notify(Comment $comment, Content $content, string $status): void
    {
        $send = $status === 'approved' ? Discussion::on('notify_new') : Discussion::on('notify_hold');
        if (! $send) {
            return;
        }
        $to = setting('contact_email') ?: \App\Models\User::where('role', 'admin')->where('active', true)->value('email');
        if (! $to) {
            return;
        }
        $title = $content->translations->first()?->title ?? ('#'.$content->id);
        $head = $status === 'approved' ? __('Нов коментар') : __('Коментар чака одобрение');
        $text = __(':head към „:title“', ['head' => $head, 'title' => $title])."\n\n".__('Автор: :name <:email>', ['name' => $comment->author_name, 'email' => $comment->author_email])."\nIP: {$comment->author_ip}\n\n{$comment->body}\n\n"
            .__('Управление: ').admin_url('comments?status='.($status === 'approved' ? 'approved' : 'pending'))."\n";
        try {
            Mail::raw($text, function ($m) use ($to, $head, $title) {
                $m->to($to)->subject('['.setting('site_name', 'mCMS').'] '.$head.': '.mb_substr($title, 0, 80));
            });
        } catch (\Throwable $e) {
            report($e); // the comment is stored anyway
        }
    }
}
