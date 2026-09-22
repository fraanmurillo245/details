<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idGuest = (int)($in['id'] ?? 0);

$stmt = $app->db->prepare(
    'DELETE g FROM guests g INNER JOIN weddings w ON w.id_wedding = g.id_wedding
     WHERE g.id_guest = ? AND w.id_account = ?'
);
$stmt->bind_param('ii', $idGuest, $idAccount);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if ($deleted) {
    $stmt = $app->db->prepare('DELETE FROM guest_allergens WHERE id_guest = ?');
    $stmt->bind_param('i', $idGuest);
    $stmt->execute();
    $stmt->close();
    $app->log('guest.delete', $idGuest, $idAccount);
}

App::json(['ok' => $deleted]);
