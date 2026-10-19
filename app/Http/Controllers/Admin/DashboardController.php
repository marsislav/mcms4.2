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
        if (! $user->allows('content')) {
            return redirect()->route('admin.profile');
        }

        $own = fn ($q) => $user->allows('content.all') ? $q : $q->where('author_id', $user->id);
        $stats = [];
        if ($user->allows('pages')) {
            $stats[] = [__('Страници'), Content::where('type', 'page')->count(), route('admin.pages.index')];
        }
        $stats[] = [$user->allows('content.all') ? __('Публикации') : __('Моите публикации'), $own(Content::where('type', 'post'))->count(), route('admin.posts.index')];
        if ($user->allows('content.all')) {
            $stats[] = [__('Чакат одобрение'), Content::where('status', 'pending')->count(), route('admin.posts.index', ['status' => 'pending'])];
        }
        if ($user->allows('media')) {
            $stats[] = [__('Медия файлове'), Media::count(), route('admin.media.index')];
        }
        if ($user->allows('messages')) {
            $stats[] = [__('Нови съобщения'), Message::whereNull('read_at')->count(), route('admin.messages.index')];
        }

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
            [__('APP_DEBUG е изключен'), ! config('app.debug'), __('Задайте APP_DEBUG=false в .env')],
            [__('Сайтът работи през HTTPS'), request()->isSecure(), __('Включете SSL и пренасочване към https')],
            [__('Административният път не е „panel“'), config('mcms.admin_path') !== 'panel', __('Сменете MCMS_ADMIN_PATH в .env')],
            [__('public/uploads блокира изпълнение на PHP'), is_file(public_path('uploads/.htaccess')), __('Липсва public/uploads/.htaccess')],
            [__('.env не е достъпен през уеб'), true, __('Document root трябва да сочи към /public')],
            [__('PHP версия ≥ 8.2'), version_compare(PHP_VERSION, '8.2.0', '>='), __('Обновете PHP от cPanel')],
            [__('Инсталаторът е заключен'), mcms_installed(), ''],
            [__('Има скорошен архив на базата'), collect(\App\Support\Backup::list())->contains(fn ($b) => str_starts_with($b['name'], 'db-') && $b['time'] > time() - 7 * 86400), __('Направете архив от „Архиви“')],
        ];
    }
}
