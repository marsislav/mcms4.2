# 6. Security — what the application does to stay protected

Each protection is explained using the pattern: **what it guards against → how → where it lives in the code**.

## Login and passwords

| Protection | How it works | Where |
|---|---|---|
| Password hashing | The password is stored "ground up" (bcrypt) — it cannot be reversed | `User`, `AuthController` |
| Attempt limiting | 5 attempts per 10 min per email+IP; 20 per IP | `AuthController::login` |
| Lockout | After 8 wrong passwords — 15 min | `users.failed_attempts`, `locked_until` |
| Uniform response time | If the email does not exist, a fake password is still checked | `AuthController` |
| New session ID on login | Prevents "session fixation" | `completeLogin` |
| Automatic logout | After 30 min of inactivity (`MCMS_IDLE_MINUTES`) | `AdminGuard` |
| Browser binding | A different User-Agent = logout | `AdminGuard` |
| Encrypted sessions | Session data is encrypted | `config/session.php` |

## Two-factor authentication

Two-factor authentication (2FA) has been **removed** from mCMS 4.2. Login uses only email and password, so use a long, unique password, change `MCMS_ADMIN_PATH`, and keep HTTPS enabled. (The `totp_*` and `recovery_codes` columns in the `users` table remain unused and harmless.)

## Form protection

| Threat | Protection | Where |
|---|---|---|
| **CSRF** (another site submits a form on your behalf) | Hidden secret token `@csrf` | all `<form method="post">` |
| **Spam bots** | Hidden `website` field + encrypted timestamp `ts` (under 3 sec = bot) + limit of 3 per 10 min | `SiteController::contactSend` |
| **Overload** | `throttle:` on search and contact | `routes/web.php` |

## XSS — injection of a foreign script

1. Everything output through `{{ }}` is escaped automatically.
2. HTML from the editor passes through the **`Sanitizer`** on save: it allows only known tags and attributes;
   blocks `javascript:` and `data:` links, `<script>`, `onclick=` and the like; `<iframe>` only for YouTube/Vimeo
   (`config/mcms.php → embed_hosts`); adds `rel="noopener"` to external links.
3. **CSP** (Content-Security-Policy) tells the browser "run only scripts carrying my secret nonce".
   That is why the project has **no** inline `onclick` or `<style>` without a nonce.

## Security headers (`SecurityHeaders`)

| Header | Meaning |
|---|---|
| `Content-Security-Policy` | Which scripts/styles are allowed |
| `Strict-Transport-Security` | "Always use HTTPS" (only over HTTPS) |
| `X-Content-Type-Options: nosniff` | The browser does not "guess" the file type |
| `X-Frame-Options` | The site cannot be embedded in a foreign `<iframe>` (prevents clickjacking) |
| `Referrer-Policy` | Does not leak internal URLs to other sites |
| `Permissions-Policy` | Disables camera, microphone, geolocation |
| `Cross-Origin-Opener-Policy` | Isolates the window |
| Admin: `no-store`, `noindex` | Panel pages are not cached and do not appear in Google |

## File uploads (`Uploader`)

Uploaded files are the most common route for hacking. Therefore:

1. The type is determined from the **content** (`finfo`), not from the name or from what the browser claims.
2. Allowed: JPG, PNG, WebP, GIF, PDF.
3. The name is replaced with a random one (nobody can slip in `shell.php`).
4. Images are **re-encoded through GD** (and converted to WebP) — this destroys any hidden code in the file.
5. GIFs are checked for `<?` and `<script`; PDFs — for a valid header.
6. Size limit (8 MB) and pixel limit (40 million); images are scaled down to 2560 px.
7. `public/uploads/.htaccess` forbids PHP execution in this folder.

## Installer

- A one-time **key** `MCMS_INSTALL_KEY` from `.env` is required (you can see it only if you have access to the file).
- It refuses to run against a database that already contains mCMS tables.
- After installation the key is deleted and `/install` returns 404.
- Limit: 10 attempts.

## Data and permissions

- **Mass assignment:** the `role` and `active` fields are not in `$fillable` — they cannot be overwritten with a forged request.
- **Roles** (`config/mcms.php`): `admin` — everything; `editor` — content, categories, media, menus, messages…; `author` — only their own content and media. Checked in `AdminGuard` (`admin:permission`).
- **The SMTP password** is stored encrypted (`Crypt`).
- **Audit log** (`audit_logs`): who, what, when, from which IP; kept for 180 days.
- **Backups** in `storage/app/backups` — `storage/.htaccess` blocks outside access.
- **Plugins** are installed only via FTP (not through the panel) — so a compromised account cannot upload code.
- **Hidden admin path:** `/panel` instead of `/admin` (changeable with `MCMS_ADMIN_PATH`); `robots.txt` does not mention it.

## What you need to do

1. Enable **HTTPS** (free Let's Encrypt from your hosting).
2. Point the document root to `public/`.
3. Use a strong, unique password.
4. Make backups and download them to your own computer.
5. Keep PHP and Laravel up to date (`composer update` from time to time).
6. In `.env`: `APP_DEBUG=false` on a live site!

> Honestly: no system is "unbreakable". This is a good foundation, but it has not undergone an independent audit.

## Note: CKEditor and CSP

The editor stores formatting (color, font size, table width) as `style="…"`.
That is why the CSP has the directive `style-src-attr 'unsafe-inline'` (only for style **attributes**; `<style>` elements and scripts remain
strictly forbidden). The risk is limited because `Sanitizer` keeps only allowed CSS properties with strictly validated values
(no `url()`, `expression()`, comments, `\`, etc.).
