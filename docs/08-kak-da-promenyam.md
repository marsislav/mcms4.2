# 8. Как да променям програмата — готови рецепти

**Златно правило:** преди промяна направете копие на проекта (и резервно копие на базата от панела).
Променяйте по една малка неща и веднага проверявайте в браузъра.

## 1. Как да видя грешките

1. В `.env` сложете `APP_DEBUG=true` (само докато разработвате!). Грешките се показват подробно.
2. Дневникът е в `storage/logs/laravel.log` — най-долу е последната грешка.
3. На жив сайт върнете `APP_DEBUG=false`.
4. След промяна на `.env` или `config/*` понякога трябва да изтриете `bootstrap/cache/*.php`.

## 2. Промяна на цветове и шрифтове

Файл: `public/assets/css/site.css`. Най-горе има блок `:root { … }` с променливи
(`--accent`, `--bg`, `--text`…). Сменете стойностите — променя се целият сайт.
Панелът е в `admin.css`.

## 3. Промяна на текст от интерфейса (напр. „Прочети още“)

Без код: панел → **Преводи** → потърсете ключа → сменете текста.

Нов текст:
1. В шаблона напишете `{{ t('moy.klyuch') }}`.
2. Добавете ключа в панела (Преводи) или в `database/seeders/strings.php` (за нови инсталации).

## 4. Нов език

Панел → **Езици** → Добави (код `de`, име). После в **Преводи** попълнете текстовете,
а при всяка страница се появява нов раздел за езика. Нищо в кода не се пипа.
Код от 2 букви (`de`) става префикс в адреса (`/de/…`).

## 5. Промяна на роли и права

Файл: `config/mcms.php`, секция `roles`. Пример — авторите да виждат и категории:

```php
'author' => ['content', 'media', 'categories'],
```

Имената на правата са в `routes/web.php` (`admin:content`, `admin:menus` …).

## 6. Ново поле на страниците (пример: „подзаглавие“)

1. **Нова миграция** (не пипайте старата!): `database/migrations/2026_02_01_000001_add_subtitle.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('content_translations', function (Blueprint $t) {
            $t->string('subtitle')->nullable();
        });
    }
    public function down(): void {
        Schema::table('content_translations', fn (Blueprint $t) => $t->dropColumn('subtitle'));
    }
};
```

2. В модела `app/Models/ContentTranslation.php` добавете `'subtitle'` в `$fillable`.
3. Във формата `resources/views/admin/content/form.blade.php` добавете `<input name="tr[{{ $code }}][subtitle]">` до заглавието.
4. В `Admin/ContentController.php` (валидацията) добавете правило `'tr.*.subtitle' => 'nullable|string|max:255'`.
5. В `app/Support/ContentSaver.php` добавете `subtitle` към полетата, които се записват.
6. В `resources/views/site/page.blade.php` покажете: `{{ $tr->subtitle }}`.
7. **Изпълнете миграцията:** на хостинг без SSH — изпълнете SQL в phpMyAdmin:
   `ALTER TABLE content_translations ADD subtitle VARCHAR(255) NULL;`
   (с SSH/локално: `php artisan migrate`).

## 7. Нов раздел в админ панела (пример: „Партньори“)

1. Миграция + модел `app/Models/Partner.php`.
2. Контролер `app/Http/Controllers/Admin/PartnerController.php` (по образец на `RedirectController` — най-прост).
3. Маршрути в `routes/web.php`, в админ групата — копирайте блока на `redirects` (ред ~83) и сменете имената:
   `Route::middleware('admin:partners')->prefix('partners')->name('partners.')->group(function () { … });`
4. Шаблони в `resources/views/admin/partners/` (копирайте `redirects`).
5. Право `partners` в `config/mcms.php` за ролите, които имат достъп (админът има `*`).
6. Връзка в менюто на панела — във файла `resources/views/admin/layout.blade.php` (масивът с менюто).
7. Не забравяйте `audit('partner.create', …)` при запис.

## 8. Нов плъгин

Папка `plugins/moy-plugin/` с два файла:

`plugin.json`
```json
{ "name": "Мой плъгин", "version": "1.0.0", "description": "Какво прави" }
```

`plugin.php`
```php
<?php
// добавя текст в края на всяка страница
add_action('footer', function () {
    echo '<p>Здравей от плъгина</p>';
});
```

