CREATE TABLE {{TABLE_LOGS}}
(
  id         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  message    LONGTEXT COLLATE utf8mb4_unicode_ci NOT NULL,
  created_at TIMESTAMP                           NOT NULL DEFAULT CURRENT_TIMESTAMP
);