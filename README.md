# mCMS 4.2

Сигурна многоезична CMS на **Laravel 12** (PHP 8.2+), проектирана за **споделен хостинг без SSH**.
Без външни пакети освен самия Laravel: двуфакторна защита, sanitizer, превод, плъгини, архиви — всичко е собствен код.

## Функции

**Съдържание** — страници и публикации, категории, медиа библиотека (с обработка на снимки), менюта (2 нива), насрочено публикуване, чернови, история на версиите (последни 20, възстановяване с един клик), начална страница, noindex по страница, търсене, RSS, sitemap.xml с hreflang, robots.txt, 301/302 пренасочвания, SEO/Open Graph/canonical.
**Многоезичност (в базата данни)** — езици и UI преводи се управляват от панела (`t('ключ')`), съдържанието има превод на всеки език, `/en/...` префикси, hreflang, превключвател на езика.
**Администрация** — роли (администратор/редактор/автор), потребители, журнал на действията, настройки (лого, favicon, социални мрежи, SMTP от панела), режим „профилактика“, архиви на БД и файлове, здравен контрол на сигурността в таблото.
**Разширяемост** — плъгини с hooks (`add_action`/`add_filter`), примерен плъгин `schema-org`, команди `php artisan mcms:*`.
**Контакт форма** — honeypot + времева защита + rate limit, запис в панела и имейл.

## Сигурност

| Област | Мерки |
|---|---|
| Вход | bcrypt, rate limit по имейл+IP и по IP, заключване на акаунт (8 грешки → 15 мин), еднакво време за несъществуващ имейл, регенериране на сесията |
| 2FA | TOTP (RFC 6238), защита от повторно използване на код, 8 кода за възстановяване (само хешове), опция „задължителна за администратори“ |
| Сесии | криптирани, HttpOnly, SameSite=Lax, Secure при HTTPS, изтичане при неактивност, обвързване с User-Agent, прекратяване на другите сесии при смяна на парола |
| Админ път | конфигурируем (`MCMS_ADMIN_PATH`), `noindex`, `no-store`, не се споменава в robots.txt |
| Заглавки | строг CSP с nonce (без inline скриптове/стилове), HSTS, X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy, COOP |
| Съдържание | allow-list HTML sanitizer (DOM), блокиране на javascript:/data: URL, iframe само YouTube/Vimeo, `rel=noopener` |
| Качване | MIME по съдържание (finfo), случайни имена, **преобработка с GD** (маха EXIF/payload), ограничение на пиксели/размер, `.htaccess` без PHP изпълнение |
| Инсталатор | изисква еднократен ключ от `.env`, rate limit, заключва се след инсталация, не изтрива чужди данни |
| Данни | Eloquent/prepared statements, mass-assignment защита (роля/2FA не са fillable), SMTP паролата е криптирана |
| Плъгини | само по FTP (не през уеб), грешка в плъгин не сваля сайта |
| Одит | всички важни действия се записват (180 дни) |

## Инсталация на споделен хостинг (без SSH)

Нужен е еднократно компютър с **PHP 8.2+ и Composer** — `vendor/` не е включена в архива.

1. Разархивирайте проекта и в папката му изпълнете:
   ```bash
   composer install --no-dev --optimize-autoloader
   php tests/standalone.php        # по желание: самопроверка на сигурността
   ```
2. Качете **цялата** папка (с `vendor/`) на хостинга, **извън** публичната директория, ако е възможно (напр. `/home/user/mcms42`).
3. Насочете document root на домейна към `mcms42/public` (cPanel → Domains → Document Root).
   *Ако не можете:* качете всичко в `public_html` — корневият `.htaccess` пренасочва към `public/` и блокира вътрешните папки (по-слабо препоръчително).
4. Дайте права за запис на `storage/`, `bootstrap/cache/` и `public/uploads/` (755/775).
5. Отворете `https://вашият-домейн/install`. При първото зареждане се създава `.env` с `MCMS_INSTALL_KEY` — копирайте ключа от файла (File Manager) във формата.
6. Попълнете данните за MySQL, сайта и администратора. След инсталация `/install` връща 404.
7. Влезте на `https://вашият-домейн/panel/login`, веднага активирайте **2FA** (Профил) и сменете `MCMS_ADMIN_PATH` в `.env`.

### След инсталация
- В `.env`: `APP_DEBUG=false`, `TRUSTED_PROXIES=*` ако сте зад Cloudflare.
- Вкарайте SPF/DKIM за домейна или ползвайте SMTP (Настройки → Изпращане на поща → „тестово писмо“).
- Правете архив от Панел → Архиви (БД + файлове) и го теглете извън сървъра.

## Чести проблеми

- **`ext-gd` липсва (XAMPP):** в `C:\xampp\php\php.ini` разкоментирайте `extension=gd` (махнете `;`), рестартирайте и проверете с `php -m`. Същото за `zip`, `intl`, `exif`, `fileinfo`, `mbstring`, `pdo_mysql` ако липсват.
- **Composer блокира Laravel заради advisories:** ползвайте текущата версия на проекта (Laravel 12). Не изключвайте `block-insecure`.

## Структура

```
app/Http/Controllers/Admin/*   панелът          app/Support/*   Sanitizer, Totp, Uploader, Backup, Hooks...
app/Http/Controllers/Site*     публичният сайт  app/Models/*    Eloquent модели
resources/views/{admin,site}   Blade изгледи    plugins/*       плъгини
database/migrations            схема            lang/bg         български съобщения за валидация
public/assets                  CSS/JS           tests/          самотестове
```

## Плъгини

`plugins/<име>/plugin.json` + `plugins/<име>/plugin.php`. Активират се от Панел → Плъгини.

```php
add_action('head', fn () => print '<meta name="x" content="y">');
add_filter('content.body', fn ($html, $content) => $html);
add_action('contact.received', fn ($message) => null);
```
Куки: `head`, `footer`, `content.body`, `content.saved`, `contact.received`, `user.login`, `admin.menu`, `site.menu`.

## Команди (само ако имате терминал)

`php artisan mcms:backup` · `mcms:unlock имейл` · `mcms:reset-2fa имейл` · `mcms:version`

## Бележки

- Изпращането на поща ползва Symfony Mailer (стандартът на Laravel 12; Swift_Mailer е прекратен).
- Вграденото архивиране е за MySQL/MariaDB. Възстановяване — през phpMyAdmin (импорт на `.sql.gz`).
- Не е тествано на живо сървър в средата на разработка (липсва достъп до Packagist) — направете преглед на `/install` в тестова база преди продукция.

## Документация
Пълно описание за начинаещи на български: папка [`docs/`](docs/README.md).

## Редактор
CKEditor 5 (GPL, безплатни плъгини) — вижте `docs/08-kak-da-promenyam.md`, раздел 12.
