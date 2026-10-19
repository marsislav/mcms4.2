<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SystemController extends Controller
{
    /** Runs pending migrations (for shared hosting without SSH, after uploading a new version). */
    public function migrate()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('Обновяването на базата не успя: :err', ['err' => mb_substr($e->getMessage(), 0, 300)]));
        }
        Cache::forget('mcms.tags_ready');
        \App\Support\Trash::forget();
        \App\Support\Discussion::forget();
        Cache::forget('mcms.menu_extras');
        \App\Support\Members::forget();
        audit('system.migrate');

        return back()->with('status', __('Базата данни е обновена.'));
    }
}
