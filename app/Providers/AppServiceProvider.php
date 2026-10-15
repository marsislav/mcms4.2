<?php

namespace App\Providers;

use App\Models\Menu;
use App\Support\PluginManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.mcms');
        Paginator::defaultSimpleView('pagination.mcms');

        if (! mcms_installed()) {
            return;
        }

        try {
            $this->applyMailSettings();
            PluginManager::boot();
        } catch (\Throwable $e) {
            report($e); // database temporarily unavailable: keep error pages working
        }

        View::composer('site.layout', function ($view) {
            $view->with([
                'languages' => mcms_languages(),
                'headerMenu' => Menu::tree('header'),
                'footerMenu' => Menu::tree('footer'),
            ]);
        });
    }

    /** SMTP credentials managed from the panel (shared hosting has no easy .env access). */
    private function applyMailSettings(): void
    {
        $host = setting('smtp_host');
        if (! $host) {
            return;
        }

        $password = null;
        if ($enc = setting('smtp_password')) {
            try {
                $password = Crypt::decryptString($enc);
            } catch (\Throwable) {
                $password = null;
            }
        }

        $enc = setting('smtp_encryption', 'tls');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) setting('smtp_port', 587),
            'mail.mailers.smtp.username' => setting('smtp_username'),
            'mail.mailers.smtp.password' => $password,
            'mail.mailers.smtp.scheme' => $enc === 'ssl' ? 'smtps' : 'smtp',
            'mail.from.address' => setting('mail_from_address', config('mail.from.address')),
            'mail.from.name' => setting('mail_from_name', setting('site_name', config('app.name'))),
        ]);
    }
}
