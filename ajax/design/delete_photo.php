<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idPhoto = (int)($in['id'] ?? 0);

$stmt = $app->db->prepare(
    'SELECT p.id_photo, p.id_wedding, p.filename FROM wedding_photos p
     INNER JOIN weddings w ON w.id_wedding = p.id_wedding
     WHERE p.id_photo = ? AND w.id_account = ? LIMIT 1'
);
$stmt->bind_param('ii', $idPhoto, $idAccount);
$stmt->execute();
$photo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$photo) App::json(['ok' => false, 'error' => 'not_found'], 404);

$stmt = $app->db->prepare('DELETE FROM wedding_photos WHERE id_photo = ?');
$stmt->bind_param('i', $idPhoto);
$stmt->execute();
$stmt->close();

Photo::delete((int)$photo['id_wedding'], $photo['filename']);
$app->log('photo.delete', $idPhoto, $idAccount);

App::json(['ok' => true]);
