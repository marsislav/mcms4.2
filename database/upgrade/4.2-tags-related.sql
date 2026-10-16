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
