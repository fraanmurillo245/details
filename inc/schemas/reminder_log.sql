-- Registro de recordatorios enviados a cuentas que aún no han pagado, para
-- no mandar el mismo aviso dos veces (ver _crons/send_reminders.php).
CREATE TABLE IF NOT EXISTS reminder_log (
    id_reminder   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_account    INT UNSIGNED NOT NULL,
    reminder_type VARCHAR(20) NOT NULL,   -- '3day' | '7day'
    sent_at       TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id_reminder),
    UNIQUE KEY uq_account_type (id_account, reminder_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
