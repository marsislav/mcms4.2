# 8. How to change the program — ready-made recipes

**Golden rule:** before making a change, copy the project (and make a database backup from the panel).
Change one small thing at a time and check it in the browser right away.

## 1. How to see errors

1. In `.env` set `APP_DEBUG=true` (only while developing!). Errors are shown in detail.
2. The log is in `storage/logs/laravel.log` — the latest error is at the bottom.
3. On a live site, set `APP_DEBUG=false` again.
4. After changing `.env` or `config/*` you sometimes need to delete `bootstrap/cache/*.php`.

## 2. Changing colors and fonts

File: `public/assets/css/site.css`. At the very top there is a `:root { … }` block with variables
(`--accent`, `--bg`, `--text`…). Change the values — the whole site changes.
The panel's styles are in `admin.css`.

## 3. Changing interface text (e.g. "Read more")

No code: panel → **Translations** → find the key → change the text.

New text:
1. In the template write `{{ t('moy.klyuch') }}`.
2. Add the key in the panel (Translations) or in `database/seeders/strings.php` (for new installations).

## 4. A new language

Panel → **Languages** → Add (code `de`, name). Then fill in the texts in **Translations**,
and every page gets a new tab for the language. Nothing in the code is touched.
A 2-letter code (`de`) becomes the URL prefix (`/de/…`).

## 5. Changing roles and permissions

File: `config/mcms.php`, `roles` section. Example — let authors see categories too:

```php
'author' => ['content', 'media', 'categories'],
```

The permission names are in `routes/web.php` (`admin:content`, `admin:menus` …).

## 6. A new field on pages (example: "subtitle")

