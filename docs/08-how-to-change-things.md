# 8. How to change the program: ready-made recipes

**Golden rule:** before changing anything, make a copy of the project (and a backup of the database from the panel).
Change one small thing at a time and check it in the browser right away.

## 1. How to see errors

1. In `.env` set `APP_DEBUG=true` (only while you are developing!). Errors are then shown in detail.
2. The log is in `storage/logs/laravel.log`; the latest error is at the very bottom.
3. On a live site, set `APP_DEBUG=false` again.
4. After changing `.env` or `config/*` you sometimes need to delete `bootstrap/cache/*.php`.

## 2. Changing colors and fonts

File: `public/assets/css/site.css`. At the very top there is a `:root { … }` block with variables
(`--accent`, `--bg`, `--text`…). Change the values and the whole site changes.
The admin panel is styled in `admin.css`.

## 3. Changing interface text (e.g. "Read more")

No code needed: Panel → **Translations** → find the key → change the text.

For new text:
1. In the template write `{{ t('my.key') }}`.
2. Add the key in the panel (Translations) or in `database/seeders/strings.php` (for new installations).

## 4. A new language

Panel → **Languages** → Add (code `de`, name). Then fill in the texts under **Translations**,
and every page gets a new tab for the language. Nothing in the code needs to be touched.
A 2-letter code (`de`) becomes the prefix in the address (`/de/…`).

## 5. Changing roles and permissions

File: `config/mcms.php`, section `roles`. Example: let authors see categories too:

```php
'author' => ['content', 'media', 'categories'],
```

The permission names are in `routes/web.php` (`admin:content`, `admin:menus` …).

## 6. A new field on pages (example: "subtitle")

1. **A new migration** (do not touch the old one!): `database/migrations/2026_02_01_000001_add_subtitle.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('content_translations', function (Blueprint $t) {
            $t->string('subtitle')->nullable();
        });
    }
    public function down(): void {
        Schema::table('content_translations', fn (Blueprint $t) => $t->dropColumn('subtitle'));
    }
};
```

2. In the model `app/Models/ContentTranslation.php` add `'subtitle'` to `$fillable`.
3. In the form `resources/views/admin/content/form.blade.php` add `<input name="tr[{{ $code }}][subtitle]">` next to the title.
4. In `Admin/ContentController.php` (the validation) add the rule `'tr.*.subtitle' => 'nullable|string|max:255'`.
5. In `app/Support/ContentSaver.php` add `subtitle` to the fields that get saved.
6. In `resources/views/site/page.blade.php` display it: `{{ $tr->subtitle }}`.
7. **Run the migration:** on hosting without SSH, run the SQL in phpMyAdmin:
   `ALTER TABLE content_translations ADD subtitle VARCHAR(255) NULL;`
   (with SSH/locally: `php artisan migrate`).

## 7. A new section in the admin panel (example: "Partners")

1. A migration + the model `app/Models/Partner.php`.
2. The controller `app/Http/Controllers/Admin/PartnerController.php` (modeled on `RedirectController`, the simplest one).
3. Routes in `routes/web.php`, in the admin group: copy the `redirects` block (line ~83) and change the names:
   `Route::middleware('admin:partners')->prefix('partners')->name('partners.')->group(function () { … });`
4. Templates in `resources/views/admin/partners/` (copy `redirects`).
5. The `partners` permission in `config/mcms.php` for the roles that should have access (the administrator has `*`).
6. A link in the panel menu, in the file `resources/views/admin/layout.blade.php` (the menu array).
7. Do not forget `audit('partner.create', …)` when saving.

## 8. A new plugin

A folder `plugins/my-plugin/` with two files:

`plugin.json`
```json
{ "name": "My plugin", "version": "1.0.0", "description": "What it does" }
```

`plugin.php`
```php
<?php
// adds text at the end of every page
add_action('footer', function () {
    echo '<p>Hello from the plugin</p>';
});
```

Upload the folder over FTP → Panel → **Plugins** → Enable.
Available hooks: `head`, `footer`, `content.body` (filter), `content.saved`, `contact.received`,
`user.login`, `admin.menu`, `site.menu`. See `plugins/schema-org/` as an example.

## 9. A new menu location

In `Menu` the locations are `header` and `footer`. For a new one (e.g. `sidebar`): add a record to the `menus` table
(column `location`), display it with `Menu::tree('sidebar')` in `AppServiceProvider` (view composer) and in the template.

## 10. Changing the limits

`.env` / `config/mcms.php`: `MCMS_MAX_UPLOAD_MB`, `MCMS_IDLE_MINUTES`, `MCMS_ADMIN_PATH`.
Note that PHP has its own limits too (`upload_max_filesize`, `post_max_size`).

## 11. Commonly made mistakes

| Mistake | Solution |
|---|---|
| I changed a `.blade.php` and nothing happened | Delete `storage/framework/views/*.php` |
| `Class not found` | A `use …;` is missing at the top, or the name/namespace is wrong |
| 419 Page Expired | The form is missing `@csrf`, or the session has expired |
| 403 on save | The role does not have this permission (`config/mcms.php`) |
| A new column "does not save" | It was not added to the model's `$fillable` |
| A new column is missing from the database | The migration was not run (recipe 6, step 7) |
