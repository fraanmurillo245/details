<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idWedding = (int)($in['id_wedding'] ?? 0);

$wedding = Wedding::currentFor($app, $idAccount, $idWedding);
if (!$wedding) App::json(['ok' => false, 'error' => 'not_found'], 404);
$idWedding = (int)$wedding['id_wedding'];

$stmt = $app->db->prepare('SELECT design_suggestion FROM wedding_pages WHERE id_wedding = ? LIMIT 1');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$suggestion = $row ? json_decode((string)$row['design_suggestion'], true) : null;
if (!is_array($suggestion) || empty($suggestion['blocks'])) {
    App::json(['ok' => false, 'error' => 'no_suggestion'], 400);
}

$blocksJson = json_encode($suggestion['blocks']);
$themeJson = json_encode($suggestion['theme'] ?? []);

$stmt = $app->db->prepare('UPDATE wedding_pages SET blocks_json = ?, theme_json = ?, updated_at = NOW() WHERE id_wedding = ?');
$stmt->bind_param('ssi', $blocksJson, $themeJson, $idWedding);
$stmt->execute();
$stmt->close();

$app->log('design.ai_apply', $idWedding, $idAccount);

App::json(['ok' => true]);
