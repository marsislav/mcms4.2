# 4. Database

The schema is defined in a single file: `database/migrations/2026_01_01_000001_create_core_tables.php`.
The models are in `app/Models/` (one per table).

## The big picture

```
users ───────────────────────────────┐ (content author, media uploader, log action)
                                     │
categories ──< category_translations │
    │                                │
    └──< contents >──< content_translations >──< revisions
            │  │
            │  └── featured_media_id ──► media
            └── (menu_items.content_id points to contents)

menus ──< menu_items

languages        (which languages exist)
translations     (interface translations: key + language → text)
settings         (settings: key → value)
messages         (messages from the contact form)
comments         (comments under posts and pages)
redirects        (redirects)
audit_logs       (log of actions)
```

The `──<` sign means "one to many": one category has many translations; one content item has many translations.

## How multilingual support works (the most important idea)

Instead of storing the title in the page's table, we store it in a separate table, **one row per language**:

**contents** (shared by the page)

| id | type | status | publish_at | is_home |
|----|------|--------|------------|---------|
| 7 | page | published | 2026-10-01 | 0 |

**content_translations** (language-specific)

| id | content_id | locale | title | slug | body |
|----|-----------|--------|-------|------|------|
| 11 | 7 | bg | За нас | za-nas | `<p>…</p>` |
| 12 | 7 | en | About us | about-us | `<p>…</p>` |

The address of the English version is `/en/about-us`, and of the Bulgarian one — `/za-nas`. This way each language has **its own** address (good for search engines).
The same applies to categories (`categories` + `category_translations`).

## The tables one by one

### `users` — panel users
| Column | What it is for |
|--------|----------------|
| `name`, `email` | Name and email (the email is unique) |
| `password` | Password as a **hash** (irreversibly "ground up"; nobody can read it) |
| `role` | `admin`, `editor` or `author` |
| `active` | 0 = deactivated |
| `failed_attempts`, `locked_until` | Counter of wrong passwords and lockout time |
| `last_login_at`, `last_login_ip` | Last login |

### `languages`
`code` (`bg`, `en`), `name`, `native_name`, `is_default` (the default one), `is_active`, `sort`.

### `translations` — interface texts
`key` (for example `nav.blog`), `locale`, `value`. The `key + locale` combination is unique.
This way `t('nav.blog')` returns "Блог" in Bulgarian and "Blog" in English.

### `settings`
The simplest table: `key` → `value`. Examples: `site_name`, `contact_email`, `maintenance_mode`,
`tagline.bg`, `tagline.en` (the dot and the language in the key make the setting language-specific).

### `contents` and `content_translations`
Described above. Additional columns in `contents`:

| Column | Meaning |
|--------|---------|
| `type` | `page` or `post` |
| `status` | `draft` or `published` |
| `publish_at` | Since when it is visible. A future date = scheduled |
| `category_id` | Posts only |
| `featured_media_id` | Main image (points to `media`) |
| `is_home` | Whether it is the home page (only one) |
| `noindex` | Hide from Google |
| `author_id` | Who created it |

### `revisions` — old versions
Before we overwrite a title/text, the old one is copied here. The last 20 per translation are kept.
Linked to `content_translations`.

### `categories` / `category_translations`
Post categories; name, address and description per language.

### `media`
Uploaded files: `path`, `thumb_path` (thumbnail), `original_name`, `mime`, `size`, `width`, `height`, `alt`.
The files themselves are in `public/uploads/YEAR/MONTH/`.

### `menus` and `menu_items`
`menus`: `location` (`header`, `footer`). `menu_items`: a menu item.

| Column | Meaning |
|--------|---------|
| `type` | `content` (points to a page), `builtin` (built-in: home, blog, contact, search), `custom` (free-form link) |
| `content_id` | For type `content` |
| `url` | For `custom` — the address; for `builtin` — the name |
| `labels` | JSON with text per language: `{"bg":"Начало","en":"Home"}` |
| `parent_id` | For a dropdown submenu (max. 2 levels) |
| `sort`, `new_tab` | Ordering; open in a new window |

### `messages`
Messages from the contact form: `name`, `email`, `subject`, `body`, `ip`, `locale`, `read_at`.

### `comments`
Comments: `content_id` (which page/post), `parent_id` (reply to another comment), `author_name`, `author_email`, `author_url`, `author_ip`, `user_agent`, `body` (plain text), `status` (`pending` awaiting moderation, `approved`, `spam`, `trash`), `user_id` (when it was written by a team member). The `contents.comments_open` column is a per-page override: `NULL` = the default from Settings, `1` = open, `0` = closed.

### `redirects`
`from_path` → `to_url` with a 301/302 code and a `hits` counter. They are applied only when the address would otherwise result in a 404.

### `audit_logs`
Who (`user_id`), what (`action`), on what (`subject`), details, from which IP and with which browser, and when.
Entries are purged after 180 days.

## What happens on deletion (cascades)

| You delete | What is automatically deleted / changed |
|------------|-----------------------------------------|
| Content | Its translations, their revisions, and the menu items that point to it |
| Category | Its translations; posts are left without a category |
| Media file | Posts lose their featured image (left empty) |
| User | Content remains, but without an author |
| Language | The interface translations and content in that language (**warning**) |

These are called **foreign keys** (`foreignId(...)->constrained()->cascadeOnDelete()` in the migration).

## How to work with the database from code (three examples)

```php
// 1) Read a setting
setting('site_name');                      // "My Site"

// 2) Find published posts, newest first
Content::visible()->where('type', 'post')->orderByDesc('publish_at')->get();

// 3) Save something
Message::create(['name' => 'Ана', 'email' => 'ana@primer.bg', 'body' => 'Здравейте']);
```

`visible()` is a **scope** — our own short query in `Content`: "status published and date ≤ now".
This way we don't repeat that logic in many places.
