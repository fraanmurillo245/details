CREATE TABLE IF NOT EXISTS seating_tables (
    id_table    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_wedding  INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a weddings (sin FK)
    name        VARCHAR(80) NOT NULL,
    capacity    TINYINT UNSIGNED NOT NULL DEFAULT 8,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    updated_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_table),
    KEY idx_wedding (id_wedding)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
