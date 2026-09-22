-- Idiomas contratados por invitación: 1 incluido gratis (el idioma en que se
-- creó la boda) y extras de pago (ver Settings::extraLanguage* y
-- class/weddinglanguages.php). Sin fila para un idioma = no contratado.
CREATE TABLE IF NOT EXISTS wedding_languages (
    id_wedding_language INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_wedding          INT UNSIGNED NOT NULL,
    language             VARCHAR(5) NOT NULL,
    is_included          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at           TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_wedding_language),
    UNIQUE KEY uq_wedding_lang (id_wedding, language),
    KEY idx_wedding (id_wedding)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
