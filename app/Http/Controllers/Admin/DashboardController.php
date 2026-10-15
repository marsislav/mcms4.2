<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Content;
use App\Models\Media;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $stats = [
            ['Pages', Content::where('type', 'page')->count(), route('admin.pages.index')],
            ['Posts', Content::where('type', 'post')->count(), route('admin.posts.index')],
            ['Media files', Media::count(), route('admin.media.index')],
            ['New messages', Message::whereNull('read_at')->count(), route('admin.messages.index')],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'drafts' => Content::with('translations')->where('status', 'draft')
                ->when(! $user->allows('content.all'), fn ($q) => $q->where('author_id', $user->id))
                ->latest()->limit(5)->get(),
            'messages' => $user->allows('messages') ? Message::latest()->limit(5)->get() : collect(),
            'audit' => $user->allows('audit') ? AuditLog::with('user')->latest('id')->limit(8)->get() : collect(),
            'health' => $user->isAdmin() ? $this->health($user) : [],
        ]);
    }

    /** @return array<int, array{0:string,1:bool,2:string}> label, ok, hint */
    private function health(User $user): array
    {
        return [
            ['APP_DEBUG is off', ! config('app.debug'), 'Set APP_DEBUG=false in .env'],
            ['The site is served over HTTPS', request()->isSecure(), 'Enable SSL and redirect to https'],
            ['2FA is enabled for your profile', (bool) $user->totp_enabled, 'Profile → Two-factor authentication'],
            ['The admin path is not “panel”', config('mcms.admin_path') !== 'panel', 'Change MCMS_ADMIN_PATH in .env'],
            ['public/uploads blocks PHP execution', is_file(public_path('uploads/.htaccess')), 'public/uploads/.htaccess is missing'],
            ['.env is not accessible from the web', true, 'The document root must point to /public'],
            ['PHP version ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), 'Upgrade PHP from cPanel'],
            ['The installer is locked', mcms_installed(), ''],
            ['A recent database backup exists', collect(\App\Support\Backup::list())->contains(fn ($b) => str_starts_with($b['name'], 'db-') && $b['time'] > time() - 7 * 86400), 'Create a backup under “Backups”'],
        ];
    }
}