1. **A new migration** (don't touch the old one!): `database/migrations/2026_02_01_000001_add_subtitle.php`

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
7. **Run the migration:** on hosting without SSH — run the SQL in phpMyAdmin:
   `ALTER TABLE content_translations ADD subtitle VARCHAR(255) NULL;`
   (with SSH/locally: `php artisan migrate`).

## 7. A new section in the admin panel (example: "Partners")

1. Migration + model `app/Models/Partner.php`.
2. Controller `app/Http/Controllers/Admin/PartnerController.php` (modeled on `RedirectController` — the simplest).
3. Routes in `routes/web.php`, in the admin group — copy the `redirects` block (line ~83) and change the names:
   `Route::middleware('admin:partners')->prefix('partners')->name('partners.')->group(function () { … });`
4. Templates in `resources/views/admin/partners/` (copy `redirects`).
5. The `partners` permission in `config/mcms.php` for the roles that have access (the admin has `*`).
6. A link in the panel menu — in the file `resources/views/admin/layout.blade.php` (the menu array).
7. Don't forget `audit('partner.create', …)` when saving.

## 8. A new plugin

A folder `plugins/moy-plugin/` with two files:

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

Upload the folder via FTP → panel → **Plugins** → Enable.
Available hooks: `head`, `footer`, `content.body` (filter), `content.saved`, `contact.received`,
`user.login`, `admin.menu`, `site.menu`. See `plugins/schema-org/` for an example.

## 9. A new menu location

In `Menu` the locations are `header` and `footer`. For a new one (e.g. `sidebar`): add a record to the `menus` table
(column `location`), display it with `Menu::tree('sidebar')` in `AppServiceProvider` (view composer) and in the template.

## 10. Changing the limits

`.env` / `config/mcms.php`: `MCMS_MAX_UPLOAD_MB`, `MCMS_IDLE_MINUTES`, `MCMS_ADMIN_PATH`.
Note that PHP also has its own limits (`upload_max_filesize`, `post_max_size`).

## 11. Commonly made mistakes

| Mistake | Solution |
|---|---|
| I changed a `.blade.php` and there is no effect | Delete `storage/framework/views/*.php` |
| `Class not found` | A `use …;` is missing at the top, or the name/namespace is wrong |
| 419 Page Expired | `@csrf` is missing in the form, or the session has expired |
| 403 on save | The role does not have this permission (`config/mcms.php`) |
| A new column "doesn't save" | It was not added to the model's `$fillable` |
| A new column is missing in the database | The migration was not run (recipe 6, step 7) |

## 12. The editor (CKEditor 5)

The "Content" field is **CKEditor 5** (the free GPL version, all free plugins). Files:

| File | Role |
|---|---|
| `public/assets/vendor/ckeditor5/` | CKEditor itself (don't touch). License: `LICENSE.md`, `COPYING.GPL` |
| `public/assets/js/editor.js` | Our configuration: which plugins, which buttons, Bulgarian, the "Media" button (the library) |
| `app/Support/Sanitizer.php` | What part of the editor's HTML is kept on save |

**Adding/removing a button:** in `editor.js` — the `toolbar.items` array. The plugin must also be in `names`.

**Important:** everything the editor produces goes through `Sanitizer`. If you add a new plugin and
the result "disappears" after saving — Sanitizer does not allow it. Extend `TAGS`, `ATTRS` or `STYLE_PROPS`
in `Sanitizer.php` and run `php tests/standalone.php`.

**Video:** YouTube/Vimeo are saved as `<oembed url="…">` and are turned into an iframe on display
(`Sanitizer::renderEmbeds`, called from `mcms_body()` in `app/helpers.php`).

**Content styles** (alignment, bullets, images, tables) are at the end of `public/assets/css/site.css`.

**Deliberately not included:** Emoji (downloads data from an external CDN), paid/cloud features, uploading images
directly in the editor (use the "Media" button), HtmlEmbed, TodoList, Bookmark.

**License:** CKEditor 5 is GPL. For a personal site this is fine; if you sell/distribute the system to clients,
read https://ckeditor.com/legal/ckeditor-licensing-options .

## 13. Rows and columns in the editor ("Columns" button)

The editor toolbar has a **"Columns (row)"** button → choose from 1 to 12 columns. A row is inserted into the text; in each column you write as in normal text
(headings, images, tables…). The other buttons (active when the cursor is inside a column):

| Button | What it does |
|---|---|
| Column width | The column takes 1–12 "parts" of the row (e.g. a row of 4 parts: columns 1+2+1) |
| Add column / Remove column | Changes the number of columns (1–12) |
| Delete entire row | Removes the row with everything in it |

It is saved as `<div class="mc-row mc-cols-3"><div class="mc-col mc-span-1">…</div>…</div>`.
Styles: `public/assets/css/grid.css` (loaded both on the site and in the editor). **Responsive:** above 900px — as configured;
641–900px — 2 columns per row; up to 640px — stacked one under another. Plugin code: `public/assets/js/editor-grid.js`.
A row inside a row (nested rows) is not allowed.

## 14. Tags and related posts

**Tags** (posts only): in the post form, the "Tags" card — type comma-separated (`php, laravel`);
new tags are created automatically. Existing ones are added with a click. The **Tags** menu — name/slug translation per language, merging and deleting.
On the site: under the article are links to `/blog/tag/name`, and the tag page is a list of posts (like a category).

**Related posts:** the "Related posts" card — start typing a title and pick one. Under the article are shown:
1) the manually chosen ones, 2) then posts with the most tags in common, 3) then ones from the same category. The number is set in *Settings → Related posts*
(0 = off).

**Files:** `database/migrations/2026_02_01_000001_add_tags_and_related.php` (tables `tags`, `tag_translations`, `content_tag`, `content_related`),
`app/Support/Taxonomy.php` (logic and algorithm), `app/Models/Tag*.php`, `Admin/TagController.php`, `SiteController::tag()`, `routes/web.php`.

**Upgrading an existing installation (no SSH):** upload the new files → on the dashboard press **"Update database"**
(or Tags → "Update database"). Alternative: run `database/upgrade/4.2-upgrade.sql` in phpMyAdmin.
Until the database is updated, the site works as before, and the tag cards show a hint.

## 15. Trash (soft delete)

Pages, posts and categories are **not deleted immediately**: "Delete" moves them to the trash (the `deleted_at` column in the database).
- Pages / Posts → the **Trash** tab: "Restore", "Delete permanently", "Empty trash".
- Categories → the "Trash" section at the bottom. Posts keep their category, so on restore everything comes back.
- In the trash, records are not visible on the site, in search, in the sitemap or in menus. Their addresses (slugs) stay taken.
- Command for automatic cleanup: `php artisan mcms:purge-trash 30` (permanently deletes items older than 30 days; can be put in cron).
- Code: `app/Models/Concerns/Trashable.php` (guarded against crashing if the database is not yet updated) and `app/Support/Trash.php`.
- Upgrading an existing installation: the "Update database" button on the dashboard or `database/upgrade/4.2-upgrade.sql`.

