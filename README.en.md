# mCMS 4.2

A secure multilingual CMS built on **Laravel 12** (PHP 8.2+), designed for **shared hosting without SSH**.
No external packages beyond Laravel itself: sanitizer, translation, plugins, archives — all in-house code.

## Features

**Content** — pages and posts, WordPress-style comments (Settings → Discussion: on/off, moderation, threading, spam protection), categories, media library (with image processing), menus (2 levels), scheduled publishing, drafts, revision history (last 20, one-click restore), home page, per-page noindex, search, RSS, sitemap.xml with hreflang, robots.txt, 301/302 redirects, SEO/Open Graph/canonical.
**Multilingual (in the database)** — languages and UI translations are managed from the panel (`t('key')`), content has a translation per language, `/en/...` prefixes, hreflang, language switcher.
**Administration** — roles (administrator/editor/author), users, activity log, settings (logo, favicon, social networks, SMTP from the panel), "maintenance" mode, DB and file backups, security health check on the dashboard.
**Extensibility** — plugins with hooks (`add_action`/`add_filter`), sample plugin `schema-org`, `php artisan mcms:*` commands.
**Contact form** — honeypot + time-based protection + rate limit, stored in the panel and sent by email.

## Security

| Area | Measures |
|---|---|
| Login | bcrypt, rate limit by email+IP and by IP, account lockout (8 failures → 15 min), uniform response time for non-existent emails, session regeneration |
| Sessions | encrypted, HttpOnly, SameSite=Lax, Secure on HTTPS, idle expiry, User-Agent binding, other sessions terminated on password change |
| Admin path | configurable (`MCMS_ADMIN_PATH`), `noindex`, `no-store`, not mentioned in robots.txt |
| Headers | strict CSP with nonce (no inline scripts/styles), HSTS, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, COOP |
| Content | allow-list HTML sanitizer (DOM), blocking of javascript:/data: URLs, iframes only for YouTube/Vimeo, `rel=noopener` |
| Uploads | MIME by content (finfo), random names, **GD re-processing** (strips EXIF/payload), pixel/size limits, `.htaccess` with no PHP execution |
| Installer | requires a one-time key from `.env`, rate limit, locks after installation, does not delete foreign data |
| Data | Eloquent/prepared statements, mass-assignment protection (role is not fillable), SMTP password is encrypted |
| Plugins | FTP only (not via the web), a plugin error does not take the site down |
| Audit | all important actions are logged (180 days) |

## Installation on shared hosting (no SSH)

You need, once, a computer with **PHP 8.2+ and Composer** — `vendor/` is not included in the archive.

1. Unzip the project and run in its folder:
   ```bash
   composer install --no-dev --optimize-autoloader
   php tests/standalone.php        # optional: security self-check
   ```
2. Upload the **entire** folder (with `vendor/`) to the host, **outside** the public directory if possible (e.g. `/home/user/mcms42`).
3. Point the domain's document root to `mcms42/public` (cPanel → Domains → Document Root).
   *If you can't:* upload everything to `public_html` — the root `.htaccess` redirects to `public/` and blocks the internal folders (less recommended).
4. Grant write permissions on `storage/`, `bootstrap/cache/` and `public/uploads/` (755/775).
5. Open `https://your-domain/install`. On first load a `.env` with `MCMS_INSTALL_KEY` is created — copy the key from the file (File Manager) into the form.
6. Fill in the MySQL, site and administrator details. After installation `/install` returns 404.
7. Log in at `https://your-domain/panel/login`, then change the password and `MCMS_ADMIN_PATH` in `.env`.

### After installation
- In `.env`: `APP_DEBUG=false`, `TRUSTED_PROXIES=*` if you are behind Cloudflare.
- Set up SPF/DKIM for the domain or use SMTP (Settings → Mail sending → "test email").
- Make a backup from Panel → Backups (DB + files) and download it off the server.

## Common problems

- **`ext-gd` missing (XAMPP):** in `C:\xampp\php\php.ini` uncomment `extension=gd` (remove the `;`), restart and check with `php -m`. The same applies to `zip`, `intl`, `exif`, `fileinfo`, `mbstring`, `pdo_mysql` if they are missing.
- **Composer blocks Laravel because of advisories:** use the project's current version (Laravel 12). Do not disable `block-insecure`.

## Structure

```
app/Http/Controllers/Admin/*   the panel          app/Support/*   Sanitizer, Uploader, Backup, Hooks...
app/Http/Controllers/Site*     the public site     app/Models/*    Eloquent models
resources/views/{admin,site}   Blade views         plugins/*       plugins
database/migrations            schema              lang/bg         Bulgarian validation messages
public/assets                  CSS/JS              tests/          self-tests
```

## Plugins

`plugins/<name>/plugin.json` + `plugins/<name>/plugin.php`. Activate them from Panel → Plugins.

```php
add_action('head', fn () => print '<meta name="x" content="y">');
add_filter('content.body', fn ($html, $content) => $html);
add_action('contact.received', fn ($message) => null);
```
Hooks: `head`, `footer`, `content.body`, `content.saved`, `contact.received`, `user.login`, `admin.menu`, `site.menu`.

## Commands (only if you have a terminal)

`php artisan mcms:backup` · `mcms:unlock email` · `mcms:version`

## Notes

- Mail sending uses Symfony Mailer (the Laravel 12 standard; Swift_Mailer is discontinued).
- The built-in backup is for MySQL/MariaDB. Restore via phpMyAdmin (import the `.sql.gz`).
- Not tested on a live server in the development environment (no access to Packagist) — preview `/install` against a test database before production.

## Documentation
A full beginner's guide in Bulgarian: the [`docs/`](docs/README.md) folder.

## Editor
CKEditor 5 (GPL, free plugins) — see `docs/08-kak-da-promenyam.md`, section 12.