Качете папката по FTP → панел → **Плъгини** → Включи.
Налични хукове: `head`, `footer`, `content.body` (филтър), `content.saved`, `contact.received`,
`user.login`, `admin.menu`, `site.menu`. Вижте `plugins/schema-org/` като пример.

## 9. Нова позиция на меню

В `Menu` позициите са `header` и `footer`. За нова (напр. `sidebar`): добавете запис в таблица `menus`
(колона `location`), покажете я с `Menu::tree('sidebar')` в `AppServiceProvider` (view composer) и в шаблона.

## 10. Промяна на лимитите

`.env` / `config/mcms.php`: `MCMS_MAX_UPLOAD_MB`, `MCMS_IDLE_MINUTES`, `MCMS_ADMIN_PATH`.
Обърнете внимание: PHP също има свои лимити (`upload_max_filesize`, `post_max_size`).

## 11. Често допускани грешки

| Грешка | Решение |
|---|---|
| Промених `.blade.php` и няма ефект | Изтрийте `storage/framework/views/*.php` |
| `Class not found` | Липсва `use …;` най-горе или грешно име/namespace |
| 419 Page Expired | Във формуляра липсва `@csrf` или сесията е изтекла |
| 403 при запис | Ролята няма това право (`config/mcms.php`) |
| Нова колона „не се записва“ | Не е добавена в `$fillable` на модела |
| Нова колона липсва в базата | Не е изпълнена миграцията (рецепта 6, стъпка 7) |

## 12. Редакторът (CKEditor 5)

Полето „Съдържание“ е **CKEditor 5** (безплатна GPL версия, всички безплатни плъгини). Файлове:

| Файл | Роля |
|---|---|
| `public/assets/vendor/ckeditor5/` | Самият CKEditor (не се пипа). Лиценз: `LICENSE.md`, `COPYING.GPL` |
| `public/assets/js/editor.js` | Нашата настройка: кои плъгини, какви бутони, български, бутон „Медия“ (библиотеката) |
| `app/Support/Sanitizer.php` | Какво от HTML-а на редактора се пази при запис |

**Добавяне/махане на бутон:** в `editor.js` — масив `toolbar.items`. Плъгинът трябва да е и в `names`.

**Важно:** всичко, което редакторът произведе, минава през `Sanitizer`. Ако добавите нов плъгин и
резултатът „изчезва“ след запис — Sanitizer не го разрешава. Разширете `TAGS`, `ATTRS` или `STYLE_PROPS`
в `Sanitizer.php` и пуснете `php tests/standalone.php`.

**Видео:** YouTube/Vimeo се записват като `<oembed url="…">`, а при показване се превръщат в iframe
(`Sanitizer::renderEmbeds`, извиква се от `mcms_body()` в `app/helpers.php`).

**Стилове на съдържанието** (подравняване, маркери, снимки, таблици) са в края на `public/assets/css/site.css`.

**Не са включени** умишлено: Emoji (тегли данни от външен CDN), платените/облачни функции, качване на снимки
директно в редактора (ползвайте бутона „Медия“), HtmlEmbed, TodoList, Bookmark.

**Лиценз:** CKEditor 5 е GPL. За личен сайт няма проблем; ако продавате/раздавате системата на клиенти,
прочетете https://ckeditor.com/legal/ckeditor-licensing-options .

## 13. Редове и колони в редактора (бутон „Колони“)

В лентата на редактора има бутон **„Колони (ред)“** → избирате от 1 до 12 колони. Ред се вмъква в текста; във всяка колона пишете като в нормален текст
(заглавия, снимки, таблици…). Останалите бутони (активни, когато курсорът е в колона):

| Бутон | Какво прави |
|---|---|
| Ширина на колоната | Колоната заема 1–12 „части“ от реда (напр. ред с 4 части: колони 1+2+1) |
| Добави колона / Премахни колоната | Променя броя колони (1–12) |
| Изтрий целия ред | Маха реда с всичко в него |

Записва се като `<div class="mc-row mc-cols-3"><div class="mc-col mc-span-1">…</div>…</div>`.
Стилове: `public/assets/css/grid.css` (зарежда се и в сайта, и в редактора). **Responsive:** над 900px — както е зададено;
641–900px — по 2 колони; до 640px — една под друга. Код на плъгина: `public/assets/js/editor-grid.js`.
Ред в ред (вложени редове) не е позволен.
