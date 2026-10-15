# 2. Folder structure

```
mcms42/
├── app/                  ← EVERYTHING we wrote ourselves (the logic)
│   ├── Http/
│   │   ├── Controllers/  ← the "cooks": decide what should happen
│   │   │   ├── Admin/    ← controllers of the admin panel
│   │   │   ├── SiteController.php     ← the public site
│   │   │   └── InstallController.php  ← the installer
│   │   └── Middleware/   ← the "security": checks before every request
│   ├── Models/           ← the "pantry": one class for each table
│   ├── Providers/        ← application startup code
│   ├── Support/          ← helper classes (security, uploads, backups, plugins…)
│   └── helpers.php       ← short functions used everywhere: t(), setting(), lroute()…
│
├── bootstrap/            ← "starting the engine"
│   ├── app.php           ← configures the application (middleware, routes, first run)
│   └── providers.php     ← list of the startup classes
│
├── config/               ← settings (as code)
│   ├── app.php           ← name, language, time zone
│   ├── mcms.php          ← OUR settings: roles, admin path, limits
│   └── session.php       ← session settings
│
├── database/
│   ├── migrations/       ← description of the tables
│   └── seeders/strings.php  ← initial interface texts (bg/en)
│
├── lang/{en,bg}/         ← form validation error messages (English, Bulgarian)
│
├── plugins/              ← plugins (one subfolder each)
│
├── public/               ← the ONLY folder reachable from the internet
│   ├── index.php         ← the front door of the whole program
│   ├── .htaccess         ← Apache server rules
│   ├── assets/css,js/    ← styles and scripts
│   └── uploads/          ← uploaded pictures and PDFs
│
├── resources/views/      ← the "plates": HTML templates (Blade)
│   ├── admin/            ← the panel
│   ├── site/             ← the public site
│   ├── install/          ← the installer
│   ├── errors/           ← error pages (404 etc.)
│   └── pagination/       ← the "Previous / Next" buttons
│
├── routes/
│   ├── web.php           ← the MAP of all addresses
│   └── console.php       ← terminal commands (php artisan mcms:…)
│
├── storage/              ← temporary and internal files
│   ├── app/backups/      ← the backups
│   ├── framework/        ← sessions, cache, compiled templates
│   ├── logs/             ← error logs
│   └── installed.lock    ← a file showing that the installation has finished
│
├── tests/standalone.php  ← self-check of the security classes
├── vendor/               ← Laravel and its packages (created by composer, do NOT touch)
├── artisan               ← Laravel's command-line tool
├── composer.json         ← list of the required packages
├── .env                  ← secret server settings (created automatically)
├── .env.example          ← template for .env
└── docs/                 ← this documentation
```

## What may and may not be touched

| Folder / file | May it be changed? |
|---------------|--------------------|
| `app/`, `resources/views/`, `routes/`, `public/assets/` | **Yes** — this is where your work happens |
| `config/mcms.php`, `.env` | Yes, carefully |
| `plugins/` | Yes — this is where you add features without touching the core |
| `database/migrations/` | Do not change the old ones; add **new** files |
| `vendor/` | **Never** — Laravel is inside |
| `storage/`, `bootstrap/cache/` | Not by hand; the program manages them itself |
| `public/uploads/` | Not by hand; upload through the panel |

## Why is only `public/` reachable from the internet?

If the server exposes the whole project folder, someone can open `https://example.com/.env`
and read your passwords. That is why the **document root** (the folder the server exposes) is pointed
at `public/`, while everything else stays "under lock". If the hosting does not allow this, the root
`.htaccess` blocks the internal folders (weaker protection).

## What is `app/Support`?

This is our "toolbox". Each class does one job:

| Class | Job |
|-------|-----|
| `Sanitizer` | Cleans dangerous code out of HTML |
| `Totp` | Generates and verifies codes for two-factor authentication |
| `Uploader` | Safely accepts and processes uploaded files |
| `Backup` | Makes backups of the database and the files |
| `ContentSaver` | Saves a page/post with all its translations and revisions |
| `Installer` | Fills the database with initial data during installation |
| `EnvWriter` | Safely changes `.env` |
| `Hooks` | The system of "hooks" (plugins) |
| `PluginManager` | Finds and loads the plugins |
