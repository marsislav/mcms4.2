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

Artisan::command('mcms:reset-2fa {email}', function (string $email) {
    $n = \App\Models\User::where('email', mb_strtolower($email))->update([
        'totp_enabled' => false, 'totp_secret' => null, 'recovery_codes' => null, 'totp_last_step' => 0,
    ]);
    $this->info($n ? '2FA disabled for the user.' : 'User not found.');
})->purpose('Disable two-factor authentication for a user (lost device)');
