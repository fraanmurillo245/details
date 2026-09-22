<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idWedding = (int)($in['id'] ?? 0);

$stmt = $app->db->prepare('DELETE FROM weddings WHERE id_wedding = ? AND id_account = ?');
$stmt->bind_param('ii', $idWedding, $idAccount);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

if ($deleted) {
    foreach (['wedding_pages', 'guests', 'seating_tables'] as $table) {
        $stmt = $app->db->prepare("DELETE FROM $table WHERE id_wedding = ?");
        $stmt->bind_param('i', $idWedding);
        $stmt->execute();
        $stmt->close();
    }
    $app->log('wedding.delete', $idWedding, $idAccount);
}

App::json(['ok' => $deleted]);
