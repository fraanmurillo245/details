<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/**
 * Idiomas contratados por invitación: 1 incluido gratis (el idioma en que
 * se creó la boda) y extras de pago vía Stripe (Settings::extraLanguage*).
 * Sin fila en wedding_languages para un código = no contratado.
 */
class WeddingLanguages
{
    public const ALL = ['es', 'en', 'fr', 'it'];

    /** Da de alta el idioma incluido gratis de una boda recién creada. Idempotente. */
    public static function ensureIncluded(App $app, int $idWedding, string $language): void
    {
        if (!in_array($language, self::ALL, true)) $language = 'es';
        $stmt = $app->db->prepare(
            'INSERT IGNORE INTO wedding_languages (id_wedding, language, is_included, created_at) VALUES (?, ?, 1, NOW())'
        );
        $stmt->bind_param('is', $idWedding, $language);
        $stmt->execute();
        $stmt->close();
    }

    /** Idiomas ya contratados (incluido + comprados), el incluido primero. */
    public static function contracted(App $app, int $idWedding): array
    {
        $stmt = $app->db->prepare(
            'SELECT language, is_included FROM wedding_languages WHERE id_wedding = ? ORDER BY is_included DESC, language ASC'
        );
        $stmt->bind_param('i', $idWedding);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public static function contractedCodes(App $app, int $idWedding): array
    {
        return array_column(self::contracted($app, $idWedding), 'language');
    }

    public static function isContracted(App $app, int $idWedding, string $language): bool
    {
        return in_array($language, self::contractedCodes($app, $idWedding), true);
    }

    /** Idiomas que aún no se han contratado para esta boda (candidatos a comprar). */
    public static function available(App $app, int $idWedding): array
    {
        return array_values(array_diff(self::ALL, self::contractedCodes($app, $idWedding)));
    }

    /** Registra un idioma ya pagado (o gratis en modo sin Stripe) como comprado. Idempotente. */
    public static function add(App $app, int $idWedding, string $language): void
    {
        if (!in_array($language, self::ALL, true)) return;
        $stmt = $app->db->prepare(
            'INSERT IGNORE INTO wedding_languages (id_wedding, language, is_included, created_at) VALUES (?, ?, 0, NOW())'
        );
        $stmt->bind_param('is', $idWedding, $language);
        $stmt->execute();
        $stmt->close();
    }
}
