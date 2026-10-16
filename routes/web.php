<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

/* ---------------------------------------------------------------- installer */
Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'index']);
    Route::post('/', [InstallController::class, 'store']);
});

/* -------------------------------------------------------------------- admin */
Route::prefix(config('mcms.admin_path'))->name('admin.')->group(function () {
    // Guest area
    Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login'])->name('login.post');
    Route::get('2fa', [Admin\AuthController::class, 'showChallenge'])->name('challenge');
    Route::post('2fa', [Admin\AuthController::class, 'challenge'])->name('challenge.post');

    // Authenticated area
    Route::middleware([AuthenticateSession::class, 'admin'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Own profile / security (no extra permission)
        Route::get('profile', [Admin\ProfileController::class, 'show'])->name('profile');
        Route::post('profile', [Admin\ProfileController::class, 'updateInfo'])->name('profile.info');
        Route::post('profile/password', [Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('profile/2fa/start', [Admin\ProfileController::class, 'startTwoFactor'])->name('profile.2fa.start');
        Route::post('profile/2fa/confirm', [Admin\ProfileController::class, 'confirmTwoFactor'])->name('profile.2fa.confirm');
        Route::post('profile/2fa/recovery', [Admin\ProfileController::class, 'regenerateRecovery'])->name('profile.2fa.recovery');
        Route::post('profile/2fa/disable', [Admin\ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');

        // Pages & posts share one controller; `type` is injected as a route default
        foreach (['page' => 'pages', 'post' => 'posts'] as $type => $segment) {
            Route::middleware('admin:content')->prefix($segment)->name($segment.'.')->group(function () use ($type) {
                foreach ([
                    Route::get('/', [Admin\ContentController::class, 'index'])->name('index'),
                    Route::get('create', [Admin\ContentController::class, 'create'])->name('create'),
                    Route::post('/', [Admin\ContentController::class, 'store'])->name('store'),
                    Route::get('{content}/edit', [Admin\ContentController::class, 'edit'])->whereNumber('content')->name('edit'),
                    Route::put('{content}', [Admin\ContentController::class, 'update'])->whereNumber('content')->name('update'),
                    Route::delete('{content}', [Admin\ContentController::class, 'destroy'])->whereNumber('content')->name('destroy'),
                ] as $route) {
                    $route->defaults('type', $type);
                }
            });
        }
        Route::post('revisions/{revision}/restore', [Admin\ContentController::class, 'restore'])
            ->middleware('admin:content')->whereNumber('revision')->name('revisions.restore');

        Route::middleware('admin:categories')->prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [Admin\CategoryController::class, 'index'])->name('index');
            Route::post('/', [Admin\CategoryController::class, 'store'])->name('store');
            Route::put('{category}', [Admin\CategoryController::class, 'update'])->whereNumber('category')->name('update');
            Route::delete('{category}', [Admin\CategoryController::class, 'destroy'])->whereNumber('category')->name('destroy');
        });

        Route::middleware('admin:categories')->prefix('tags')->name('tags.')->group(function () {
            Route::get('/', [Admin\TagController::class, 'index'])->name('index');
            Route::post('/', [Admin\TagController::class, 'store'])->name('store');
            Route::put('{tag}', [Admin\TagController::class, 'update'])->whereNumber('tag')->name('update');
            Route::post('{tag}/merge', [Admin\TagController::class, 'merge'])->whereNumber('tag')->name('merge');
            Route::delete('{tag}', [Admin\TagController::class, 'destroy'])->whereNumber('tag')->name('destroy');
        });

        Route::middleware('admin:media')->prefix('media')->name('media.')->group(function () {
            Route::get('/', [Admin\MediaController::class, 'index'])->name('index');
            Route::get('picker', [Admin\MediaController::class, 'picker'])->name('picker');
            Route::post('/', [Admin\MediaController::class, 'store'])->name('store');
            Route::put('{media}', [Admin\MediaController::class, 'update'])->whereNumber('media')->name('update');
            Route::delete('{media}', [Admin\MediaController::class, 'destroy'])->whereNumber('media')->name('destroy');
        });

        Route::middleware('admin:menus')->prefix('menus')->name('menus.')->group(function () {
            Route::get('/', [Admin\MenuController::class, 'index'])->name('index');
            Route::get('{menu}', [Admin\MenuController::class, 'edit'])->whereNumber('menu')->name('edit');
            Route::put('{menu}', [Admin\MenuController::class, 'update'])->whereNumber('menu')->name('update');
        });

        Route::middleware('admin:messages')->prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [Admin\MessageController::class, 'index'])->name('index');
            Route::get('{message}', [Admin\MessageController::class, 'show'])->whereNumber('message')->name('show');
            Route::delete('{message}', [Admin\MessageController::class, 'destroy'])->whereNumber('message')->name('destroy');
        });

        Route::middleware('admin:redirects')->prefix('redirects')->name('redirects.')->group(function () {
            Route::get('/', [Admin\RedirectController::class, 'index'])->name('index');
            Route::post('/', [Admin\RedirectController::class, 'store'])->name('store');
            Route::delete('{redirect}', [Admin\RedirectController::class, 'destroy'])->whereNumber('redirect')->name('destroy');
        });

        Route::middleware('admin:translations')->prefix('translations')->name('translations.')->group(function () {
            Route::get('/', [Admin\TranslationController::class, 'index'])->name('index');
            Route::put('/', [Admin\TranslationController::class, 'update'])->name('update');
        });

        // Admin-only
        Route::middleware('admin:languages')->prefix('languages')->name('languages.')->group(function () {
            Route::get('/', [Admin\LanguageController::class, 'index'])->name('index');
            Route::post('/', [Admin\LanguageController::class, 'store'])->name('store');
            Route::put('{language}', [Admin\LanguageController::class, 'update'])->whereNumber('language')->name('update');
            Route::delete('{language}', [Admin\LanguageController::class, 'destroy'])->whereNumber('language')->name('destroy');
        });

        Route::middleware('admin:users')->prefix('users')->name('users.')->group(function () {
            Route::get('/', [Admin\UserController::class, 'index'])->name('index');
            Route::get('create', [Admin\UserController::class, 'create'])->name('create');
            Route::post('/', [Admin\UserController::class, 'store'])->name('store');
            Route::get('{user}/edit', [Admin\UserController::class, 'edit'])->whereNumber('user')->name('edit');
            Route::put('{user}', [Admin\UserController::class, 'update'])->whereNumber('user')->name('update');
            Route::delete('{user}', [Admin\UserController::class, 'destroy'])->whereNumber('user')->name('destroy');
        });

        Route::middleware('admin:settings')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/test-mail', [Admin\SettingController::class, 'testMail'])->name('settings.mail-test');
            Route::post('system/migrate', [Admin\SystemController::class, 'migrate'])->name('system.migrate');
        });

        Route::get('audit', [Admin\AuditController::class, 'index'])->middleware('admin:audit')->name('audit');

        Route::middleware('admin:backups')->prefix('backups')->name('backups.')->group(function () {
            Route::get('/', [Admin\BackupController::class, 'index'])->name('index');
            Route::post('/', [Admin\BackupController::class, 'create'])->name('create');
            Route::get('{name}', [Admin\BackupController::class, 'download'])->name('download');
            Route::delete('{name}', [Admin\BackupController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('admin:plugins')->prefix('plugins')->name('plugins.')->group(function () {
            Route::get('/', [Admin\PluginController::class, 'index'])->name('index');
            Route::post('{slug}/toggle', [Admin\PluginController::class, 'toggle'])->name('toggle');
        });
    });
});

