<?php

use App\Http\Middleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware as MW;

$root = dirname(__DIR__);

/*
 | First-run bootstrap (works on shared hosting without SSH):
 | creates writable directories and a fresh .env with a random APP_KEY.
 */
foreach ([
    'storage/app/backups', 'storage/framework/cache/data', 'storage/framework/sessions',
    'storage/framework/views', 'storage/logs', 'bootstrap/cache', 'public/uploads',
] as $dir) {
    if (! is_dir("$root/$dir")) {
        @mkdir("$root/$dir", 0755, true);
    }
}

if (! is_file("$root/.env") && is_file("$root/.env.example")) {
    $env = file_get_contents("$root/.env.example");
    $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $env);
    $env = preg_replace('/^MCMS_INSTALL_KEY=.*$/m', 'MCMS_INSTALL_KEY='.bin2hex(random_bytes(8)), $env);
    @file_put_contents("$root/.env", $env, LOCK_EX);
    @chmod("$root/.env", 0600);
}

return Application::configure(basePath: $root)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (MW $middleware) {
        $middleware->prepend(Middleware\CheckInstalled::class);

        $proxies = env('TRUSTED_PROXIES');
        if ($proxies) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        $middleware->web(append: [
            Middleware\SecurityHeaders::class,
            Middleware\SetLocale::class,
            Middleware\CheckMaintenance::class,
        ]);

        $middleware->alias([
            'admin' => Middleware\AdminGuard::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Never leak details; Laravel renders resources/views/errors/4xx|5xx.blade.php
    })
    ->create();
