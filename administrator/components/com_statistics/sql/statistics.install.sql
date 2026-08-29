CREATE TABLE IF NOT EXISTS `#__ws_stats_counter`
(
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `stat_date` DATE         NOT NULL,
    `metric`    VARCHAR(32)  NOT NULL,
    `bucket`    VARCHAR(32)  NOT NULL,
    `hits`      INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `idx_ws_stats_unique` UNIQUE (`stat_date`, `metric`, `bucket`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  DEFAULT COLLATE = utf8mb4_unicode_ci;
