<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/** Ajustes globales de la plataforma (clave/valor en BD), p.ej. la tarifa plana. */
class Settings
{
    public static function get(App $app, string $key, string $default = ''): string
    {
        $stmt = $app->db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row['setting_value'] : $default;
    }

    public static function set(App $app, string $key, string $value): void
    {
        $stmt = $app->db->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
        $stmt->close();
    }

    public static function flatFeeCents(App $app): int
    {
        return (int)self::get($app, 'flat_fee_cents', '4900');
    }

    public static function flatFeeCurrency(App $app): string
    {
        return self::get($app, 'flat_fee_currency', 'eur');
    }

    /** Precio de un idioma extra para la invitación pública (ver class/weddinglanguages.php). */
    public static function extraLanguageCents(App $app): int
    {
        return (int)self::get($app, 'extra_language_cents', '2500');
    }

    public static function extraLanguageCurrency(App $app): string
    {
        return self::get($app, 'extra_language_currency', 'eur');
    }
}
