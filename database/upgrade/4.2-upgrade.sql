-- Alternative to the "Обнови базата" button: run in phpMyAdmin (SQL tab) on an EXISTING install.
-- Safe to run once. Adds tags + related posts.
CREATE TABLE IF NOT EXISTS `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tag_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` bigint unsigned NOT NULL,
  `locale` varchar(10) NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_translations_locale_slug_unique` (`locale`,`slug`),
  UNIQUE KEY `tag_translations_tag_id_locale_unique` (`tag_id`,`locale`),
  CONSTRAINT `tag_translations_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_tag` (
  `content_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`content_id`,`tag_id`),
  CONSTRAINT `content_tag_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `content_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `content_related` (
  `content_id` bigint unsigned NOT NULL,
  `related_id` bigint unsigned NOT NULL,
  `sort` smallint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`content_id`,`related_id`),
  CONSTRAINT `content_related_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `content_related_related_id_foreign` FOREIGN KEY (`related_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `translations` (`key`,`locale`,`value`) VALUES
 ('site.tags','bg','Тагове'),('site.related','bg','Свързани публикации'),('site.tag','bg','Таг: :name'),
 ('site.tags','en','Tags'),('site.related','en','Related posts'),('site.tag','en','Tag: :name')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Soft delete ("кошче") for pages, posts and categories. Run once (errors with "Duplicate column" if already applied).
ALTER TABLE `contents` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL, ADD INDEX `contents_deleted_at_index` (`deleted_at`);
ALTER TABLE `categories` ADD COLUMN `deleted_at` timestamp NULL DEFAULT NULL, ADD INDEX `categories_deleted_at_index` (`deleted_at`);

-- Comments (Settings > Дискусии). Run once.
CREATE TABLE IF NOT EXISTS `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(190) DEFAULT NULL,
  `author_url` varchar(200) DEFAULT NULL,
  `author_ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'pending',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_parent_id_index` (`parent_id`),
  KEY `comments_status_index` (`status`),
  KEY `comments_content_id_status_created_at_index` (`content_id`,`status`,`created_at`),
  CONSTRAINT `comments_content_id_foreign` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (errors with "Duplicate column" if already applied)
ALTER TABLE `contents` ADD COLUMN `comments_open` tinyint(1) NULL DEFAULT NULL;

INSERT INTO `translations` (`key`,`locale`,`value`) VALUES
('comments.title','bg','Коментари'),
('comments.n','bg','Коментари: :n'),
('comments.none','bg','Все още няма коментари.'),
('comments.leave','bg','Остави коментар'),
('comments.reply','bg','Отговор'),
('comments.reply_to','bg','Отговор до :name'),
('comments.cancel','bg','Откажи отговора'),
('comments.name','bg','Име'),
('comments.email','bg','Имейл (няма да бъде публикуван)'),
('comments.url','bg','Уебсайт'),
('comments.text','bg','Коментар'),
('comments.submit','bg','Публикувай коментара'),
('comments.closed','bg','Коментарите са затворени.'),
('comments.held','bg','Благодарим! Коментарът ви чака одобрение.'),
('comments.posted','bg','Благодарим! Коментарът е публикуван.'),
('comments.error','bg','Моля, проверете полетата и опитайте отново.'),
('comments.throttle','bg','Твърде много опити. Опитайте по-късно.'),
('comments.duplicate','bg','Този коментар вече е изпратен.'),
('comments.prev','bg','По-стари коментари'),
('comments.next','bg','По-нови коментари'),
('comments.staff','bg','Автор'),
('comments.anonymous','bg','Анонимен'),
('comments.title','en','Comments'),
('comments.n','en','Comments: :n'),
('comments.none','en','No comments yet.'),
('comments.leave','en','Leave a comment'),
('comments.reply','en','Reply'),
('comments.reply_to','en','Reply to :name'),
('comments.cancel','en','Cancel reply'),
('comments.name','en','Name'),
('comments.email','en','Email (will not be published)'),
('comments.url','en','Website'),
('comments.text','en','Comment'),
('comments.submit','en','Post comment'),
('comments.closed','en','Comments are closed.'),
('comments.held','en','Thank you! Your comment is awaiting moderation.'),
('comments.posted','en','Thank you! Your comment has been published.'),
('comments.error','en','Please check the fields and try again.'),
('comments.throttle','en','Too many attempts. Please try later.'),
('comments.duplicate','en','This comment has already been submitted.'),
('comments.prev','en','Older comments'),
('comments.next','en','Newer comments'),
('comments.staff','en','Author'),
('comments.anonymous','en','Anonymous')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Menu items: custom CSS class and ID. Run once (errors with "Duplicate column" if already applied).
ALTER TABLE `menu_items` ADD COLUMN `css_class` varchar(200) NULL DEFAULT NULL, ADD COLUMN `css_id` varchar(80) NULL DEFAULT NULL;
