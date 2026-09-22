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

-- Migración: distingue la tarifa plana de los idiomas extra comprados
-- (ver class/weddinglanguages.php). ref_id = id_wedding para 'language';
-- sin uso (0) para 'flat_fee', que ya identifica la cuenta con id_account.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'kind');
SET @s := IF(@c = 0, "ALTER TABLE payments ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT 'flat_fee' AFTER id_account", 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'ref_id');
SET @s := IF(@c = 0, 'ALTER TABLE payments ADD COLUMN ref_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER kind', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;
