CREATE TABLE IF NOT EXISTS wedding_photos (
    id_photo      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_wedding    INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a weddings (sin FK)
    filename      VARCHAR(120) NOT NULL,             -- nombre en disco (uploads/weddings/<id_wedding>/<filename>)
    original_name VARCHAR(190) NOT NULL DEFAULT '',
    mime_type     VARCHAR(60)  NOT NULL DEFAULT '',
    size_bytes    INT UNSIGNED NOT NULL DEFAULT 0,
    sort_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_photo),
    KEY idx_wedding (id_wedding)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
