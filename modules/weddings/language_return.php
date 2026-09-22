<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$idWedding = (int)$_id;
$sessionId = (string)($_GET['session_id'] ?? '');

if ($sessionId === '') { $app->redirect(u('weddings', 'languages', $idWedding)); }

try {
    $session = Billing::retrieveSession($sessionId);
} catch (RuntimeException $e) {
    error_log('Billing: ' . $e->getMessage());
    App::flash(t('languages_purchase_error'), 'err');
    $app->redirect(u('weddings', 'languages', $idWedding));
}

$metaWedding = (int)($session['metadata']['id_wedding'] ?? 0);
$metaAccount = (int)($session['metadata']['id_account'] ?? 0);
$language = (string)($session['metadata']['language'] ?? '');

// La sesión debe ser de esta cuenta y esta boda: evita reutilizar el session_id de otra compra.
if ($metaAccount !== $idAccount || $metaWedding !== $idWedding || !in_array($language, WeddingLanguages::ALL, true)) {
    App::flash(t('languages_purchase_error'), 'err');
    $app->redirect(u('weddings', 'languages', $idWedding));
}

if (($session['payment_status'] ?? '') !== 'paid') {
    App::flash(t('languages_purchase_pending'), 'err');
    $app->redirect(u('weddings', 'languages', $idWedding));
}

WeddingLanguages::add($app, $idWedding, $language);

// El pago se registra una sola vez por sesión de Stripe (idempotente ante recargas de esta página).
$stmt = $app->db->prepare('SELECT 1 FROM payments WHERE stripe_session_id = ? LIMIT 1');
$stmt->bind_param('s', $sessionId);
$stmt->execute();
$alreadyLogged = (bool)$stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$alreadyLogged) {
    $amount = (int)($session['amount_total'] ?? 0);
    $currency = (string)($session['currency'] ?? 'eur');
    $stmt = $app->db->prepare(
        'INSERT INTO payments (id_account, kind, ref_id, stripe_session_id, amount_cents, currency, status, created_at)
         VALUES (?, "language", ?, ?, ?, ?, "paid", NOW())'
    );
    $stmt->bind_param('iisis', $idAccount, $idWedding, $sessionId, $amount, $currency);
    $stmt->execute();
    $stmt->close();
    $app->log('wedding.language_add', $idWedding, $idAccount);
}

App::flash(t('languages_added_flash'));
$app->redirect(u('weddings', 'languages', $idWedding));
