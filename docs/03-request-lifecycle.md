# 3. The lifecycle of a request

This is the most important document. If you understand it, you understand how the whole program works.
We will follow **four** cases:

1. A visitor opens the page `https://example.com/about-us`
2. An administrator signs in to the panel
3. An administrator saves a new page
4. A visitor submits the contact form

---

## Case 1: A visitor opens `/about-us`

```
Browser
  │  GET /about-us
  ▼
[1] Apache + public/.htaccess      ← "no such file → hand it to index.php"
  ▼
[2] public/index.php               ← the front door
  ▼
[3] bootstrap/app.php              ← prepares the application
  ▼
[4] Middleware (the security)      ← checks, one by one
  ▼
[5] routes/web.php                 ← "which address leads where?"
  ▼
[6] SiteController::page()         ← the cook
  ▼
[7] Models + database              ← the pantry
  ▼
[8] resources/views/site/page.blade.php + layout   ← the plate
  ▼
The browser receives the HTML and draws it
```

### [1] Apache and `.htaccess`

The Apache server receives the request. The file `public/.htaccess` says:
"if they ask for a real file (an image, CSS) — serve it; otherwise pass the request to `index.php`".
That is why the address `/about-us` is not a real folder — it is just a "question" to the program.

### [2] `public/index.php`

A very short file. It checks that `vendor/` exists, loads Laravel and says:
"handle this request" (`handleRequest`).

### [3] `bootstrap/app.php`

Several things happen here:

1. **On first run** it creates the missing folders (`storage/…`, `public/uploads`) and copies `.env.example` → `.env`,
   generating a random `APP_KEY` and a random `MCMS_INSTALL_KEY`.
2. It declares **which file is the map of addresses** (`routes/web.php`).
3. It registers our **middleware** (the security).

### [4] Middleware — the security

The request passes through several "checkpoints". Each one can let it through, change it or stop it.
Ours:

| Middleware | File | What it does |
|------------|------|--------------|
| `CheckInstalled` | `app/Http/Middleware/CheckInstalled.php` | If the program is not installed yet → redirects to `/install`. If it is installed → hides `/install` (404) |
| *(Laravel)* | — | Starts the session, checks the CSRF code, encrypts cookies |
| `SecurityHeaders` | `SecurityHeaders.php` | Once the response is ready, adds security headers (CSP, HSTS, etc.) |
| `SetLocale` | `SetLocale.php` | Determines which language the page is in (`/bg/…` → Bulgarian) |
| `CheckMaintenance` | `CheckMaintenance.php` | If the site is in maintenance mode → shows a 503 page |

### [5] `routes/web.php` — the map

The program goes through the list and finds a match. For `/about-us` this one matches:

```php
Route::get('{slug}', [SiteController::class, 'page'])->name('page');
```

`{slug}` is a "blank space" — here `slug = "about-us"`. It says: "call the `page` method of `SiteController`".

> **Important about the order:** the routes with `/{locale}/…` (for non-default languages) are declared **before** the ordinary ones,
> otherwise `/bg` would be taken for a page named "bg". That is why pages with a 2-letter name are not allowed.

### [6] `SiteController::page()`

```php
$tr = $this->findTranslation($slug, 'page');   // 1) look it up in the database
if (! $tr) return $this->notFound($request);   // 2) not there → 404 (or a redirect)
...
return $this->renderContent($tr->content, $tr, 'site.page');  // 3) prepare and display
```

What `findTranslation` does: it looks for a row in the `content_translations` table where
`locale` = the current language and `slug` = `about-us`, and the related content is **published**
and (if scheduled) its time has come. A visitor who is signed in to the panel also sees drafts (preview).

### [7] Models and database

`ContentTranslation` is the model for the `content_translations` table. It is linked to `Content`
(the shared data: status, date, picture) — see [04-database.md](04-database.md).

Why two tables? So that **one page can have many languages**:
`contents` holds "the page as a whole", and `content_translations` holds one row per language
(title, address, text).

### [8] The view

`renderContent` gathers:

