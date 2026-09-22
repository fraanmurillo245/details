CREATE TABLE IF NOT EXISTS accounts (
    id_account       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name             VARCHAR(190) NOT NULL,          -- p.ej. "Ana & Juan"
    email            VARCHAR(190) NOT NULL,
    plan             VARCHAR(30)  NOT NULL DEFAULT 'flat',
    plan_expires_at  TIMESTAMP NULL DEFAULT NULL,
    active           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at       TIMESTAMP NULL DEFAULT NULL,
    updated_at       TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_account),
    UNIQUE KEY uq_email (email),
    KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
