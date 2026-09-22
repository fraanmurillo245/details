CREATE TABLE IF NOT EXISTS guest_allergens (
    id_guest_allergen INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_guest          INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a guests (sin FK)
    id_allergen       INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a allergens (sin FK)
    notes             VARCHAR(190) NOT NULL DEFAULT '',
    created_at        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_guest_allergen),
    UNIQUE KEY uq_guest_allergen (id_guest, id_allergen),
    KEY idx_guest (id_guest),
    KEY idx_allergen (id_allergen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
