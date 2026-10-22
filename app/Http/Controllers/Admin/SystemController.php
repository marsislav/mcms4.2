<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    /** Lists migration files with their state (ran / pending) and the pending count. */
    public function migrations()
    {
        $files = collect(glob(database_path('migrations/*.php')) ?: [])->map(fn ($f) => basename($f, '.php'))->sort()->values();
        try {
            $ran = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('batch', 'migration')->all() : [];
        } catch (\Throwable) {
            $ran = [];
        }
        $rows = $files->map(fn ($n) => ['name' => $n, 'ran' => isset($ran[$n]), 'batch' => $ran[$n] ?? null])->all();

        return view('admin.system.migrations', [
            'rows' => $rows,
            'pending' => count(array_filter($rows, fn ($r) => ! $r['ran'])),
            'output' => session('migrate_output'),
        ]);
    }

    /** Runs pending migrations (for shared hosting without SSH, after uploading a new version). */
    public function migrate()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            session()->flash('migrate_output', trim(Artisan::output()));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('Обновяването на базата не успя: :err', ['err' => mb_substr($e->getMessage(), 0, 300)]));
        }
        Cache::forget('mcms.tags_ready');
        \App\Support\Trash::forget();
        \App\Support\Discussion::forget();
        Cache::forget('mcms.menu_extras');
        \App\Support\Members::forget();
        \App\Support\Tables::forget();
        audit('system.migrate');

        return back()->with('status', __('Базата данни е обновена.'));
    }
}
