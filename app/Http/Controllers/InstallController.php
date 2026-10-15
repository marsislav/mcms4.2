<?php

namespace App\Http\Controllers;

use App\Support\EnvWriter;
use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class InstallController extends Controller
{
    public function index()
    {
        return view('install.index', ['checks' => $this->requirements(), 'keyConfigured' => (bool) config('mcms.install_key')]);
    }

    public function store(Request $request)
    {
        $throttle = 'install:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttle, 10)) {
            return back()->withErrors(['install_key' => 'Твърде много опити. Изчакайте няколко минути.'])->withInput();
        }
        RateLimiter::hit($throttle, 600);

        $data = $request->validate([
            'install_key' => 'required|string|max:100',
            'db_host' => 'required|string|max:190',
            'db_port' => 'required|integer|min:1|max:65535',
            'db_name' => 'required|string|max:64',
            'db_user' => 'required|string|max:80',
            'db_pass' => 'nullable|string|max:200',
            'site_name' => 'required|string|max:100',
            'default_lang' => 'required|in:bg,en',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:190',
            'admin_password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()],
        ]);

        $key = (string) config('mcms.install_key');
        if ($key === '' || ! hash_equals($key, trim($data['install_key']))) {
            return back()->withErrors(['install_key' => 'Невалиден инсталационен ключ. Намерете MCMS_INSTALL_KEY във файла .env на сървъра.'])->withInput();
        }

        foreach ($this->requirements() as $check) {
            if ($check['required'] && ! $check['ok']) {
                return back()->withErrors(['install_key' => 'Сървърът не покрива изискванията: '.$check['label']])->withInput();
            }
        }

        // 1) Can we reach the database?
        try {
            new \PDO(
                "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4",
                $data['db_user'],
                $data['db_pass'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 8]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['db_host' => 'Неуспешна връзка с базата данни. Проверете хост, име, потребител и парола.'])->withInput();
        }

        // 2) Apply connection at runtime and migrate (never "fresh": we must not wipe foreign data)
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_name'],
            'database.connections.mysql.username' => $data['db_user'],
            'database.connections.mysql.password' => $data['db_pass'] ?? '',
        ]);
        DB::purge('mysql');

        try {
            if (Schema::hasTable('users') && DB::table('users')->exists()) {
                return back()->withErrors(['db_name' => 'Тази база вече съдържа данни на mCMS. Изберете празна база.'])->withInput();
            }

            Artisan::call('migrate', ['--force' => true]);
            Installer::seed($data);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['db_host' => 'Инсталацията се провали. Детайли има в storage/logs. Базата може да се използва повторно.'])->withInput();
        }

        // 3) Persist configuration and lock the installer
        EnvWriter::set(base_path('.env'), [
            'APP_NAME' => $data['site_name'],
            'APP_URL' => rtrim($request->root(), '/'),
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_LOCALE' => $data['default_lang'],
            'APP_FALLBACK_LOCALE' => $data['default_lang'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_name'],
            'DB_USERNAME' => $data['db_user'],
            'DB_PASSWORD' => $data['db_pass'] ?? '',
            'MCMS_INSTALL_KEY' => null,
        ]);
        file_put_contents(storage_path('installed.lock'), date('c')."\n", LOCK_EX);

        return redirect('/'.config('mcms.admin_path').'/login')->with('status', 'Инсталацията е успешна. Влезте с вашите данни.');
    }

    /** @return array<int, array{label:string,ok:bool,required:bool}> */
    private function requirements(): array
    {
        $writable = fn ($p) => is_dir($p) && is_writable($p);
        $checks = [
            ['PHP ≥ 8.2 (текуща: '.PHP_VERSION.')', version_compare(PHP_VERSION, '8.2.0', '>='), true],
            ['pdo_mysql', extension_loaded('pdo_mysql'), true],
            ['mbstring', extension_loaded('mbstring'), true],
            ['openssl', extension_loaded('openssl'), true],
            ['fileinfo', extension_loaded('fileinfo'), true],
            ['dom / libxml', extension_loaded('dom'), true],
            ['gd (обработка на изображения)', extension_loaded('gd'), true],
            ['intl', extension_loaded('intl'), false],
            ['zip (архив на качените файлове)', extension_loaded('zip'), false],
            ['exif (автоматично завъртане на снимки)', extension_loaded('exif'), false],
            ['storage/ е записваема', $writable(storage_path()), true],
            ['bootstrap/cache е записваема', $writable(base_path('bootstrap/cache')), true],
            ['public/uploads е записваема', $writable(public_path('uploads')), true],
            ['.env е записваем', is_writable(base_path('.env')), true],
        ];

        return array_map(fn ($c) => ['label' => $c[0], 'ok' => $c[1], 'required' => $c[2]], $checks);
    }
}
