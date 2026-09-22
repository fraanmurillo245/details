CREATE TABLE IF NOT EXISTS guests (
    id_guest        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_wedding      INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a weddings (sin FK)
    id_table        INT UNSIGNED NOT NULL DEFAULT 0,   -- ref. lógica a seating_tables (0 = sin asignar)
    name            VARCHAR(190) NOT NULL,
    email           VARCHAR(190) NOT NULL DEFAULT '',
    phone           VARCHAR(40)  NOT NULL DEFAULT '',
    group_name      VARCHAR(80)  NOT NULL DEFAULT '',   -- familia novia, familia novio, amigos, trabajo...
    max_companions  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    rsvp_status     VARCHAR(20)  NOT NULL DEFAULT 'pending',   -- pending | confirmed | declined
    rsvp_companions TINYINT UNSIGNED NOT NULL DEFAULT 0,
    rsvp_message    VARCHAR(500) NOT NULL DEFAULT '',
    rsvp_at         TIMESTAMP NULL DEFAULT NULL,
    notes           VARCHAR(500) NOT NULL DEFAULT '',
    created_at      TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_guest),
    KEY idx_wedding (id_wedding),
    KEY idx_table (id_table),
    KEY idx_rsvp_status (rsvp_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
