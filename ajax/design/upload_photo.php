<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$idAccount = (int)$_user['id_account'];
$idWedding = (int)($_POST['id_wedding'] ?? 0);

$wedding = Wedding::currentFor($app, $idAccount, $idWedding);
if (!$wedding) App::json(['ok' => false, 'error' => 'not_found'], 404);
$idWedding = (int)$wedding['id_wedding'];

$stmt = $app->db->prepare('SELECT COUNT(*) AS n FROM wedding_photos WHERE id_wedding = ?');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$count = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
$stmt->close();
if ($count >= Photo::MAX_PER_WEDDING) {
    App::json(['ok' => false, 'error' => 'limit_reached'], 400);
}

try {
    [$filename, $mime, $size] = Photo::store($_FILES['photo'] ?? [], $idWedding);
} catch (RuntimeException $e) {
    App::json(['ok' => false, 'error' => $e->getMessage()], 400);
}

$originalName = mb_substr((string)($_FILES['photo']['name'] ?? ''), 0, 190);
$stmt = $app->db->prepare(
    'INSERT INTO wedding_photos (id_wedding, filename, original_name, mime_type, size_bytes, sort_order, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())'
);
$stmt->bind_param('isssii', $idWedding, $filename, $originalName, $mime, $size, $count);
$stmt->execute();
$idPhoto = (int)$stmt->insert_id;
$stmt->close();

$app->log('photo.upload', $idPhoto, $idAccount);

App::json(['ok' => true, 'photo' => [
    'id' => $idPhoto,
    'url' => Photo::url($idWedding, $filename),
    'name' => $originalName,
]]);
