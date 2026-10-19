# 7. File reference

A short description of every file. Paths are relative to the project root.

## Root
| File | Description |
|---|---|
| `composer.json` | Required packages (Laravel 12, PHP 8.2+) and autoloading of `app/helpers.php` |
| `artisan` | Entry point for `php artisan …` commands |
| `.env.example` | Template for `.env` (secret settings) |
| `.htaccess` | Blocks internal folders if the document root is the project root |
| `.gitignore` | What stays out of git (`.env`, `vendor`…) |
| `README.md` | Short getting-started guide |

## bootstrap/ and config/
| File | Description |
|---|---|
| `bootstrap/app.php` | First run (folders, `.env`), routes, middleware |
| `bootstrap/providers.php` | List of boot classes (only `AppServiceProvider`) |
| `config/app.php` | Name, language, time zone, key |
| `config/session.php` | Sessions: encryption, cookie, lifetime |
| `config/mcms.php` | Version, admin path, limits, roles, reserved URLs, allowed video sites |

## routes/
| File | Description |
|---|---|
| `routes/web.php` | All URLs: installer, admin, public site (language routes before the plain ones) |
| `routes/console.php` | Commands `mcms:version`, `mcms:backup`, `mcms:unlock` |

## app/helpers.php
Functions: `t`, `setting`, `setting_l`, `lroute`, `admin_url`, `upload_url`, `csp_nonce`, `audit`, `excerpt`,
`add_action`, `do_action`, `add_filter`, `apply_filters`, plus `mcms_*` helpers for languages and translations.

## app/Http/Middleware/
| File | Description |
|---|---|
| `CheckInstalled` | Redirects to `/install` if not installed; hides `/install` afterwards |
| `SecurityHeaders` | CSP with nonce and other protective headers |
| `SetLocale` | Determines the language from the URL prefix |
| `CheckMaintenance` | 503 page during maintenance |
| `AdminGuard` | Login, active account, browser, inactivity, permissions |

## app/Http/Controllers/
| File | Description |
|---|---|
| `Controller` | Base class |
| `InstallController` | Web installer |
| `SiteController` | Public site: home, page, post, blog, category, search, contact, sitemap, robots, RSS, 404/redirects |
| `Admin/AuthController` | Login, logout |
| `Admin/ProfileController` | Profile, password |
| `Admin/DashboardController` | Dashboard |
| `Admin/ContentController` | Pages and posts (list, form, save, delete, revisions) |
| `Admin/CategoryController` | Categories |
| `Admin/MediaController` | Media library |
| `Admin/MenuController` | Menus |
| `Admin/LanguageController` | Languages |
| `Admin/TranslationController` | Interface translations |
| `Admin/MessageController` | Messages from the contact form |
| `Admin/CommentController` | Comment moderation (approve, spam, trash, reply, edit) |
| `CommentController` | Receives a comment from the public form (protections, rules, notifications) |
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
| `Tag`, `TagTranslation` | tags, tag_translations (+ pivots `content_tag`, `content_related`) |
| `Menu`, `MenuItem` | menus, menu_items |
| `Message` | messages |
| `Redirect` | redirects |
| `AuditLog` | audit_logs |

## app/Support/ and app/Providers/
| File | Description |
|---|---|
| `Sanitizer` | Cleans HTML |
| `Uploader` | Secure file upload and processing |
| `Backup` | Archives of the database and uploaded files |
| `ContentSaver` | Saves a page/post with translations and revisions |
| `Installer` | Initial data |
| `EnvWriter` | Safely writes to `.env` |
| `Discussion` | Comments: moderation rules, closing, threads, text formatting |
| `Taxonomy` | Tags and related posts: parsing, saving, the "related" algorithm |
| `Hooks` | Hooks for plugins |
| `PluginManager` | Finds and loads plugins |
| `Providers/AppServiceProvider` | SMTP from settings, plugins, data for the shared template (languages, menus) |

## database/ and lang/
| File | Description |
|---|---|
| `migrations/2026_01_01_000001_create_core_tables.php` | All tables |
| `seeders/strings.php` | Initial interface translations (bg/en) |
| `lang/bg/validation.php` | Bulgarian form error messages |

## resources/views/
| Folder / file | Description |
|---|---|
| `site/layout` | The shared template: header, menu, footer, languages, SEO headers |
| `site/page`, `post`, `blog`, `search`, `contact` | The site's pages |
| `site/partials/card` | Post card in a list |
| `admin/layout` | The panel frame (sidebar menu) |
| `admin/login` | Login |
| `admin/dashboard`, `profile`, `settings`, `audit`, `backups`, `plugins`, `redirects` | The corresponding sections |
| `admin/content/index,form` | List and form for pages/posts |
| `admin/categories`, `media`, `menus`, `messages`, `languages`, `translations`, `users` | The corresponding sections |
| `admin/partials/media-field` | Picking an image from the library |
| `install/index` | Installer |
| `errors/*` | 403, 404, 419, 429, 500, 503 |
| `pagination/mcms` | Pagination |

## public/
| File | Description |
|---|---|
| `index.php` | Front door |
| `.htaccess` | Redirects to `index.php`, hides `.env`, etc. |
| `assets/css/site.css`, `admin.css`, `errors.css` | Styles |
| `assets/js/site.js`, `admin.js` | Scripts (no inline `onclick`) |
| `app/Support/Design.php` | "Design" settings: colors, fonts, animations, top bar → generates `/design.css` |
| `resources/views/admin/partials/design-form.blade.php` | The form of the "Design" tab |
| `assets/js/editor-fa.js` | CKEditor plugin: pick and insert Font Awesome icons |
| `assets/vendor/fontawesome/` | Font Awesome Free 7.3.1 (CSS, woff2, icons.json) |
| `assets/js/editor-grid.js` | CKEditor "Columns" plugin (rows and columns 1–12) |
| `assets/css/grid.css` | Styles and responsive rules for rows/columns |
| `assets/js/editor.js` | CKEditor 5 configuration (plugins, buttons, media library) |
| `assets/vendor/ckeditor5/` | CKEditor 5 v49 (GPL) + Bulgarian translation |
| `uploads/.htaccess` | Forbids PHP in uploaded files |

## Other
| File | Description |
|---|---|
| `plugins/schema-org/` | Example plugin (structured data for search engines) |
| `storage/.htaccess` | Forbids outside access |
| `tests/standalone.php` | Tests for Sanitizer, EnvWriter (`php tests/standalone.php`) |
| `docs/` | This documentation |
- `app/Support/Widgets.php`, `admin/partials/{footer-form,sidebar-form,widget-card,widget-area}.blade.php`, `site/partials/widget-area.blade.php`, `site/widgets/*.blade.php` — widgets (see 08 §23)
- `app/Support/Options.php`, `admin/partials/options/*.blade.php` — Reading/Writing/Date/Links/Media/Scripts/Maintenance (see 08 §24)
- `app/Support/Members.php`, `RegisterController`, `site/register|author.blade.php`, migration `…add_user_profile` — registration and profiles (see 08 §25)
