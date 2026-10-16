<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('mcms:version', function () {
    $this->info('mCMS '.config('mcms.version'));
})->purpose('Show the mCMS version');

Artisan::command('mcms:backup', function () {
    $this->info('Database backup: '.\App\Support\Backup::database());
})->purpose('Create a database backup in storage/app/backups');

Artisan::command('mcms:unlock {email}', function (string $email) {
    $n = \App\Models\User::where('email', mb_strtolower($email))->update(['failed_attempts' => 0, 'locked_until' => null]);
    $this->info($n ? 'Account unlocked.' : 'User not found.');
})->purpose('Unlock a locked admin account');

Artisan::command('mcms:purge-trash {days=30}', function (int $days) {
    if (! \App\Support\Trash::ready()) {
        return $this->error('Trash (deleted_at) is not set up yet - run the database update first.');
    }
    $cut = now()->subDays(max(1, $days));
    $n = 0;
    foreach (\App\Models\Content::onlyTrashed()->where('deleted_at', '<', $cut)->get() as $c) { $c->forceDelete(); $n++; }
    foreach (\App\Models\Category::onlyTrashed()->where('deleted_at', '<', $cut)->get() as $c) { $c->forceDelete(); $n++; }
    $this->info("Permanently deleted $n item(s) trashed more than $days days ago.");
})->purpose('Permanently delete pages/posts/categories that have been in the trash for N days (default 30)');
