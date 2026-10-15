<?php

namespace App\Support;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Content;
use App\Models\ContentTranslation;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Installer
{
    /**
     * Seeds languages, UI strings, menus, sample content, settings and the first admin.
     * Runs in one transaction so a failed install can simply be retried.
     */
    public static function seed(array $d): User
    {
        return DB::transaction(function () use ($d) {
            $default = $d['default_lang']; // bg | en
            $names = ['bg' => ['Bulgarian', 'Български'], 'en' => ['English', 'English']];

            foreach (['bg', 'en'] as $i => $code) {
                Language::create([
                    'code' => $code, 'name' => $names[$code][0], 'native_name' => $names[$code][1],
                    'is_default' => $code === $default, 'is_active' => true, 'sort' => $code === $default ? 0 : 1,
                ]);
            }

            $strings = require database_path('seeders/strings.php');
            $rows = [];
            foreach ($strings as $locale => $map) {
                foreach ($map as $key => $value) {
                    $rows[] = ['key' => $key, 'locale' => $locale, 'value' => $value];
                }
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                Translation::insert($chunk);
            }

            $admin = new User;
            $admin->name = $d['admin_name'];
            $admin->email = mb_strtolower($d['admin_email']);
            $admin->password = $d['admin_password']; // hashed by cast
            $admin->role = 'admin';
            $admin->active = true;
            $admin->password_changed_at = now();
            $admin->save();

            Setting::putMany([
                'site_name' => $d['site_name'],
                'contact_email' => $admin->email,
                'posts_per_page' => '9',
                'maintenance_mode' => '0',
                'require_2fa_admin' => '0',
                'tagline.bg' => 'Модерен и сигурен сайт',
                'tagline.en' => 'A modern and secure website',
                'footer_text.bg' => '© '.date('Y').' '.$d['site_name'].'. Всички права запазени.',
                'footer_text.en' => '© '.date('Y').' '.$d['site_name'].'. All rights reserved.',
                'meta_description.bg' => $d['site_name'].' — официален сайт.',
                'meta_description.en' => $d['site_name'].' — official website.',
                'plugins_enabled' => json_encode([]),
            ]);

            // Categories
            $cat = Category::create(['sort' => 1]);
            CategoryTranslation::insert([
                ['category_id' => $cat->id, 'locale' => 'bg', 'name' => 'Новини', 'slug' => 'novini', 'description' => null],
                ['category_id' => $cat->id, 'locale' => 'en', 'name' => 'News', 'slug' => 'news', 'description' => null],
            ]);

            // Sample content
            $home = self::content('page', $admin->id, ['is_home' => true], [
                'bg' => ['Добре дошли', 'dobre-doshli', '<p>Това е началната страница на вашия нов сайт, изграден с <strong>mCMS 4.2</strong>. Редактирайте я от административния панел.</p>'],
                'en' => ['Welcome', 'welcome', '<p>This is the home page of your new website built with <strong>mCMS 4.2</strong>. Edit it from the admin panel.</p>'],
            ]);
            $about = self::content('page', $admin->id, [], [
                'bg' => ['За нас', 'za-nas', '<p>Разкажете за себе си или за вашия бизнес.</p>'],
                'en' => ['About us', 'about-us', '<p>Tell visitors about yourself or your business.</p>'],
            ]);
            self::content('post', $admin->id, ['category_id' => $cat->id], [
                'bg' => ['Първа публикация', 'purva-publikatsiya', '<p>Това е примерна публикация. Изтрийте или редактирайте я.</p>'],
                'en' => ['First post', 'first-post', '<p>This is a sample post. Delete or edit it.</p>'],
            ]);

            // Menus
            $header = Menu::create(['location' => 'header', 'name' => 'Main menu']);
            $footer = Menu::create(['location' => 'footer', 'name' => 'Footer menu']);

            $sort = 0;
            foreach ([['content', $home->id], ['content', $about->id], ['builtin', 'blog'], ['builtin', 'contact']] as [$type, $ref]) {
                MenuItem::create([
                    'menu_id' => $header->id, 'sort' => $sort++, 'type' => $type,
                    'content_id' => $type === 'content' ? $ref : null,
                    'url' => $type === 'builtin' ? $ref : null,
                    'labels' => $ref === $home->id ? ['bg' => 'Начало', 'en' => 'Home'] : null,
                ]);
            }
            MenuItem::create(['menu_id' => $footer->id, 'sort' => 0, 'type' => 'builtin', 'url' => 'contact']);
            MenuItem::create(['menu_id' => $footer->id, 'sort' => 1, 'type' => 'builtin', 'url' => 'search']);

            return $admin;
        });
    }

    private static function content(string $type, int $authorId, array $extra, array $translations): Content
    {
        $c = Content::create($extra + [
            'type' => $type, 'status' => 'published', 'publish_at' => now()->subMinute(),
            'author_id' => $authorId, 'is_home' => false,
        ]);
        foreach ($translations as $locale => [$title, $slug, $body]) {
            ContentTranslation::create([
                'content_id' => $c->id, 'locale' => $locale, 'title' => $title, 'slug' => $slug, 'body' => $body,
            ]);
        }

        return $c;
    }
}
