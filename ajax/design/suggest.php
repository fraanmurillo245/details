<?php
define('IS_AJAX', true);
include_once __DIR__ . '/../../config.php';

if (!AiDesigner::isConfigured()) {
    App::json(['ok' => false, 'error' => 'ai_not_configured'], 400);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) App::json(['ok' => false, 'error' => 'bad request'], 400);

$idAccount = (int)$_user['id_account'];
$idWedding = (int)($in['id_wedding'] ?? 0);
$styleNotes = trim((string)($in['style_notes'] ?? ''));

$wedding = Wedding::currentFor($app, $idAccount, $idWedding);
if (!$wedding) App::json(['ok' => false, 'error' => 'not_found'], 404);
$idWedding = (int)$wedding['id_wedding'];

$stmt = $app->db->prepare('SELECT filename FROM wedding_photos WHERE id_wedding = ? ORDER BY sort_order ASC, id_photo ASC LIMIT 4');
$stmt->bind_param('i', $idWedding);
$stmt->execute();
$photoPaths = array_map(
    fn($row) => Photo::dir($idWedding) . '/' . $row['filename'],
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
);
$stmt->close();

try {
    $suggestion = AiDesigner::suggest($wedding, $photoPaths, $styleNotes);
} catch (RuntimeException $e) {
    error_log('AiDesigner: ' . $e->getMessage());
    App::json(['ok' => false, 'error' => 'ai_failed'], 502);
}

$suggestion['generated_at'] = date('c');
$suggestionJson = json_encode($suggestion, JSON_UNESCAPED_UNICODE);

$stmt = $app->db->prepare('UPDATE wedding_pages SET design_suggestion = ?, updated_at = NOW() WHERE id_wedding = ?');
$stmt->bind_param('si', $suggestionJson, $idWedding);
$stmt->execute();
$stmt->close();

$app->log('design.ai_suggest', $idWedding, $idAccount);

App::json(['ok' => true, 'suggestion' => $suggestion]);