/* -------------------------------------------------------- SEO / feed files */
Route::get('sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');
Route::get('robots.txt', [SiteController::class, 'robots'])->name('site.robots');

/* ------------------------------------------------------------- public site */
$public = function () {
    Route::get('/', [SiteController::class, 'home'])->name('home');
    Route::get('feed.xml', [SiteController::class, 'feed'])->name('feed');
    Route::get('blog', [SiteController::class, 'blog'])->name('blog');
    Route::get('blog/category/{slug}', [SiteController::class, 'category'])->name('category');
    Route::get('blog/tag/{slug}', [SiteController::class, 'tag'])->where('slug', '[a-z0-9\-]+')->name('tag');
    Route::get('blog/{slug}', [SiteController::class, 'post'])->name('post');
    Route::get('search', [SiteController::class, 'search'])->middleware('throttle:30,1')->name('search');
    Route::get('contact', [SiteController::class, 'contactForm'])->name('contact');
    Route::post('contact', [SiteController::class, 'contactSend'])->middleware('throttle:10,1')->name('contact.send');
    Route::get('{slug}', [SiteController::class, 'page'])->where('slug', '[a-z0-9\-]+')->name('page');
};

// Non-default languages live under /{locale}; registered first so "/en" is never read as a page slug.
// (2-letter page slugs are therefore reserved - see ContentSaver::uniqueSlug)
Route::prefix('{locale}')->where(['locale' => '[a-z]{2}(-[A-Z]{2})?'])->name('site.l.')->group($public);
Route::name('site.')->group($public);

Route::fallback([SiteController::class, 'fallback']);
