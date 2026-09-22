CREATE TABLE IF NOT EXISTS weddings (
    id_wedding    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_account    INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a accounts (sin FK)
    slug          VARCHAR(80)  NOT NULL,             -- URL pública: /invite/<slug>/
    partner1_name VARCHAR(120) NOT NULL DEFAULT '',
    partner2_name VARCHAR(120) NOT NULL DEFAULT '',
    event_date    DATETIME NULL DEFAULT NULL,
    venue_name    VARCHAR(190) NOT NULL DEFAULT '',
    venue_address VARCHAR(255) NOT NULL DEFAULT '',
    template      VARCHAR(40)  NOT NULL DEFAULT 'classic',
    status        VARCHAR(20)  NOT NULL DEFAULT 'draft',   -- draft | published
    created_at    TIMESTAMP NULL DEFAULT NULL,
    updated_at    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_wedding),
    UNIQUE KEY uq_slug (slug),
    KEY idx_account (id_account),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
