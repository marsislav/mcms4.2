# 1. Basic concepts (for people who are just starting)

## An analogy: a website is a restaurant

| In the restaurant | In the program | What it is called in technical terms |
|-------------------|----------------|--------------------------------------|
| The customer orders a dish | The visitor types an address in the browser | **Request** |
| The waiter decides which kitchen to take the order to | The program looks at the address and chooses what to run | **Route** |
| The cook prepares the dish | Code that gathers the needed information | **Controller** |
| The pantry and the fridge | The data, arranged in tables | **Database** and **models** |
| The plate with the nicely arranged food | The HTML page that you see | **View / template** (Blade) |
| The security at the door who checks whether you may come in | Code that checks every request before it reaches the cook | **Middleware** |
| The restaurant's recipe | Settings such as the database password | The **.env** file and the **config** folder |

The whole path "customer → waiter → cook → pantry → plate → customer" is described in detail in
[03-request-lifecycle.md](03-request-lifecycle.md).

## What a website is and how the internet works (in 5 sentences)

1. The **browser** (Chrome, Firefox…) is the program you use to view websites.
2. The **server** is a computer that is always on and "holds" the website.
3. When you type `https://example.com/about-us`, the browser sends a **request** to the server.
4. The server runs the program (PHP in this case), which returns a **response** — a ready-made HTML page.
5. The browser draws the HTML as a nice-looking page.

## PHP

**PHP** is the programming language the program is written in. Its files end in `.php`.
The code runs **on the server**, not at the visitor's computer. That is why the visitor never sees your passwords.

A small example:

```php
$name = "Maria";            // variable: a box with a name that holds a value
echo "Hello, " . $name;     // echo displays text; the dot glues two texts together
```

What else you will meet:

- **Function** — a named piece of code that can be called: `setting('site_name')`.
- **Array** — a list of values: `['bg', 'en']`.
- **Class** — a "blueprint" for a group of related things; a **method** is a function inside a class.
  Example: `SiteController` is a class and `page` is one of its methods.
- **namespace** (`namespace App\Models;`) — the "address" of the class, so that two classes with the same name do not get mixed up.
- **use** (`use App\Models\User;`) — says "I will use this class".

## Laravel and frameworks

A **framework** is a ready-made kit of tools for common tasks: sign-in with a password,
connecting to the database, validating forms, sending email. Without it we would write everything from scratch.

**Laravel** is the most popular PHP framework. The `vendor/` folder contains Laravel itself
(hundreds of ready-made files). It **must not be touched** and is created by the `composer install` command.

**Composer** is PHP's "app store". The `composer.json` file says what the project needs.

## CMS

A **CMS** (Content Management System) is a program in which
content is edited through a convenient panel instead of changing files by hand.
WordPress, Joomla and others work this way. **mCMS** is a CMS of our own.

## Database

A **database** is an organised store of information. Think of it as an Excel file with many sheets:

- **Table** (sheet): for example `users`.
- **Row**: one user.
- **Column**: a property — `name`, `email`, `password`.

We use **MySQL**. The language for talking to it is SQL, but in this project we hardly ever write SQL by hand —
we use **Eloquent models**:

```php
User::where('email', 'ivan@example.com')->first();   // "find the first user with this email"
```

A **model** is a PHP class that represents one table (`User` ↔ `users`).

A **migration** is a file that describes how to *create* a table (`database/migrations/…`).
This way the database schema is kept together with the code.

## Request, response, GET and POST

- **GET** — "give me a page" (opening an address, a link).
- **POST** — "accept this data" (submitting a form: sign-in, saving a page).
- **PUT / DELETE** — "change" / "delete". Browsers only know GET and POST,
  so in the form we put `@method('PUT')` and Laravel understands the real intention.

## Session, cookie, CSRF

- **Cookie** — a small piece of text that the browser stores and sends with every request.
- **Session** — information remembered about the visitor on the server (for example "signed in as Ivan").
  The cookie holds only a meaningless session identifier.
- **CSRF** — an attack in which another site tries to submit a form on your behalf.
  The protection is a hidden `@csrf` field with a secret code in every form. Laravel checks it automatically.

## Blade (templates)

Blade is a way to mix HTML and PHP. Its files end in `.blade.php`.

| Syntax | Meaning |
|--------|---------|
| `{{ $title }}` | Show the value and **escape** special characters (protection against XSS) |
| `{!! $html !!}` | Show **without** escaping (only for content that has already been cleaned!) |
| `@if (...) ... @endif` | Condition |
| `@foreach ($items as $item) ... @endforeach` | Repeat for each item |
| `@extends('site.layout')` | "This page uses the shared template" |
| `@section('content') ... @endsection` | "This is the part that gets inserted into the template" |
| `@csrf` | The hidden protection field |

## The .env file

`.env` is a text file with the **secret settings** of a particular server: the database password,
the `APP_KEY` encryption key, the site address. It is **never uploaded publicly**
and never shared. `.env.example` is only a template without secrets.

## What a "hook" is

A hook is a "hanger" in the program on which a plugin can hang its own code.
Example: before `<head>` is closed, the program says "if anyone wants to add something — now is the moment"
(`do_action('head')`). The plugin registers itself with `add_action('head', ...)`.

## What XSS is and why we care so much about "cleaning"

**XSS** is an attack in which a malicious person inserts JavaScript into text (for example in a comment),
and the program shows it to other visitors, where it gets executed. That is why:

1. Everything output through `{{ }}` is escaped automatically.
2. Content from the editor (which *must* be HTML) goes through `Sanitizer` when saved.
