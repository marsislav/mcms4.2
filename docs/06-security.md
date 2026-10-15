# 6. Security: what the program does to stay protected

Each protection is explained with the same pattern: **what it guards against → how → where it lives in the code**.

## Sign-in and passwords

| Protection | How it works | Where |
|---|---|---|
| Password hashing | The password is stored "ground up" (bcrypt) and cannot be turned back into the original | `User`, `AuthController` |
| Attempt limiting | 5 attempts per 10 min per email+IP; 20 per IP | `AuthController::login` |
| Lockout | After 8 wrong passwords, locked for 15 min | `users.failed_attempts`, `locked_until` |
| Uniform response time | If the email does not exist, a fake password is still checked | `AuthController` |
| New session ID on sign-in | Prevents "session fixation" | `completeLogin` |
| Automatic sign-out | After 30 min of inactivity (`MCMS_IDLE_MINUTES`) | `AdminGuard` |
| Binding to the browser | A different User-Agent means sign-out | `AdminGuard` |
| Encrypted sessions | Session data is encrypted | `config/session.php` |

## Two-factor authentication (2FA)

- An app (Google Authenticator, etc.) shows a 6-digit code that changes every 30 seconds (the **TOTP** standard).
- A code cannot be used twice (`totp_last_step`).
- 5 wrong attempts, then a block; 10 per IP.
- 8 recovery codes, stored only as hashes.
- It can be made mandatory for administrators: *Settings → Security and maintenance*.
- Code: `app/Support/Totp.php`, `AuthController`, `ProfileController`.
- If you lost your phone: `php artisan mcms:reset-2fa email` (or edit the database by hand).

## Form protection

| Threat | Protection | Where |
|---|---|---|
| **CSRF** (another site submits a form on your behalf) | Hidden secret token `@csrf` | all `<form method="post">` |
| **Spam bots** | Hidden `website` field + encrypted time `ts` (under 3 sec = bot) + limit of 3 per 10 min | `SiteController::contactSend` |
| **Overload** | `throttle:` on search and contact | `routes/web.php` |

## XSS: injecting a foreign script

1. Everything output through `{{ }}` is escaped automatically.
2. HTML from the editor goes through the **`Sanitizer`** when saved: it allows only known tags and attributes;
   blocks `javascript:` and `data:` links, `<script>`, `onclick=` and the like; allows `<iframe>` only for YouTube/Vimeo
   (`config/mcms.php → embed_hosts`); and adds `rel="noopener"` to external links.
3. **CSP** (Content-Security-Policy) tells the browser "run only scripts that carry my secret nonce".
   That is why the project has **no** inline `onclick` or `<style>` without a nonce.

## Security headers (`SecurityHeaders`)

| Header | Meaning |
|---|---|
| `Content-Security-Policy` | Which scripts/styles are allowed |
| `Strict-Transport-Security` | "Always use HTTPS" (only over HTTPS) |
| `X-Content-Type-Options: nosniff` | The browser does not "guess" the file type |
| `X-Frame-Options` | The site cannot be embedded in someone else's `<iframe>` (prevents clickjacking) |
| `Referrer-Policy` | Does not leak internal addresses to other sites |
| `Permissions-Policy` | Disables camera, microphone, geolocation |
| `Cross-Origin-Opener-Policy` | Isolates the window |
| Admin: `no-store`, `noindex` | Admin panel pages are not cached and do not appear in Google |

## File uploads (`Uploader`)

Uploaded files are the most common way to get hacked. That is why:

1. The type is determined from the **content** (`finfo`), not from the name or from what the browser claims.
2. Allowed: JPG, PNG, WebP, GIF, PDF.
3. The name is replaced with a random one (nobody can slip in a `shell.php`).
4. Images are **re-encoded through GD**, which destroys any code hidden in the file.
5. GIFs are checked for `<?` and `<script`; PDFs for a valid header.
6. Size limit (8 MB) and pixel limit (40 million); images are shrunk to 2560 px.
7. `public/uploads/.htaccess` forbids running PHP in that folder.

## Installer

- It needs a one-time **key** `MCMS_INSTALL_KEY` from `.env` (you can see it only if you have access to the file).
- It refuses to run on a database that already contains mCMS tables.
- After installation the key is deleted, and `/install` returns 404.
- Limit: 10 attempts.

## Data and permissions

- **Mass assignment:** `role`, `active` and the 2FA fields are not in `$fillable`, so they cannot be overwritten with a forged request.
- **Roles** (`config/mcms.php`): `admin` can do everything; `editor` handles content, categories, media, menus, messages…; `author` only their own content and media. This is checked in `AdminGuard` (`admin:permission`).
- **The SMTP password** is stored encrypted (`Crypt`).
- **Audit log** (`audit_logs`): who, what, when, from which IP; kept for 180 days.
- **Backups** in `storage/app/backups`: `storage/.htaccess` blocks outside access.
- **Plugins** are installed only over FTP (not through the panel), so a hacked account cannot upload code.
- **Hidden admin path:** instead of `/admin` it is `/panel` (change it with `MCMS_ADMIN_PATH`); `robots.txt` does not mention it.

## What you need to do

1. Set up **HTTPS** (free Let's Encrypt from your hosting).
2. Point the document root at `public/`.
3. Use a strong password and turn on 2FA.
4. Make backups and download them to your own computer.
5. Keep PHP and Laravel up to date (`composer update` from time to time).
6. In `.env`: `APP_DEBUG=false` on a live site!

> To be honest: no system is "unbreakable". This is a good foundation, but it has not been through an independent audit.
