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

            return back()->with('error', 'Обновяването на базата не успя: '.mb_substr($e->getMessage(), 0, 300));
        }
        Cache::forget('mcms.tags_ready');
        \App\Support\Trash::forget();
        audit('system.migrate');

        return back()->with('status', 'Базата данни е обновена.');
    }
}
