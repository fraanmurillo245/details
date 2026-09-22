<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idTable = (int)($in['id'] ?? 0);

$stmt = $app->db->prepare(
    'DELETE t FROM seating_tables t INNER JOIN weddings w ON w.id_wedding = t.id_wedding
     WHERE t.id_table = ? AND w.id_account = ?'
);
$stmt->bind_param('ii', $idTable, $idAccount);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if ($deleted) {
    // Los invitados de esa mesa quedan sin asignar, no se borran.
    $stmt = $app->db->prepare('UPDATE guests SET id_table = 0 WHERE id_table = ?');
    $stmt->bind_param('i', $idTable);
    $stmt->execute();
    $stmt->close();
    $app->log('table.delete', $idTable, $idAccount);
}

App::json(['ok' => $deleted]);
