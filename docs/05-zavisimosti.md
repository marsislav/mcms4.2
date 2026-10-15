# 5. Кое от кое зависи

## Слоеве (отгоре надолу)

```
        Браузър
           │
   ┌───────▼────────┐
   │ routes/web.php │  картата на адресите
   └───────┬────────┘
           │ (минава през Middleware)
   ┌───────▼────────┐
   │  Контролери    │  решават какво да стане
   └───┬────────┬───┘
       │        │
┌──────▼───┐ ┌──▼────────────┐
│  Модели  │ │ app/Support/* │  помощни класове
└──────┬───┘ └──┬────────────┘
       │        │
   ┌───▼────────▼───┐
   │  База данни /  │
   │  файлове       │
   └────────────────┘

Контролерите подават данни на  ──►  Views (resources/views)
Views и всички слоеве ползват  ──►  app/helpers.php (t(), setting(), lroute()…)
```

**Правило:** зависимостите вървят само надолу. Моделите не знаят за контролерите, а
контролерите не знаят как изглеждат шаблоните в детайл. Затова можете да сменяте шаблон
без да пипате логиката.

## Какво е нужно, за да тръгне програмата (най-долу)

1. `vendor/` (Laravel) — без него `public/index.php` спира със съобщение.
2. `.env` — създава се сам от `bootstrap/app.php`.
3. База данни + изпълнени миграции — прави ги инсталаторът.
4. `storage/` трябва да е записваема.

## Общи помощници — ползват се почти навсякъде

| Функция (`app/helpers.php`) | Зависи от | Ползва се в |
|---|---|---|
| `setting()` | модел `Setting` (кеширан) | шаблони, контролери, SecurityHeaders |
| `t()` | таблица `translations`, `mcms_tr()` | всички публични шаблони |
| `lroute()` | маршрутите `site.*` и `site.l.*` | шаблони, SiteController, Menu |
| `admin_url()` | `config('mcms.admin_path')` | админ шаблони, AdminGuard |
| `audit()` | модел `AuditLog` | почти всички админ контролери |
| `csp_nonce()` | `SecurityHeaders` (създава nonce) | шаблони със `<script>`/`<style>` |
| `do_action()` / `apply_filters()` | `Support\Hooks` | шаблони, ContentSaver, AuthController |

## Middleware

| Middleware | Зависи от |
|---|---|
| `CheckInstalled` | файла `storage/installed.lock`, `.env` |
| `SecurityHeaders` | `setting()`, `csp_nonce()` |
| `SetLocale` | таблица `languages` (през `mcms_languages()`) |
| `CheckMaintenance` | настройка `maintenance_mode`, влязъл ли е потребител |
| `AdminGuard` | `User`, `config/mcms.php` (роли), настройка `require_2fa_admin` |

## Контролери

| Контролер | Модели | Support класове |
|---|---|---|
| `SiteController` | Content, ContentTranslation, Category, Message, Redirect, Menu | Hooks, `excerpt()` |
| `InstallController` | — (PDO + Artisan migrate) | Installer, EnvWriter |
| `Admin\AuthController` | User | Totp |
| `Admin\ProfileController` | User | Totp |
| `Admin\ContentController` | Content, Revision, Category, Media | ContentSaver |
| `Admin\CategoryController` | Category, CategoryTranslation | — |
| `Admin\MediaController` | Media | Uploader |
| `Admin\MenuController` | Menu, MenuItem, Content | — |
| `Admin\LanguageController` | Language, Translation | — |
| `Admin\TranslationController` | Translation | — |
| `Admin\MessageController` | Message | — |
| `Admin\UserController` | User | — |
| `Admin\SettingController` | Setting | Uploader, EnvWriter (при нужда), Crypt |
| `Admin\RedirectController` | Redirect | — |
| `Admin\AuditController` | AuditLog | — |
| `Admin\BackupController` | — | Backup |
| `Admin\PluginController` | — | PluginManager |

## Support класове

| Клас | Зависи от |
|---|---|
| `Sanitizer` | само PHP (DOM) — без Laravel; тества се отделно |
| `Totp` | само PHP |
| `EnvWriter` | само PHP |
| `Hooks` | само PHP |
| `Uploader` | GD, finfo, `config('mcms')`, модел Media |
| `Backup` | PDO връзката на Laravel, ZipArchive |
| `ContentSaver` | Sanitizer, Hooks, модели Content/ContentTranslation/Revision, кеша |
| `Installer` | модели, `database/seeders/strings.php` |
| `PluginManager` | папка `plugins/`, Hooks |

## Какво се чупи, ако махна …

| Махам | Последица |
|---|---|
| `app/helpers.php` | **Всичко** спира (шаблоните викат `t()`, `setting()`) |
| `config/mcms.php` | Роли, админ път и лимити липсват → панелът не работи |
| `Sanitizer` | Записът на страници спира (ContentSaver го вика) |
| `plugins/` | Нищо. Плъгините са по желание |
| `tests/` | Нищо. Само самопроверка |
| `docs/` | Нищо |
| `storage/installed.lock` | Програмата мисли, че не е инсталирана → праща към `/install` |
| `.env` | Създава се наново, но с нов `APP_KEY` → старите сесии и криптирани данни (SMTP парола, 2FA) стават нечетливи |
| `database/seeders/strings.php` | Инсталацията няма начални текстове |
| Таблица `translations` | `t()` връща ключовете (напр. `nav.blog`) вместо текст |
| Език `bg` в `languages` | Основният език изчезва — не го трийте |
| Middleware `SecurityHeaders` | Сайтът работи, но без защитните заглавки (**не го правете**) |
| `public/.htaccess` | Всички адреси освен `/` дават 404 |

## Как да проверя „какво ползва този файл?“

1. Отворете файла и вижте реда `use ...;` най-горе — това са класовете, от които зависи.
2. За „кой ползва този клас?“ търсете името му из проекта (в редактора: *Find in files*).
3. За шаблон — вижте `@extends`, `@include` и кой контролер връща `view('име')`.
