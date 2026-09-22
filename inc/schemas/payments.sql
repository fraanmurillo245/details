CREATE TABLE IF NOT EXISTS payments (
    id_payment        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_account        INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a accounts (sin FK)
    stripe_session_id VARCHAR(190) NOT NULL DEFAULT '',
    amount_cents      INT UNSIGNED NOT NULL DEFAULT 0,
    currency          VARCHAR(10) NOT NULL DEFAULT 'eur',
    status            VARCHAR(20) NOT NULL DEFAULT 'paid',
    created_at        TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_payment),
    UNIQUE KEY uq_session (stripe_session_id),
    KEY idx_account (id_account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
