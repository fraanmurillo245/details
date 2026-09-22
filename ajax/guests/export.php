<?php
include_once __DIR__ . '/../../config.php';

$idAccount = (int)$_user['id_account'];
$wedding = Wedding::currentFor($app, $idAccount, (int)($_GET['id_wedding'] ?? 0));
if (!$wedding) { http_response_code(404); exit('No encontrada'); }
$idWedding = (int)$wedding['id_wedding'];

$stmt = $app->db->prepare(
    'SELECT g.name, g.email, g.phone, g.group_name, g.max_companions, g.rsvp_status, g.rsvp_companions, g.notes,
            GROUP_CONCAT(a.name SEPARATOR "; ") AS allergens
     FROM guests g
     LEFT JOIN guest_allergens ga ON ga.id_guest = g.id_guest
     LEFT JOIN allergens a ON a.id_allergen = ga.id_allergen
     WHERE g.id_wedding = ?
     GROUP BY g.id_guest
     ORDER BY g.name ASC'
);
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="invitados-' . $wedding['slug'] . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM: para que Excel detecte UTF-8 y no rompa acentos
fputcsv($out, ['nombre', 'email', 'telefono', 'grupo', 'acompanantes_max', 'confirmacion', 'acompanantes_confirmados', 'alergenos', 'notas'], ',', '"', '\\');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['name'], $r['email'], $r['phone'], $r['group_name'],
        $r['max_companions'], $r['rsvp_status'], $r['rsvp_companions'], $r['allergens'] ?? '', $r['notes'],
    ], ',', '"', '\\');
}
fclose($out);
