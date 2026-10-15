# 4. Database

The schema is described in a single file: `database/migrations/2026_01_01_000001_create_core_tables.php`.
The models are in `app/Models/` (one per table).

## The big picture

```
users ───────────────────────────────┐ (author of content, uploader of media, action in the audit log)
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
redirects        (redirects)
audit_logs       (audit log of actions)
```

The `──<` sign means "one to many": one category has many translations; one piece of content has many translations.

## How multilingual support is designed (the most important idea)

Instead of keeping the title in the page's table, we keep it in a separate table with **one row per language**:

**contents** (shared by the whole page)

| id | type | status | publish_at | is_home |
|----|------|--------|------------|---------|
| 7 | page | published | 2026-10-01 | 0 |

**content_translations** (specific to the language)

| id | content_id | locale | title | slug | body |
|----|-----------|--------|-------|------|------|
| 11 | 7 | en | About us | about-us | `<p>…</p>` |
| 12 | 7 | bg | (the Bulgarian title) | za-nas | `<p>…</p>` |

The address of the English (default) version is `/about-us`, and the Bulgarian one is `/bg/za-nas`. This way every language has **its own** address (good for search engines).
The same applies to categories (`categories` + `category_translations`).

## The tables one by one

### `users` — panel users
| Column | What it is for |
|--------|----------------|
| `name`, `email` | Name and email (the email is unique) |
| `password` | Password as a **hash** (irreversibly "ground up"; nobody can read it) |
| `role` | `admin`, `editor` or `author` |
| `active` | 0 = deactivated |
| `totp_secret`, `totp_enabled`, `totp_last_step`, `recovery_codes` | Two-factor authentication (the secret and the codes are encrypted) |
| `failed_attempts`, `locked_until` | Wrong-password counter and lock time |
| `last_login_at`, `last_login_ip` | Last sign-in |

### `languages`
`code` (`bg`, `en`), `name`, `native_name`, `is_default` (the default one), `is_active`, `sort`.

### `translations` — the interface texts
`key` (for example `nav.blog`), `locale`, `value`. The combination `key + locale` is unique.
This way `t('nav.blog')` returns "Blog" in English and the Bulgarian equivalent in Bulgarian.

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
| `category_id` | For posts only |
| `featured_media_id` | Main picture (points to `media`) |
| `is_home` | Whether it is the home page (only one) |
| `noindex` | Hide from Google |
| `author_id` | Who created it |

### `revisions` — old versions
Before we overwrite a title/text, the old one is copied here. The last 20 per translation are kept.
Linked to `content_translations`.

### `categories` / `category_translations`
The categories of posts; name, address and description per language.

### `media`
Uploaded files: `path`, `thumb_path` (reduced picture), `original_name`, `mime`, `size`, `width`, `height`, `alt`.
The files themselves are in `public/uploads/YEAR/MONTH/`.

### `menus` and `menu_items`
`menus`: `location` (`header`, `footer`). `menu_items`: a menu item.

| Column | Meaning |
|--------|---------|
| `type` | `content` (to a page), `builtin` (built-in: home, blog, contact, search), `custom` (free link) |
| `content_id` | For type `content` |
| `url` | For `custom` — the address; for `builtin` — the name |
| `labels` | JSON with text per language: `{"en":"Home","bg":"Nachalo"}` |
| `parent_id` | For a dropdown submenu (max. 2 levels) |
| `sort`, `new_tab` | Ordering; open in a new window |

### `messages`
Messages from the contact form: `name`, `email`, `subject`, `body`, `ip`, `locale`, `read_at`.

### `redirects`
`from_path` → `to_url` with code 301/302 and a `hits` counter. They apply only when the address would otherwise give a 404.

### `audit_logs`
Who (`user_id`), what (`action`), on what (`subject`), details, from which IP and with which browser, and when.
Records are cleaned up after 180 days.

## What happens on deletion (cascades)

| You delete | What is automatically deleted / changed |
|------------|-----------------------------------------|
| Content | Its translations, their revisions, the menu items that point to it |
| Category | Its translations; the posts are left without a category |
| Media file | Posts lose their main picture (it is left empty) |
| User | The content stays, but without an author |
| Language | The interface translations and the content in that language (**careful**) |

These are called **foreign keys** (`foreignId(...)->constrained()->cascadeOnDelete()` in the migration).

## How to work with the database from code (three examples)

```php
// 1) Read a setting
setting('site_name');                      // "My site"

// 2) Find published posts, newest first
Content::visible()->where('type', 'post')->orderByDesc('publish_at')->get();

// 3) Save something
Message::create(['name' => 'Ana', 'email' => 'ana@example.com', 'body' => 'Hello']);
```

`visible()` is a **scope** — our own short query in `Content`: "status published and date ≤ now".
This way we do not repeat that logic in many places.
