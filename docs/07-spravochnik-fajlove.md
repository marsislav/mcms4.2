# 7. Справочник на файловете

Кратко описание на всеки файл. Пътищата са спрямо корена на проекта.

## Корен
| Файл | Описание |
|---|---|
| `composer.json` | Нужни пакети (Laravel 12, PHP 8.2+) и автозареждане на `app/helpers.php` |
| `artisan` | Входна точка за команди `php artisan …` |
| `.env.example` | Шаблон за `.env` (тайни настройки) |
| `.htaccess` | Блокира вътрешните папки, ако document root е коренът |
| `.gitignore` | Какво да не влиза в git (`.env`, `vendor`…) |
| `README.md` | Кратко ръководство за стартиране |

## bootstrap/ и config/
| Файл | Описание |
|---|---|
| `bootstrap/app.php` | Първо пускане (папки, `.env`), маршрути, middleware |
| `bootstrap/providers.php` | Списък със стартови класове (само `AppServiceProvider`) |
| `config/app.php` | Име, език, часова зона, ключ |
| `config/session.php` | Сесии: криптиране, бисквитка, живот |
| `config/mcms.php` | Версия, админ път, лимити, роли, резервирани адреси, разрешени видео сайтове |

## routes/
| Файл | Описание |
|---|---|
| `routes/web.php` | Всички адреси: инсталатор, админ, публичен сайт (езиковите маршрути преди обикновените) |
| `routes/console.php` | Команди `mcms:version`, `mcms:backup`, `mcms:unlock` |

## app/helpers.php
Функции: `t`, `setting`, `setting_l`, `lroute`, `admin_url`, `upload_url`, `csp_nonce`, `audit`, `excerpt`,
`add_action`, `do_action`, `add_filter`, `apply_filters`, плюс `mcms_*` помощници за езици и преводи.

## app/Http/Middleware/
| Файл | Описание |
|---|---|
| `CheckInstalled` | Праща към `/install`, ако няма инсталация; скрива `/install` след това |
| `SecurityHeaders` | CSP с nonce и други защитни заглавки |
| `SetLocale` | Определя езика по префикса в адреса |
| `CheckMaintenance` | Страница 503 при профилактика |
| `AdminGuard` | Вход, активен акаунт, браузър, бездействие, права |

## app/Http/Controllers/
| Файл | Описание |
|---|---|
| `Controller` | Базов клас |
| `InstallController` | Уеб инсталатор |
| `SiteController` | Публичен сайт: начало, страница, публикация, блог, категория, търсене, контакт, sitemap, robots, RSS, 404/пренасочвания |
| `Admin/AuthController` | Вход, изход |
| `Admin/ProfileController` | Профил, парола |
| `Admin/DashboardController` | Начално табло |
| `Admin/ContentController` | Страници и публикации (списък, форма, запис, изтриване, версии) |
| `Admin/CategoryController` | Категории |
| `Admin/MediaController` | Медийна библиотека |
| `Admin/MenuController` | Менюта |
| `Admin/LanguageController` | Езици |
| `Admin/TranslationController` | Преводи на интерфейса |
| `Admin/MessageController` | Съобщения от контактната форма |
| `Admin/CommentController` | Модерация на коментари (одобри, спам, кошче, отговор, редакция) |
| `CommentController` | Приема коментар от публичната форма (защити, правила, известия) |
| `Admin/UserController` | Потребители |
| `Admin/SettingController` | Настройки (лого, поща, сигурност…) |
| `Admin/RedirectController` | Пренасочвания |
| `Admin/AuditController` | Журнал |
| `Admin/BackupController` | Резервни копия |
| `Admin/PluginController` | Включване/изключване на плъгини |

## app/Models/
| Файл | Таблица |
|---|---|
| `User` | users |
| `Language` | languages |
| `Translation` | translations |
| `Setting` | settings (с кеш) |
| `Media` | media |
| `Category`, `CategoryTranslation` | categories, category_translations |
| `Content`, `ContentTranslation` | contents, content_translations |
| `Revision` | revisions |
| `Tag`, `TagTranslation` | tags, tag_translations (+ pivot `content_tag`, `content_related`) |
| `Menu`, `MenuItem` | menus, menu_items |
| `Message` | messages |
| `Redirect` | redirects |
| `AuditLog` | audit_logs |

