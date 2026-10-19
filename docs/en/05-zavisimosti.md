# 5. What Depends on What

## Layers (top to bottom)

```
        Browser
           │
   ┌───────▼────────┐
   │ routes/web.php │  the address map
   └───────┬────────┘
           │ (passes through Middleware)
   ┌───────▼────────┐
   │  Controllers   │  decide what should happen
   └───┬────────┬───┘
       │        │
┌──────▼───┐ ┌──▼────────────┐
│  Models  │ │ app/Support/* │  helper classes
└──────┬───┘ └──┬────────────┘
       │        │
   ┌───▼────────▼───┐
   │  Database /    │
   │  files         │
   └────────────────┘

Controllers pass data to  ──►  Views (resources/views)
Views and all layers use  ──►  app/helpers.php (t(), setting(), lroute()…)
```

**Rule:** dependencies only go downward. Models don't know about controllers, and
controllers don't know in detail what the templates look like. That is why you can swap a template
without touching the logic.

## What the application needs in order to start (the very bottom)

1. `vendor/` (Laravel) — without it `public/index.php` stops with a message.
2. `.env` — created automatically by `bootstrap/app.php`.
3. A database + executed migrations — done by the installer.
4. `storage/` must be writable.

## Common helpers — used almost everywhere

| Function (`app/helpers.php`) | Depends on | Used in |
|---|---|---|
| `setting()` | model `Setting` (cached) | templates, controllers, SecurityHeaders |
| `t()` | table `translations`, `mcms_tr()` | all public templates |
| `lroute()` | the routes `site.*` and `site.l.*` | templates, SiteController, Menu |
| `admin_url()` | `config('mcms.admin_path')` | admin templates, AdminGuard |
| `audit()` | model `AuditLog` | almost all admin controllers |
| `csp_nonce()` | `SecurityHeaders` (creates the nonce) | templates with `<script>`/`<style>` |
| `do_action()` / `apply_filters()` | `Support\Hooks` | templates, ContentSaver, AuthController |

## Middleware

| Middleware | Depends on |
|---|---|
| `CheckInstalled` | the file `storage/installed.lock`, `.env` |
| `SecurityHeaders` | `setting()`, `csp_nonce()` |
| `SetLocale` | table `languages` (via `mcms_languages()`) |
| `CheckMaintenance` | setting `maintenance_mode`, whether a user is logged in |
| `AdminGuard` | `User`, `config/mcms.php` (roles) |

## Controllers

| Controller | Models | Support classes |
|---|---|---|
| `SiteController` | Content, ContentTranslation, Category, Message, Redirect, Menu | Hooks, `excerpt()` |
| `InstallController` | — (PDO + Artisan migrate) | Installer, EnvWriter |
| `Admin\AuthController` | User | — |
| `Admin\ProfileController` | User | — |
| `Admin\ContentController` | Content, Revision, Category, Media | ContentSaver |
| `Admin\CategoryController` | Category, CategoryTranslation | — |
| `Admin\MediaController` | Media | Uploader |
| `Admin\MenuController` | Menu, MenuItem, Content | — |
| `Admin\LanguageController` | Language, Translation | — |
| `Admin\TranslationController` | Translation | — |
| `Admin\MessageController` | Message | — |
| `Admin\UserController` | User | — |
| `Admin\SettingController` | Setting | Uploader, EnvWriter (if needed), Crypt |
| `Admin\RedirectController` | Redirect | — |
| `Admin\AuditController` | AuditLog | — |
| `Admin\BackupController` | — | Backup |
| `Admin\PluginController` | — | PluginManager |

## Support classes

| Class | Depends on |
|---|---|
| `Sanitizer` | PHP only (DOM) — no Laravel; tested separately |
| `Design` | PHP only (+ `setting()`); generates the CSS for `/design.css` |
| `EnvWriter` | PHP only |
| `Hooks` | PHP only |
| `Uploader` | GD, finfo, `config('mcms')`, model Media |
| `Backup` | Laravel's PDO connection, ZipArchive |
| `ContentSaver` | Sanitizer, Hooks, models Content/ContentTranslation/Revision, the cache |
| `Installer` | models, `database/seeders/strings.php` |
| `PluginManager` | the `plugins/` folder, Hooks |

## What breaks if I remove …

| I remove | Consequence |
|---|---|
| `app/helpers.php` | **Everything** stops (templates call `t()`, `setting()`) |
| `config/mcms.php` | Roles, admin path and limits are missing → the panel doesn't work |
| `Sanitizer` | Saving pages stops (ContentSaver calls it) |
| `plugins/` | Nothing. Plugins are optional |
| `tests/` | Nothing. It's only a self-check |
| `docs/` | Nothing |
| `storage/installed.lock` | The application thinks it isn't installed → redirects to `/install` |
| `.env` | It is recreated, but with a new `APP_KEY` → old sessions and encrypted data (SMTP password) become unreadable |
| `database/seeders/strings.php` | The installation has no initial texts |
| Table `translations` | `t()` returns the keys (e.g. `nav.blog`) instead of text |
| Language `bg` in `languages` | The default language disappears — don't delete it |
| Middleware `SecurityHeaders` | The site works, but without the security headers (**don't do this**) |
| `public/.htaccess` | All addresses except `/` return 404 |

## How do I check "what does this file use?"

1. Open the file and look at the `use ...;` lines at the top — these are the classes it depends on.
2. For "who uses this class?", search for its name across the project (in the editor: *Find in files*).
3. For a template — look at `@extends`, `@include`, and which controller returns `view('name')`.
