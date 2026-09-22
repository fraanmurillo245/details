CREATE TABLE IF NOT EXISTS users (
    id_user     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_account  INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a accounts (sin FK)
    email       VARCHAR(190) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    rol         VARCHAR(20)  NOT NULL DEFAULT 'owner',   -- owner | admin
    active      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    updated_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_user),
    UNIQUE KEY uq_email (email),
    KEY idx_account (id_account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
