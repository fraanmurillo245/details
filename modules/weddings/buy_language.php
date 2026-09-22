<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$idWedding = (int)$_id;
$language = (string)($_GET['lang'] ?? '');

$stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
$stmt->bind_param('ii', $idWedding, $idAccount);
$stmt->execute();
$wedding = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wedding) { $app->redirect(u('weddings')); }

if (!in_array($language, WeddingLanguages::ALL, true) || WeddingLanguages::isContracted($app, $idWedding, $language)) {
    $app->redirect(u('weddings', 'languages', $idWedding));
}

if (!Billing::isConfigured()) {
    // Modo sin cobro (Stripe no configurado en este servidor): se añade directamente.
    WeddingLanguages::add($app, $idWedding, $language);
    $app->log('wedding.language_add', $idWedding, $idAccount);
    App::flash(t('languages_added_flash'));
    $app->redirect(u('weddings', 'languages', $idWedding));
}

$stmt = $app->db->prepare('SELECT email FROM accounts WHERE id_account = ? LIMIT 1');
$stmt->bind_param('i', $idAccount);
$stmt->execute();
$accountEmail = (string)($stmt->get_result()->fetch_assoc()['email'] ?? $_user['email']);
$stmt->close();

try {
    $session = Billing::createLanguageCheckoutSession(
        $app,
        u('weddings', 'language_return') . '?session_id={CHECKOUT_SESSION_ID}&id=' . $idWedding,
        u('weddings', 'languages', $idWedding),
        $accountEmail,
        ['id_wedding' => $idWedding, 'id_account' => $idAccount, 'language' => $language]
    );
    $app->redirect($session['url']);
} catch (RuntimeException $e) {
    error_log('Billing: ' . $e->getMessage());
    App::flash(t('languages_purchase_error'), 'err');
    $app->redirect(u('weddings', 'languages', $idWedding));
}