## app/Support/ и app/Providers/
| Файл | Описание |
|---|---|
| `Sanitizer` | Почиства HTML |
| `Uploader` | Безопасно качване и обработка на файлове |
| `Backup` | Архиви на базата и качените файлове |
| `ContentSaver` | Запис на страница/публикация с преводи и версии |
| `Installer` | Начални данни |
| `EnvWriter` | Безопасно пише в `.env` |
| `Discussion` | Коментари: правила за модерация, затваряне, нишки, форматиране на текста |
| `Taxonomy` | Тагове и свързани публикации: разбор, запис, алгоритъм за „свързани“ |
| `Hooks` | Хукове за плъгини |
| `PluginManager` | Намира и зарежда плъгини |
| `Providers/AppServiceProvider` | SMTP от настройките, плъгини, данни за общия шаблон (езици, менюта) |

## database/ и lang/
| Файл | Описание |
|---|---|
| `migrations/2026_01_01_000001_create_core_tables.php` | Всички таблици |
| `seeders/strings.php` | Начални преводи на интерфейса (bg/en) |
| `lang/bg/validation.php` | Български съобщения за грешки във формуляри |

## resources/views/
| Папка / файл | Описание |
|---|---|
| `site/layout` | Общият шаблон: хедър, меню, футър, езици, SEO заглавки |
| `site/page`, `post`, `blog`, `search`, `contact` | Страниците на сайта |
| `site/partials/card` | Карта на публикация в списък |
| `admin/layout` | Рамката на панела (странично меню) |
| `admin/login` | Вход |
| `admin/dashboard`, `profile`, `settings`, `audit`, `backups`, `plugins`, `redirects` | Съответните раздели |
| `admin/content/index,form` | Списък и форма за страници/публикации |
| `admin/categories`, `media`, `menus`, `messages`, `languages`, `translations`, `users` | Съответните раздели |
| `admin/partials/media-field` | Избор на снимка от библиотеката |
| `install/index` | Инсталатор |
| `errors/*` | 403, 404, 419, 429, 500, 503 |
| `pagination/mcms` | Страниране |

## public/
| Файл | Описание |
|---|---|
| `index.php` | Входна врата |
| `.htaccess` | Пренасочва към `index.php`, скрива `.env` и др. |
| `assets/css/site.css`, `admin.css`, `errors.css` | Стилове |
| `assets/js/site.js`, `admin.js` | Скриптове (без вградени `onclick`) |
| `app/Support/Design.php` | Настройки „Дизайн“: цветове, шрифтове, анимации, top-bar → генерира `/design.css` |
| `resources/views/admin/partials/design-form.blade.php` | Формата на таб „Дизайн“ |
| `assets/js/editor-fa.js` | Плъгин на CKEditor: избор и вмъкване на Font Awesome икони |
| `assets/vendor/fontawesome/` | Font Awesome Free 7.3.1 (CSS, woff2, icons.json) |
| `assets/js/editor-grid.js` | Плъгин на CKEditor „Колони“ (редове и колони 1–12) |
| `assets/css/grid.css` | Стилове и responsive за редове/колони |
| `assets/js/editor.js` | Настройка на CKEditor 5 (плъгини, бутони, библиотека с медия) |
| `assets/vendor/ckeditor5/` | CKEditor 5 v49 (GPL) + български превод |
| `uploads/.htaccess` | Забранява PHP в качените файлове |

## Други
| Файл | Описание |
|---|---|
| `plugins/schema-org/` | Примерен плъгин (структурирани данни за търсачките) |
| `storage/.htaccess` | Забранява достъп отвън |
| `tests/standalone.php` | Тестове на Sanitizer, EnvWriter (`php tests/standalone.php`) |
| `docs/` | Тази документация |
- `app/Support/Widgets.php`, `admin/partials/{footer-form,sidebar-form,widget-card,widget-area}.blade.php`, `site/partials/widget-area.blade.php`, `site/widgets/*.blade.php` — уиджети (виж 08 §23)