## 16. Font Awesome

**Font Awesome Free 7.3.1** is bundled locally (no CDN): `public/assets/vendor/fontawesome/` (CSS, fonts, `icons.json` with the icon list).
It is loaded on the site (`site/layout.blade.php`) and in the panel (`admin/layout.blade.php`).

- **In the editor:** the star button "Icon (Font Awesome)" → search by name (in English), choose a style (Solid / Regular / Brands) → the icon is inserted into the text.
  It is saved as `<span class="fa-solid fa-house" aria-hidden="true"></span>`. The icon is part of the line — it inherits the color and size of the surrounding text.
- **In templates:** `<i class="fa-solid fa-envelope"></i>` (Blade). All icons: https://fontawesome.com/search?o=r&m=free
- Code: `public/assets/js/editor-fa.js`. License: icons CC BY 4.0, fonts SIL OFL, code MIT (`vendor/fontawesome/LICENSE.txt`) — keep it.
- The free version has no "Pro" styles (light, thin, duotone).

## 17. Design (Settings → "Design" tab)

Everything is stored in the `settings` table with `design.*` keys — **no migration needed**.
The generated CSS is served from `/design.css?v=…` (CSP-compatible, no inline styles) and is loaded after `site.css`.

| Group | What you configure |
|---|---|
| Colors | background, text, headings, accent, header and footer background. Empty = the theme's color. If you set only the background or only the text, the other is picked automatically for contrast |
| Fonts | body, headings, menu, logo; base size 14–22 px. System fonts only (no external requests) |
| Logo and header | logo height `auto` or 20–300 px; sticky header on/off |
| Page animation | 6 types (fade, 4 directions, zoom), speed in ms, **on/off switch**, optionally a "Stop animations" button for visitors (stored in their browser) |
| Element animations | a separate type for: header, logo, menus, cards, images, headings in the text, footer. Shown when scrolling to the element. A **master switch** stops them all |
| Top-bar | on/off, 1–4 columns, alignment and content for each column and each language (text or HTML: links, bold, Font Awesome icons), colors, showing a phone number |

- Visitors with "reduce motion" enabled on their device see no animations, whatever is configured.
- "Restore default design" deletes all `design.*` settings.
- Code: `app/Support/Design.php` (values, validation, CSS), `Admin/SettingController::updateDesign`, `resources/views/admin/partials/design-form.blade.php`, `public/assets/js/site.js` (animations), `site.css` (top-bar, keyframes).
- A new font: an entry in `Design::FONTS`. A new animated element: `Design::ELEMENTS` + a selector in `SELECTORS` in `site.js` + an `el_<name>` key in `Design::DEFAULTS`.

## 18. Uploading images (posts, pages, editor)

Images can be uploaded directly, without going through "Media":

- **In CKEditor:** the button "Upload image from computer", dragging a file onto the text, or pasting (Ctrl+V) an image. The file goes into the library and is inserted into the text.
- **The "Image" field of a post/page** (and logo/favicon in Settings): the **⬆ Upload** button or dragging an image onto the field. "Library" opens the files already uploaded.
- **The "Library" window:** the **⬆ Upload** button (a single file is selected immediately; several are only added) or dragging onto the window.

Every upload goes through `Uploader` (type check, re-encoding with GD, random name). Endpoints: `POST /panel/media/upload` (JSON) and `POST /panel/media/editor-upload` (for CKEditor, images only).
Limit: `MCMS_MAX_UPLOAD_MB` in `.env` (8 MB), but the host has its own — `upload_max_filesize` and `post_max_size` in PHP. On a 413 error, raise them.

## 19. Automatic conversion of images to WebP

On upload (library, "Image" fields, CKEditor) every **JPG, PNG and static GIF** image is converted to **WebP** and stored in the `media` table as `image/webp` (with the new size, width and height). The thumbnail is also WebP.

