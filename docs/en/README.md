# mCMS 4.2 Documentation (for beginners)

This folder explains, **in plain words**, how the program is written, how it works, and what depends on what.
You don't need to know PHP beforehand. Wherever a new term appears, it is explained here or in the [glossary](10-rechnik.md).

## Reading order

| # | File | What it covers | When to read it |
|---|------|----------------|-----------------|
| 1 | [01-osnovni-ponyatiya.md](01-osnovni-ponyatiya.md) | What a website, server, PHP, Laravel and a database are. Explained with a restaurant analogy | **First** |
| 2 | [02-struktura-na-papkite.md](02-struktura-na-papkite.md) | What each folder is for | Second |
| 3 | [03-patyat-na-zayavkata.md](03-patyat-na-zayavkata.md) | What happens when someone opens a page. Step by step | **The most important one** |
| 4 | [04-baza-danni.md](04-baza-danni.md) | All the tables and how they are connected | After 3 |
| 5 | [05-zavisimosti.md](05-zavisimosti.md) | What depends on what, and what breaks if we remove something | After 4 |
| 6 | [06-sigurnost.md](06-sigurnost.md) | Every protection: what it does and where it is in the code | Optional |
| 7 | [07-spravochnik-fajlove.md](07-spravochnik-fajlove.md) | A description of **every** file | As a reference |
| 8 | [08-kak-da-promenyam.md](08-kak-da-promenyam.md) | Ready-made recipes: how to add a field, a section, a language, a plugin… | When you start making changes |
| 9 | [09-instalaciya-i-problemi.md](09-instalaciya-i-problemi.md) | Installation, hosting, common errors | When something won't start |
| 10 | [10-rechnik.md](10-rechnik.md) | Glossary of terms | Always at hand |

## The shortest explanation

mCMS is a program that lets a person **with no programming knowledge** manage a website:
write pages and articles, upload images, build menus and translate into several languages.
All of this is done through an **admin panel** (address `/panel`), while visitors see the
finished **public site**.

The program is written in **PHP** using **Laravel** (a ready-made set of tools).
The data (texts, users, settings) is stored in a **MySQL database**.

```
Visitor ──► public site ──┐
                          ├──► application (PHP/Laravel) ──► database (MySQL)
Administrator ──► panel ──┘                         └──► folder with uploaded files
```

## How to read the code itself (recommended path)

1. Open `routes/web.php` — this is the "map" of the site: which address leads where.
2. Find `Route::get('{slug}', [SiteController::class, 'page'])` and open `app/Http/Controllers/SiteController.php`, method `page`.
3. Follow how it finds the page in the database (model `ContentTranslation`) and how it displays it (`resources/views/site/page.blade.php`).
4. Repeat the same for the admin panel: `routes/web.php` → `Admin/ContentController.php` → `resources/views/admin/content/form.blade.php`.
5. When you come across an unfamiliar function such as `t()`, `setting()` or `lroute()`, look it up in `app/helpers.php`.

## An honest note

The code has been checked for syntax errors, and the security helper classes have automated tests
(`php tests/standalone.php`). However, the whole application has **not yet been run on a live Laravel server**,
so minor errors may appear on first launch. [09](09-instalaciya-i-problemi.md)
describes how to find and fix them.
