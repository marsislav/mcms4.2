# 9. Installation, hosting and common problems

## Requirements

- PHP **8.2 or newer**, with the extensions: `gd`, `fileinfo`, `mbstring`, `pdo_mysql`, `dom`, `zip`.
- MySQL 5.7+ / MariaDB 10.3+.
- Apache with `mod_rewrite` (an `.htaccess` is included).
- Composer, on **your own computer** (it is not needed on the hosting).

## Step 1: Preparation on your computer

In the project folder:

```
composer install --no-dev -o
```

This creates `vendor/` (Laravel). That folder **must be uploaded** to the hosting if it does not have Composer.

### Error: `ext-gd ... is missing` (Windows / XAMPP)

1. Open `C:\xampp\php\php.ini`.
2. Find `;extension=gd` and remove the semicolon at the start → `extension=gd`.
3. Repeat for `zip`, `fileinfo`, `mbstring` if they are commented out.
4. Restart XAMPP and close/reopen the terminal.
5. Check: `php -m`; `gd` must be in the list.

### Error: `laravel/framework ^11.0 ... security advisories`

All Laravel 11 versions are blocked by Composer. The project uses **Laravel 12**:
`composer.json` must say `"laravel/framework": "^12.0"`. If it says `^11.0`,
change it (or unpack the latest ZIP), delete `composer.lock` and `vendor/`, and run it again.

## Step 2: Uploading

1. Create an empty database from the hosting panel (note the name, user and password).
2. Upload everything over FTP (including `vendor/` and the hidden `.htaccess` files).
3. **Best:** point the domain's document root at the `public/` folder.
   If you cannot, the root `.htaccess` protects the internal folders, but it is weaker protection.
4. Give write permission to `storage/`, `bootstrap/cache/`, `public/uploads/` (usually 755; if that does not work, 775).

## Step 3: Installer

1. Open `https://your-site.com/install`.
2. It will ask for the **installation key**: open `.env` (created when the site is first opened)
   and copy the value of `MCMS_INSTALL_KEY`.
3. Enter the database details, the site name, and the administrator's email and password.
4. The installer creates the tables and initial data, writes `storage/installed.lock`, and deletes the key.
5. Sign in to the panel: `https://your-site.com/panel`.

Locally (XAMPP) you can also start it like this: `php artisan serve` → `http://127.0.0.1:8000/install`.

## After installation (mandatory)

- [ ] `.env`: `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL=https://…`
- [ ] Turn on HTTPS
- [ ] Turn on 2FA on your profile
- [ ] Settings → Outgoing mail (SMTP), so the contact form works by email
- [ ] Make a first backup

## Common problems

| Symptom | Cause | Solution |
|---|---|---|
| "vendor/ is missing" | The folder was not uploaded | `composer install --no-dev -o` and upload `vendor/` |
| White page / 500 | A PHP error | `APP_DEBUG=true`, check `storage/logs/laravel.log` |
| 404 everywhere except the home page | `mod_rewrite`/`.htaccess` is not working | Check that `.htaccess` was uploaded and `AllowOverride All` is set |
| Cannot write to `storage` | Permissions | chmod 775 on `storage` and `bootstrap/cache` |
| `/install` returns 404 | Already installed | Normal. To reinstall: delete `storage/installed.lock` **and** the tables (this deletes the data!) |
| Forgotten administrator password | n/a | `php artisan tinker` or phpMyAdmin: change the hash; easier locally: `php artisan mcms:unlock email` unlocks the account |
| Locked account | 8 wrong attempts | Wait 15 min or run `php artisan mcms:unlock email` |
| Lost phone for 2FA | n/a | `php artisan mcms:reset-2fa email` |
| Images will not upload | PHP limit or `gd` is missing | `upload_max_filesize`, `post_max_size`; enable `gd` |
| The contact form does not send email | SMTP is not set up | Settings → Outgoing mail (SMTP); the messages are in "Messages" anyway |
| The site shows "Maintenance" | Maintenance mode is on | Settings → Security and maintenance → turn it off; signed-in administrators see the site normally |

## Commands (Artisan)

Run them in the project folder (terminal access is required, usually on your own computer):

| Command | Does |
|---|---|
| `php artisan mcms:version` | Shows the version |
| `php artisan mcms:backup` | Makes a database backup (`.sql.gz`); the archive of files is made from the panel |
| `php artisan mcms:unlock email` | Unlocks an account |
| `php artisan mcms:reset-2fa email` | Turns off 2FA on an account |
| `php artisan migrate` | Runs new migrations |
| `php artisan serve` | Local development server |
| `php tests/standalone.php` | Self-check of the security classes |

## An honest note

The program was written without the ability to run it on a live Laravel in the environment where it was created.
The syntax has been checked and the security classes tested, but **routes, migrations, the installer and uploads have not been tried in practice**.
Install on a **test database** first. If an error appears, copy the line from `storage/logs/laravel.log`
(file + line number) and it will be fixed quickly.
