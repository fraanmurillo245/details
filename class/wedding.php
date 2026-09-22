<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

class Wedding
{
    /** Resuelve la boda activa de la cuenta: la indicada por URL o, si no hay, la más reciente. */
    public static function currentFor(App $app, int $idAccount, int $idFromUrl = 0): ?array
    {
        if ($idFromUrl > 0) {
            $stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
            $stmt->bind_param('ii', $idFromUrl, $idAccount);
        } else {
            $stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_account = ? ORDER BY id_wedding DESC LIMIT 1');
            $stmt->bind_param('i', $idAccount);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
