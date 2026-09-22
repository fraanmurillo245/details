CREATE TABLE IF NOT EXISTS activity_log (
    id_log      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_account  INT UNSIGNED NOT NULL DEFAULT 0,
    id_user     INT UNSIGNED NOT NULL DEFAULT 0,
    action      VARCHAR(60) NOT NULL,
    ref_id      INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_log),
    KEY idx_account (id_account),
    KEY idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
