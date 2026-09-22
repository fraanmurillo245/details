CREATE TABLE IF NOT EXISTS wedding_pages (
    id_page           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_wedding        INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a weddings (sin FK)
    blocks_json       LONGTEXT NULL,          -- array de bloques: cover, countdown, location, gift, gallery, rsvp...
    theme_json        LONGTEXT NULL,          -- paleta, tipografía
    design_suggestion LONGTEXT NULL,          -- propuesta de diseño generada por IA (fase 2), pendiente de aplicar
    created_at        TIMESTAMP NULL DEFAULT NULL,
    updated_at        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_page),
    UNIQUE KEY uq_wedding (id_wedding)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migración: texto libre para el bloque "gift" (cuenta bancaria, lista de regalos...).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wedding_pages' AND COLUMN_NAME = 'gift_message');
SET @s := IF(@c = 0, 'ALTER TABLE wedding_pages ADD COLUMN gift_message VARCHAR(1000) NOT NULL DEFAULT "" AFTER theme_json', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;
