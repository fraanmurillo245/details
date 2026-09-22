-- Registro temporal mientras se completa el pago en Stripe Checkout.
-- Se borra al provisionar la cuenta (o si el pago nunca se completa, queda
-- huérfano y puede limpiarse periódicamente por antigüedad).
CREATE TABLE IF NOT EXISTS pending_signups (
    id_pending    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    token         VARCHAR(64) NOT NULL,
    partner1_name VARCHAR(120) NOT NULL DEFAULT '',
    partner2_name VARCHAR(120) NOT NULL DEFAULT '',
    email         VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    wedding_slug  VARCHAR(80) NOT NULL,
    created_at    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_pending),
    UNIQUE KEY uq_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
