# mCMS 4.2 Documentation (for beginners)

This folder explains **in plain words** how the program is written, how it works and what depends on what.
You do not need to know PHP beforehand. Wherever a new word appears, it is explained here or in the [glossary](10-glossary.md).

## In what order to read

| # | File | What it covers | When to read it |
|---|------|----------------|-----------------|
| 1 | [01-basic-concepts.md](01-basic-concepts.md) | What a website, server, PHP, Laravel and a database are. Explained with a restaurant example | **First** |
| 2 | [02-folder-structure.md](02-folder-structure.md) | What each folder is for | Second |
| 3 | [03-request-lifecycle.md](03-request-lifecycle.md) | What happens when someone opens a page, step by step | **The most important one** |
| 4 | [04-database.md](04-database.md) | All the tables and how they are connected | After 3 |
| 5 | [05-dependencies.md](05-dependencies.md) | What depends on what, and what breaks if we remove something | After 4 |
| 6 | [06-security.md](06-security.md) | Every protection: what it does and where it is in the code | Optional |
| 7 | [07-file-reference.md](07-file-reference.md) | A description of **every** file | As a reference |
| 8 | [08-how-to-change-things.md](08-how-to-change-things.md) | Ready-made recipes: how to add a field, a section, a language, a plugin… | When you start making changes |
| 9 | [09-installation-and-troubleshooting.md](09-installation-and-troubleshooting.md) | Installation, hosting, common errors | When something does not work |
| 10 | [10-glossary.md](10-glossary.md) | Glossary of terms | Always at hand |

## The shortest explanation

mCMS is a program that lets a person **with no programming knowledge** manage a website:
write pages and articles, upload pictures, build menus and translate into several languages.
All of this is done through an **admin panel** (address `/panel`), while visitors see the
finished **public site**.

The program is written in the **PHP** language with the help of **Laravel** (a ready-made set of tools).
The data (texts, users, settings) is stored in a **MySQL database**.

```
Visitor ──► public site ──┐
                          ├──► program (PHP/Laravel) ──► database (MySQL)
Administrator ──► panel ──┘                         └──► folder with uploaded files
```

## How to read the code itself (recommended path)

1. Open `routes/web.php` — this is the "map" of the site: which address leads where.
2. Find `Route::get('{slug}', [SiteController::class, 'page'])` and open `app/Http/Controllers/SiteController.php`, method `page`.
3. Follow how it finds the page in the database (model `ContentTranslation`) and how it displays it (`resources/views/site/page.blade.php`).
4. Repeat the same for the admin panel: `routes/web.php` → `Admin/ContentController.php` → `resources/views/admin/content/form.blade.php`.
5. When you meet an unfamiliar function such as `t()`, `setting()` or `lroute()`, look it up in `app/helpers.php`.

## An honest note

The code has been checked for syntax errors, and the security helper classes have automated tests
(`php tests/standalone.php`). However, the whole program has **not yet been run on a live server with Laravel**,
so small errors may appear on the first launch. [09](09-installation-and-troubleshooting.md)
describes how to find and fix them.
