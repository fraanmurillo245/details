<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/** Crea cuenta + usuario propietario + boda inicial (con página de diseño vacía). */
class Onboarding
{
    public static function provision(App $app, string $p1, string $p2, string $email, string $passwordHash, string $slug): array
    {
        $name = trim($p1 . ' & ' . $p2);
        $stmt = $app->db->prepare(
            'INSERT INTO accounts (name, email, plan, plan_expires_at, active, created_at, updated_at)
             VALUES (?, ?, "flat", DATE_ADD(NOW(), INTERVAL 1 YEAR), 1, NOW(), NOW())'
        );
        $stmt->bind_param('ss', $name, $email);
        $stmt->execute();
        $idAccount = (int)$stmt->insert_id;
        $stmt->close();

        $stmt = $app->db->prepare(
            'INSERT INTO users (id_account, email, password, rol, active, created_at, updated_at)
             VALUES (?, ?, ?, "owner", 1, NOW(), NOW())'
        );
        $stmt->bind_param('iss', $idAccount, $email, $passwordHash);
        $stmt->execute();
        $idUser = (int)$stmt->insert_id;
        $stmt->close();

        $stmt = $app->db->prepare(
            'INSERT INTO weddings (id_account, slug, partner1_name, partner2_name, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, "draft", NOW(), NOW())'
        );
        $stmt->bind_param('isss', $idAccount, $slug, $p1, $p2);
        $stmt->execute();
        $idWedding = (int)$stmt->insert_id;
        $stmt->close();

        $blocks = json_encode(['cover', 'countdown', 'location', 'rsvp']);
        $theme = json_encode(['color_primary' => '#b76e79', 'color_secondary' => '#faf6f2', 'font' => 'serif']);
        $stmt = $app->db->prepare(
            'INSERT INTO wedding_pages (id_wedding, blocks_json, theme_json, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())'
        );
        $stmt->bind_param('iss', $idWedding, $blocks, $theme);
        $stmt->execute();
        $stmt->close();

        return ['id_account' => $idAccount, 'id_user' => $idUser, 'id_wedding' => $idWedding];
    }
}
