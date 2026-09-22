-- Catálogo global de alérgenos (los 14 de la UE + "otro").
CREATE TABLE IF NOT EXISTS allergens (
    id_allergen INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(80) NOT NULL,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_allergen),
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