- JPG → WebP lossy, with the quality from Settings → General → "WebP quality" (default 82).
- PNG and GIF → WebP **lossless** (keeps transparency and sharp edges).
- Animated GIFs stay GIF (GD's WebP does not support animation). PDFs are not changed.
- Can be turned off in Settings (the "Convert uploaded images to WebP" checkbox). If the host's GD has no WebP support, the upload continues in the original format.
- Images already uploaded are **not converted** — only new ones.
- Code: `app/Support/Uploader.php` (`webpTarget`, `reencode`).

## 20. Two-factor authentication (2FA) removed

In 4.2 there is no 2FA: login is by email and password (with attempt limiting, account lockout, automatic logout and browser binding). The code page, the Profile section, the "mandatory 2FA" setting, the `mcms:reset-2fa` command and the `Totp` class have been removed.
If you had 2FA enabled, it simply stops being required. The `totp_*` columns in `users` remain unused and do no harm — no migration is needed.

## 21. Comments (Settings → Discussion)

A comment system under posts and pages, modeled on WordPress's **Settings → Discussion**. The `comments` table + the `contents.comments_open` column are created with the **"Update database"** button (Dashboard) or with `database/upgrade/4.2-upgrade.sql`.

**Settings ("Discussion" tab)**
- **Master on/off switch** — when off: no form, and already written comments are not shown.
- Defaults: comments under new posts / pages; name and email required; "Website" field; avatars (Gravatar, off by default — they send an email hash to gravatar.com).
- Display: automatically close comments older than N days, comments per page, order (oldest/newest first), nested replies up to 5 levels.
- Email notifications: new comment / comment awaiting approval (to the contact email).
- Before a comment appears: every comment manually; the author must have a previously approved comment.
- Moderation and spam: hold at N or more links, moderation keywords, banned words (→ "Spam"), a delay between comments from one IP.

**Per page/post:** a "Discussion" box in the editor — Default / Open / Closed.

**Management:** the "Comments" menu (with a pending counter) — tabs All / Pending / Approved / Spam / Trash, bulk actions, search, editing, replying from the panel. Spam and trash are cleaned by `php artisan mcms:purge-trash`.

**Protections:** CSRF, a hidden honeypot field, a timing check (≥3 sec), per-IP attempt limiting, duplicate detection, the text is stored as plain text and displayed escaped (links get `rel="nofollow ugc"`). Team members logged in to the panel post without moderation.

Code: `app/Support/Discussion.php`, `CommentController` (public), `Admin/CommentController`, views `site/partials/comments*.blade.php`, `admin/comments/*`, `admin/partials/discussion-form.blade.php`.
Not included (unlike WordPress): pingbacks/trackbacks, registered readers, Akismet.

## 22. CSS class and ID of menu items

In **Menus → edit**, every row has **CSS class** (one or more, space-separated) and **ID** fields. They are added to the item's `<li>` (as in WordPress), in the header (and in the dropdown submenu) and in the footer:

```html
<li class="has-sub btn highlight" id="menu-shop"><a href="/shop">Shop</a> …</li>
```

```css
#menu-shop > a { font-weight: 700; }
.highlight > a { color: #c00; }
```

- Class: letters, digits, `-` and `_`, must not start with a digit. ID: starts with a letter, no spaces, **unique** within the menu (duplicates are rejected).
- Dangerous characters (quotes, `;`, `<`) are rejected on save and are also filtered on display.
- The columns `css_class`, `css_id` in `menu_items` are required — the "Update database" button (Dashboard) or `database/upgrade/4.2-upgrade.sql`. Until that is done, the fields are not shown and menus work as before.
- Code: `MenuItem::cleanClass/cleanId`, `Admin/MenuController`, `site/layout.blade.php`.

## 23. Widgets: footer with columns and blog sidebar

**Settings → Footer / Sidebar.** No migration — everything is in `settings` (JSON).

- **Footer:** choose 0–12 columns (0 = the standard footer). In each column you add widgets, ordered with ↑ ↓.
- **Sidebar:** on/off, side (left/right). Shown **only in the blog** (list, category, tag, post) — not on pages. On a phone it goes below the content.
- **Widget types:** Text/HTML (with Font Awesome — an icon button), Menu (a menu is chosen by location), Recent posts (count, date, image), Categories, Tags (cloud/list), Recent comments, Search, Social networks.
- Each widget: a title per language, icon, CSS class. HTML is cleaned by the Sanitizer on save.
- If the columns are empty → the standard footer. The "Footer text" stays at the very bottom.
- Code: `app/Support/Widgets.php`, `Admin/SettingController::updateFooter/updateSidebar`, `admin/partials/{footer-form,sidebar-form,widget-card,widget-area}`, `site/partials/widget-area`, `site/widgets/*`, `site/layout.blade.php`, JS in `admin.js`. A new type: add it to `Widgets::TYPES`, `clean()`, `widget-card` and `site/widgets/<type>.blade.php`.

## 24. WordPress-style settings (Reading, Writing, Date and time, Links, Media, Scripts, Maintenance)

Tabs in **Settings** (administrator only). No migration — `opt.*` keys in `settings`. Logic and validation: `app/Support/Options.php` (tests in `tests/standalone.php`); the forms are in `admin/partials/options/*.blade.php`, saving — `SettingController::updateOptions`.

| Tab | What it does |
|---|---|
| Reading | home = a static page or latest posts; count and full text in RSS; excerpt length; "hide from search engines" (meta noindex + `robots.txt` Disallow) |
| Writing | default category and status for a new post |
| Date and time | time zone (caution: shifts dates already saved), date and time format — `mcms_date()` in templates |
| Links | blog address, category and tag prefix (`/blog/category/x` → e.g. `/news/topics/x`); old addresses are redirected with 301 (`Options::legacyPath`) |
| Media | max file size, max image side, thumbnail width (empty = from `config/mcms.php`/`.env`) |
| Scripts | code in `<head>` and before `</body>` (auto CSP nonce) + a list of allowed external https addresses for script-src/connect-src |
| Maintenance | the message on the 503 page and IP addresses that always see the site (the mode is switched on in "General") |

- Links: the route names (`blog`, `category`, `tag`, `post`) are the same, so menus, maps and the sitemap follow the new addresses by themselves. The blog address cannot be a system one (`search`, `panel`…), two letters long, or match an existing page.
- Scripts: an external script works only from an address in the list (CSP). Don't add unfamiliar domains.
- The time zone is applied in `AppServiceProvider::applyOptions()`.

## 25. Registration, WordPress-style roles, bio and author page

**Settings → Users:** "Anyone can register" (the `/register` page), the role of new users, administrator approval, email notifications. The migration `2026_03_03_000001_add_user_profile` is required ("Update database" button or `database/upgrade/4.2-upgrade.sql`) — until then registration is off, and everything else works.

| Role | Can |
|---|---|
| Subscriber | profile and bio only |
| Contributor | writes their own articles; "Published" becomes "Pending approval"; cannot edit an already published one; no media |
| Author | writes, edits and publishes their own articles; uploads media |
| Editor | all articles and pages, approves pending ones, categories, menu, comments |
| Administrator | everything |

- Pages (`pages`) and settings are for editor/administrator only (previously authors had access to pages).
- Status `pending` ("Pending approval"): a filter in the list, a dashboard counter, an email to the administrator.
- **Bio:** My profile → "Bio" (CKEditor, cleaned by the Sanitizer) + website. Shown at `/author/{username}` (with their articles) and as an "About the author" box under the article. A parameter in Settings → Reading: "Show the author".
- A "Login / Register" widget for the footer/sidebar.
- Code: `RegisterController`, `app/Support/Members.php`, `Admin/ProfileController::updateBio`, `SiteController::author`, `site/{register,author}.blade.php`, `site/widgets/account.blade.php`, `ContentController` (pending/permissions).
- Registration protection: CSRF, a hidden field + minimum time, 5 attempts/hour per IP, password ≥ 10 characters. There is no email confirmation or "forgot password" (the password is reset by an administrator) — if you get spam, turn on approval.

## 26. Interface languages (English / Bulgarian)

- **Admin panel, login and installer:** English by default. Switch with `?lang=en|bg` (or the EN/BG button); the choice is stored in the `mcms_admin_lang` cookie (1 year). Default language: `MCMS_ADMIN_LOCALE=en|bg` in `.env` (`config/mcms.php → admin_locale`).
- **Mechanism:** strings in code are Bulgarian keys inside `__('…')`; `lang/en.json` holds the English values (with `bg` the key itself is returned — `fallback_locale = bg`). Validation: `lang/en/validation.php`, `lang/bg/validation.php`.
- **JavaScript:** `window.T('…')` (`public/assets/js/i18n.js`); the dictionary comes from `lang/js.json` via `admin/partials/i18n.blade.php`; the CKEditor language follows `T.lang`.
- **New text:** write it as `__('Bulgarian text')` and add the line to `lang/en.json` (for JS, also to `lang/js.json`). Placeholders: `:name` etc.
- **The public site** has its own languages (the `languages` table, translations under “Translations”) — independent of the panel language.
- English docs: `docs/en/`, `README.en.md`.

## 27. Additional options

- **Settings → Reading:** reading time, last-modified date, “Share” buttons (plain links, no external scripts), “Back to top” button (↑). Keys `opt.reading_time`, `opt.show_updated`, `opt.share_buttons`, `opt.back_to_top`.
- **Settings → Scripts:** “Custom CSS” (`opt.custom_css`) — appended to `/design.css` (CSP-compatible), stripped of `<`, `>`, `@import`, `expression()`. Code: `Options::cleanCss()`, `SiteController::designCss()`.
- `Options::readingMinutes()` — 200 words/min, at least 1.

## 28. Extra fields in the contact form

- **Settings → Contact form.** Types: text, long text, email, phone, website, number, date, dropdown, checkbox. Per field: a label in each language, required, options (for the dropdown), “Delete”. The order is the order in the list; after saving, 3 more empty rows are added.
- Storage: the `contact.fields` setting (JSON) — no migration. Values are appended to the message text as “Label: value” lines after `— — —`, so they show in “Messages” and in the email to you.
- Per-type validation in `ContactFields::rules()`; the protections (honeypot, timing, rate limit) are unchanged.
- Code: `app/Support/ContactFields.php`, `SiteController::contactForm/contactSend`, `SettingController::updateContact`, `site/contact.blade.php`, `admin/partials/contact-form.blade.php`.

## 29. Contact form builder v2

- Fields are added with “Add field”, removed with ✕ and **reordered with ↑ ↓** (the saved order is the order on the site).
- Types: text, long text, email, phone, website, number, date, time, dropdown, radio buttons, checkbox, several checkboxes, heading/divider.
- Per field: label, placeholder and help text (per language), default value, required, width (full, 1/2, 1/3, 2/3), min/max characters, number/date range, textarea rows, CSS class.
- Code: `ContactFields::clean/rules/lines/normalize`, `site/partials/contact-field.blade.php`, `admin/partials/contact-field-row.blade.php`, builder JS at the end of `admin.js`. Fields saved by an older version keep working (`normalize()` fills defaults).

## 30. Growth pack (needs “Update the database”: Settings → Administration → Migrations)

- **Migrations page** (`/admin/system/migrations`): runs pending migrations in the panel (no SSH). New tables: `page_views`, `not_found_log`, `subscribers`, `blocks`; every feature checks `Tables::has()` and degrades gracefully until they exist.
- **Breadcrumbs + JSON-LD** (Settings → SEO & navigation): `Breadcrumbs`, `StructuredData`, `site/partials/breadcrumbs.blade.php`; views declare their kind with `@section('kind', 'page|post|list')`.
- **404 log**: `NotFoundLog::record()` is called from `SiteController::notFound()`; admin list “404 log” turns an entry into a redirect with one click.
- **Revisions**: Settings → Writing → number of revisions kept (0 = none); “Compare” in the editor opens a side-by-side diff (`Diff`), any version can be restored (the current text is saved as a revision first).
- **Blocks & templates**: `[block slug]` in any content/HTML widget, “Block” widget, templates offered when creating a post/page (`Blocks`).
- **Newsletter**: widget “Newsletter”, double opt-in, unsubscribe link, admin list + CSV export (admin only). Settings → Newsletter.
- **GDPR cookie banner** (Settings → Cookies (GDPR)): categories Statistics/Marketing; scripts in those two fields load only after consent (re-injected with the CSP nonce by `site.js`); cookie `mcms_consent`.
- **Live search** (Settings → Search): `/search/live?q=` JSON + dropdown (`data-live` attribute on the search input).
- **Reading progress bar**: look in Settings → Design; `read-progress` element + script in `site.js`.
- **Visit counter** (Settings → Statistics): `Stats::hit()`, one row per day and URL, no IPs/cookies; “Statistics” page; “Most read” widget for Sidebar/Footer.
- **Quick create**: “＋ New” menu in the panel top bar and an admin bar on the public site (Settings → Administration).
- **Error log** (admin only): reads `storage/logs/*.log`, filter by level, download, clear (`ErrorLog`).
- **Settings** now has a grouped side navigation (Site, Content, Appearance, Visitors, SEO & statistics, System).
