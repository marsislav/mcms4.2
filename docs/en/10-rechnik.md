# 10. Glossary

| Term | Plain explanation |
|---|---|
| **Admin / panel** | The part of the site where content is managed (`/panel`) |
| **Alias** | A short name for something longer, e.g. `admin` for `AdminGuard` |
| **Apache** | The server program that receives requests from the internet |
| **Artisan** | Laravel's command-line tool (`php artisan …`) |
| **Audit log** | A record of who did what in the panel |
| **Bcrypt** | A way to "grind up" a password so that it cannot be reversed |
| **Blade** | Laravel's templating language (`.blade.php`) |
| **Cache** | A remembered result, so it is not recalculated every time |
| **Class** | A "blueprint" for a group of related things (code and data together) |
| **CMS** | Content management system |
| **Composer** | A tool that downloads the required PHP packages |
| **Config** | Settings (the `config/` folder) |
| **Controller** | A class that handles a request and decides what to return |
| **Cookie** | A small piece of text remembered by the browser |
| **CSP** | A rule that tells the browser which scripts are allowed |
| **CSRF** | An attack using a forged form; protection — the secret token `@csrf` |
| **Document root** | The folder the server exposes to the internet (should be `public/`) |
| **Eloquent** | Laravel's way of working with the database through PHP classes |
| **.env** | A file with the server's secret settings |
| **Framework** | A ready-made set of tools (Laravel) |
| **GD** | A PHP extension for working with images |
| **GET / POST** | Request types: "give me" / "accept data" |
| **Hash** | An irreversible "fingerprint" of data |
| **Hook** | A "hanger" for plugins |
| **HSTS** | A header meaning "always use HTTPS" |
| **HTTPS** | Encrypted connection (the padlock in the browser) |
| **Locale** | The page language (`bg`, `en`) |
| **Middleware** | A "guard" — a check before/after the request |
| **Migration** | A file describing the creation/alteration of a table |
| **Model** | A PHP class for one table |
| **Namespace** | The "address" of a class (`App\Models`) |
| **nonce** | A one-time secret code that allows a script under CSP |
| **PDO** | PHP's way of connecting to a database |
| **Plugin** | An add-on that extends the application without touching the core |
| **Rate limit** | A limit on the number of attempts within a time window |
| **Request / Response** | Request / response |
| **Revision** | A saved older version of a text |
| **Route** | A rule "this URL → this code" |
| **Sanitizer** | Cleans dangerous code out of HTML |
| **Scope** | Our own short query in a model (e.g. `visible()`) |
| **Seeder** | Code/file that fills the database with initial data |
| **Session** | Information about the visitor remembered on the server |
| **Slug** | The readable part of the URL (`za-nas`) |
| **SMTP** | A protocol for sending mail |
| **SQL** | The language for talking to a database |
| **Table / row / column** | Sheet / record / property in the database |
| **Transaction** | "All or nothing" when writing to the database |
| **Validation** | Checking whether the entered data is correct |
| **vendor/** | The folder with Laravel and the packages — do not touch |
| **View** | An HTML template |
| **XSS** | Injection of a foreign script into a page; protection — Sanitizer + `{{ }}` + CSP |
