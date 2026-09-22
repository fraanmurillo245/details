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

-- Migración: recuperación de contraseña. reset_token NULL (no "") para que
-- el UNIQUE KEY no choque entre usuarios sin token activo (MySQL permite
-- varios NULL en una UNIQUE KEY, pero no varias cadenas vacías iguales).
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token');
SET @s := IF(@c = 0, 'ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL AFTER password', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_expires');
SET @s := IF(@c = 0, 'ALTER TABLE users ADD COLUMN reset_expires TIMESTAMP NULL DEFAULT NULL AFTER reset_token', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_reset_token');
SET @s := IF(@c = 0, 'ALTER TABLE users ADD UNIQUE KEY uq_reset_token (reset_token)', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- Migración: administrador de la plataforma (distinto de "rol", que es a
-- nivel de cuenta/pareja). No hay UI para el primer admin: se marca a mano
-- por phpMyAdmin o con _crons/make_admin.php.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_admin');
SET @s := IF(@c = 0, 'ALTER TABLE users ADD COLUMN is_admin TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER rol', 'DO 0');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;
