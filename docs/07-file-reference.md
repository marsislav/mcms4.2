# 7. File reference

A short description of every file. Paths are relative to the project root.

## Root
| File | Description |
|---|---|
| `composer.json` | Required packages (Laravel 12, PHP 8.2+) and autoloading of `app/helpers.php` |
| `artisan` | Entry point for `php artisan …` commands |
| `.env.example` | Template for `.env` (secret settings) |
| `.htaccess` | Blocks the internal folders if the document root is the project root |
| `.gitignore` | What must stay out of git (`.env`, `vendor`…) |
| `README.md` | Short getting-started guide |

## bootstrap/ and config/
| File | Description |
|---|---|
| `bootstrap/app.php` | First run (folders, `.env`), routes, middleware |
| `bootstrap/providers.php` | List of boot classes (only `AppServiceProvider`) |
| `config/app.php` | Name, language, time zone, key |
| `config/session.php` | Sessions: encryption, cookie, lifetime |
| `config/mcms.php` | Version, admin path, limits, roles, reserved addresses, allowed video sites |

## routes/
| File | Description |
|---|---|
| `routes/web.php` | All addresses: installer, admin, public site (language routes before the plain ones) |
| `routes/console.php` | Commands `mcms:version`, `mcms:backup`, `mcms:unlock`, `mcms:reset-2fa` |

## app/helpers.php
Functions: `t`, `setting`, `setting_l`, `lroute`, `admin_url`, `upload_url`, `csp_nonce`, `audit`, `excerpt`,
`add_action`, `do_action`, `add_filter`, `apply_filters`, plus `mcms_*` helpers for languages and translations.

## app/Http/Middleware/
| File | Description |
|---|---|
| `CheckInstalled` | Redirects to `/install` if there is no installation; hides `/install` afterwards |
| `SecurityHeaders` | CSP with nonce and other protective headers |
| `SetLocale` | Determines the language from the prefix in the address |
| `CheckMaintenance` | 503 page during maintenance |
| `AdminGuard` | Sign-in, active account, browser, inactivity, permissions, mandatory 2FA |

## app/Http/Controllers/
| File | Description |
|---|---|
| `Controller` | Base class |
| `InstallController` | Web installer |
| `SiteController` | Public site: home, page, post, blog, category, search, contact, sitemap, robots, RSS, 404/redirects |
| `Admin/AuthController` | Sign-in, 2FA challenge, sign-out |
| `Admin/ProfileController` | Profile, password, 2FA setup |
| `Admin/DashboardController` | Dashboard |
| `Admin/ContentController` | Pages and posts (list, form, save, delete, revisions) |
| `Admin/CategoryController` | Categories |
| `Admin/MediaController` | Media library |
| `Admin/MenuController` | Menus |
| `Admin/LanguageController` | Languages |
| `Admin/TranslationController` | Interface translations |
| `Admin/MessageController` | Messages from the contact form |
| `Admin/UserController` | Users |
| `Admin/SettingController` | Settings (logo, mail, security…) |
| `Admin/RedirectController` | Redirects |
| `Admin/AuditController` | Audit log |
| `Admin/BackupController` | Backups |
| `Admin/PluginController` | Enabling/disabling plugins |

## app/Models/
| File | Table |
|---|---|
| `User` | users |
| `Language` | languages |
| `Translation` | translations |
| `Setting` | settings (cached) |
| `Media` | media |
| `Category`, `CategoryTranslation` | categories, category_translations |
| `Content`, `ContentTranslation` | contents, content_translations |
| `Revision` | revisions |
| `Menu`, `MenuItem` | menus, menu_items |
| `Message` | messages |
| `Redirect` | redirects |
| `AuditLog` | audit_logs |

## app/Support/ and app/Providers/
| File | Description |
|---|---|
| `Sanitizer` | Cleans HTML |
| `Totp` | Two-factor codes (RFC 6238) |
| `Uploader` | Safe uploading and processing of files |
| `Backup` | Archives of the database and uploaded files |
| `ContentSaver` | Saves a page/post with translations and revisions |
| `Installer` | Initial data |
| `EnvWriter` | Safely writes to `.env` |
| `Hooks` | Plugin hooks |
| `PluginManager` | Finds and loads plugins |
| `Providers/AppServiceProvider` | SMTP from the settings, plugins, data for the shared layout (languages, menus) |

## database/ and lang/
| File | Description |
|---|---|
| `migrations/2026_01_01_000001_create_core_tables.php` | All tables |
| `seeders/strings.php` | Initial interface translations (bg/en) |
| `lang/en/validation.php`, `lang/bg/validation.php` | Form error messages (English, Bulgarian) |

## resources/views/
| Folder / file | Description |
|---|---|
| `site/layout` | The shared layout: header, menu, footer, languages, SEO headers |
| `site/page`, `post`, `blog`, `search`, `contact` | The site's pages |
| `site/partials/card` | Post card in a list |
| `admin/layout` | The admin panel frame (sidebar) |
| `admin/login`, `challenge` | Sign-in and entering the 2FA code |
| `admin/dashboard`, `profile`, `settings`, `audit`, `backups`, `plugins`, `redirects` | The matching sections |
| `admin/content/index,form` | List and form for pages/posts |
| `admin/categories`, `media`, `menus`, `messages`, `languages`, `translations`, `users` | The matching sections |
| `admin/partials/media-field` | Picking an image from the library |
| `install/index` | Installer |
| `errors/*` | 403, 404, 419, 429, 500, 503 |
| `pagination/mcms` | Pagination |

## public/
| File | Description |
|---|---|
| `index.php` | Front door |
| `.htaccess` | Redirects to `index.php`, hides `.env` and more |
| `assets/css/site.css`, `admin.css`, `errors.css` | Styles |
| `assets/js/site.js`, `admin.js` | Scripts (no inline `onclick`) |
| `uploads/.htaccess` | Forbids PHP in uploaded files |

## Other
| File | Description |
|---|---|
| `plugins/schema-org/` | Example plugin (structured data for search engines) |
| `storage/.htaccess` | Forbids outside access |
| `tests/standalone.php` | Tests for Sanitizer, Totp, EnvWriter (`php tests/standalone.php`) |
| `docs/` | This documentation |
