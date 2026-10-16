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
            ['Страници', Content::where('type', 'page')->count(), route('admin.pages.index')],
            ['Публикации', Content::where('type', 'post')->count(), route('admin.posts.index')],
            ['Медия файлове', Media::count(), route('admin.media.index')],
            ['Нови съобщения', Message::whereNull('read_at')->count(), route('admin.messages.index')],
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
            ['APP_DEBUG е изключен', ! config('app.debug'), 'Задайте APP_DEBUG=false в .env'],
            ['Сайтът работи през HTTPS', request()->isSecure(), 'Включете SSL и пренасочване към https'],
            ['Административният път не е „panel“', config('mcms.admin_path') !== 'panel', 'Сменете MCMS_ADMIN_PATH в .env'],
            ['public/uploads блокира изпълнение на PHP', is_file(public_path('uploads/.htaccess')), 'Липсва public/uploads/.htaccess'],
            ['.env не е достъпен през уеб', true, 'Document root трябва да сочи към /public'],
            ['PHP версия ≥ 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), 'Обновете PHP от cPanel'],
            ['Инсталаторът е заключен', mcms_installed(), ''],
            ['Има скорошен архив на базата', collect(\App\Support\Backup::list())->contains(fn ($b) => str_starts_with($b['name'], 'db-') && $b['time'] > time() - 7 * 86400), 'Направете архив от „Архиви“'],
        ];
    }
}
