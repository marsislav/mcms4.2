# mCMS 4.2

A secure multilingual CMS built on **Laravel 12** (PHP 8.2+), designed for **shared hosting without SSH**.
No external packages besides Laravel itself: two-factor authentication, sanitizer, translations, plugins, backups — all in-house code.

## Features

**Content** — pages and posts, categories, media library (with image processing), menus (2 levels), scheduled publishing, drafts, revision history (last 20, one-click restore), home page, per-page noindex, search, RSS, sitemap.xml with hreflang, robots.txt, 301/302 redirects, SEO/Open Graph/canonical.
**Multilingual (in the database)** — languages and UI translations are managed from the admin panel (`t('key')`), content has a translation per language, `/en/...` prefixes, hreflang, language switcher.
**Administration** — roles (administrator/editor/author), users, audit log, settings (logo, favicon, social networks, SMTP from the panel), maintenance mode, database and file backups, security health check on the dashboard.
**Extensibility** — plugins with hooks (`add_action`/`add_filter`), a sample `schema-org` plugin, `php artisan mcms:*` commands.
**Contact form** — honeypot + timing protection + rate limit, stored in the panel and sent by email.

## Security

| Area | Measures |
|---|---|
| Sign in | bcrypt, rate limit per email+IP and per IP, account lockout (8 failures → 15 min), constant timing for non-existent emails, session regeneration |
| 2FA | TOTP (RFC 6238), code reuse protection, 8 recovery codes (hashes only), optional "required for administrators" policy |
| Sessions | encrypted, HttpOnly, SameSite=Lax, Secure on HTTPS, idle timeout, User-Agent binding, other sessions terminated on password change |
| Admin path | configurable (`MCMS_ADMIN_PATH`), `noindex`, `no-store`, not mentioned in robots.txt |
| Headers | strict CSP with nonce (no inline scripts/styles), HSTS, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, COOP |
| Content | allow-list HTML sanitizer (DOM), javascript:/data: URL blocking, iframes only for YouTube/Vimeo, `rel=noopener` |
| Uploads | content-based MIME (finfo), random names, **GD reprocessing** (strips EXIF/payloads), pixel/size limits, `.htaccess` without PHP execution |
| Installer | requires a one-time key from `.env`, rate limit, locked after installation, never deletes existing data |
| Data | Eloquent/prepared statements, mass-assignment protection (role/2FA are not fillable), SMTP password is encrypted |
| Plugins | installed via FTP only (not through the web), a failing plugin does not take the site down |
| Audit | all important actions are logged (180 days) |

## Installation on shared hosting (no SSH)

You need, once, a computer with **PHP 8.2+ and Composer** — `vendor/` is not included in the archive.

1. Unzip the project and run in its folder:
   ```bash
   composer install --no-dev --optimize-autoloader
   php tests/standalone.php        # optional: security self-check
   ```
2. Upload the **whole** folder (including `vendor/`) to the hosting, **outside** the public directory if possible (e.g. `/home/user/mcms42`).
3. Point the domain's document root to `mcms42/public` (cPanel → Domains → Document Root).
   *If you cannot:* upload everything into `public_html` — the root `.htaccess` redirects to `public/` and blocks the internal folders (less recommended).
4. Give write permission to `storage/`, `bootstrap/cache/` and `public/uploads/` (755/775).
5. Open `https://your-domain/install`. On first load a `.env` file is created with `MCMS_INSTALL_KEY` — copy the key from that file (File Manager) into the form.
6. Fill in the MySQL, site and administrator details. After installation `/install` returns 404.
7. Sign in at `https://your-domain/panel/login`, immediately enable **2FA** (Profile) and change `MCMS_ADMIN_PATH` in `.env`.

### After installation
- In `.env`: `APP_DEBUG=false`, `TRUSTED_PROXIES=*` if you are behind Cloudflare.
- Set up SPF/DKIM for the domain or use SMTP (Settings → Outgoing mail (SMTP) → "Send me a test email").
- Create backups from Panel → Backups (database + files) and download them off the server.

## Common problems

- **`ext-gd` missing (XAMPP):** in `C:\xampp\php\php.ini` uncomment `extension=gd` (remove the `;`), restart and verify with `php -m`. The same applies to `zip`, `intl`, `exif`, `fileinfo`, `mbstring`, `pdo_mysql` if missing.
- **Composer blocks Laravel because of advisories:** use the project's current version (Laravel 12). Do not disable `block-insecure`.

## Structure

```
app/Http/Controllers/Admin/*   the admin panel   app/Support/*   Sanitizer, Totp, Uploader, Backup, Hooks...
app/Http/Controllers/Site*     the public site   app/Models/*    Eloquent models
resources/views/{admin,site}   Blade views       plugins/*       plugins
database/migrations            schema            lang/bg, lang/en  validation messages
public/assets                  CSS/JS            tests/          self-tests
```

## Plugins

`plugins/<name>/plugin.json` + `plugins/<name>/plugin.php`. Enable them from Panel → Plugins.

```php
add_action('head', fn () => print '<meta name="x" content="y">');
add_filter('content.body', fn ($html, $content) => $html);
add_action('contact.received', fn ($message) => null);
```
Hooks: `head`, `footer`, `content.body`, `content.saved`, `contact.received`, `user.login`, `admin.menu`, `site.menu`.

## Commands (only if you have a terminal)

`php artisan mcms:backup` · `mcms:unlock email` · `mcms:reset-2fa email` · `mcms:version`

## Notes

- Mail sending uses Symfony Mailer (the Laravel 12 standard; Swift_Mailer is discontinued).
- Built-in backup supports MySQL/MariaDB. Restore via phpMyAdmin (import the `.sql.gz`).
- Not tested on a live server in the development environment (no Packagist access) — preview `/install` against a test database before production.

## Documentation
Full beginner-friendly description in English: the [`docs/`](docs/README.md) folder.