- the **content** (title, text, picture);
- **SEO data** (`title`, `description`, `canonical`, addresses of the other languages for `hreflang`);
- the **language switcher**.

Then it runs `resources/views/site/page.blade.php`, which says `@extends('site.layout')` — that is,
"put me inside the shared template". Before the shared template is drawn, `AppServiceProvider`
(through a *view composer*) hands it the menu for the top and bottom parts (`Menu::tree('header')`).

Inside the templates, helper functions from `app/helpers.php` are used:

- `t('nav.blog')` — a translation from the database;
- `setting('site_name')` — a setting;
- `lroute('home')` — an address with the correct language prefix;
- `do_action('head')` — a place where a plugin can add code.

### Back to the browser

The finished HTML travels back through `SecurityHeaders`, which adds the security headers to it,
and reaches the browser.

---

## Case 2: An administrator signs in to the panel

1. They open `/panel/login` → `Admin\AuthController::showLogin` shows the form (`admin/login.blade.php`).
2. They submit the form (POST) → `AuthController::login`:
   1. Validates the fields.
   2. Checks the **attempt limit** (`RateLimiter`): no more than 5 per 10 minutes for the email+IP pair.
   3. Looks up the user. Checks the password with `password_verify`. If the user does not exist,
      it still checks against a "fake" password so that it takes the same time (this way nobody can tell which emails exist).
   4. If it is wrong — it counts an attempt; after 8 failures the account is locked for 15 minutes.
   5. If **2FA** is enabled → it stores "waiting for code" in the session and redirects to `/panel/2fa`.
      Otherwise they are signed in directly.
3. `completeLogin` signs them in (`Auth::login`), **changes the session identifier** (against "session hijacking"),
   remembers the browser and the time, and writes to the audit log (`audit('login')`).
4. From this moment every request to `/panel/...` passes through **`AdminGuard`**:
   - Are you signed in? Is the account active?
   - Is it the same browser (User-Agent)?
   - Has more than 30 minutes passed without activity?
   - Do you have permission for this section (role → permission)?
   - If the policy requires 2FA for administrators — do you have it?

   If anything is wrong → sign out and return to the sign-in page.

---

## Case 3: An administrator saves a new page

```
Form (admin/content/form.blade.php)
   │  POST /panel/pages
   ▼
Admin\ContentController::store
   │  1) validate(): check the fields
   ▼
ContentSaver::save           ← this is the "brain"
   │  2) for each language: the title is required only for the default one
   │  3) makes a slug (address) from the title: "About us" → "about-us" (and checks that it is free)
   │  4) Sanitizer::clean(text)  ← removes dangerous HTML
   │  5) writes contents + content_translations (in a transaction: all or nothing)
   │  6) on change keeps the old version in revisions (up to 20)
   │  7) clears the sitemap cache, calls do_action('content.saved')
   ▼
audit('page.create')          ← writes to the audit log
   ▼
Redirect to the edit page + a "Saved" message
```

A **transaction** = "all or nothing". If something blows up at step 4, the earlier writes are rolled back,
so that half a page is not left in the database.

---

## Case 4: A visitor submits the contact form

1. `GET /contact` → `SiteController::contactForm` shows the form. It contains:
   - a hidden field `ts` — the **encrypted time** at which the form was shown;
   - a hidden field `website` — a **trap for bots** (a human does not see it and does not fill it in).
2. `POST /contact` → `contactSend`:
   1. Limit: 3 submissions per 10 minutes from one IP.
   2. If `website` is filled in → it is a bot. We show "success" but **save nothing**.
   3. If the form was submitted in less than 3 seconds → bot → rejected.
   4. Validation (email, message length).
   5. It is saved in the `messages` table (always, even if mail does not work).
   6. It tries to send an email; a sending error is written to the log but not shown to the visitor.
   7. `do_action('contact.received')` — plugins can react.

---

## In short

```
address ─► routes/web.php ─► Controller ─► (Models ↔ Database) ─► View ─► HTML
            ▲                   ▲
        Middleware         Support classes
        (security)         (Sanitizer, Uploader…)
```
