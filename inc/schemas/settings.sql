-- Ajustes globales de la plataforma (clave/valor). Hoy solo la tarifa
-- plana, pero sirve para cualquier otro parámetro futuro sin migrar.
CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(60) NOT NULL,
    setting_value VARCHAR(255) NOT NULL DEFAULT '',
    updated_at    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES
    ('flat_fee_cents', '4900', NOW()),
    ('flat_fee_currency', 'eur', NOW());
