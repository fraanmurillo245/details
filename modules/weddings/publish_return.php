<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$sessionId = (string)($_GET['session_id'] ?? '');

if ($sessionId === '') { $app->redirect(u('weddings')); }

try {
    $session = Billing::retrieveSession($sessionId);
} catch (RuntimeException $e) {
    error_log('Billing: ' . $e->getMessage());
    App::flash(t('publish_payment_error'), 'err');
    $app->redirect(u('weddings'));
}

$idWedding = (int)($session['metadata']['id_wedding'] ?? 0);
$metaAccount = (int)($session['metadata']['id_account'] ?? 0);

// La sesión debe ser de esta cuenta: evita que alguien reutilice el session_id de otra.
if ($metaAccount !== $idAccount || $idWedding <= 0) {
    App::flash(t('publish_payment_error'), 'err');
    $app->redirect(u('weddings'));
}

if (($session['payment_status'] ?? '') !== 'paid') {
    App::flash(t('publish_payment_pending'), 'err');
    $app->redirect(u('weddings'));
}

$stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
$stmt->bind_param('ii', $idWedding, $idAccount);
$stmt->execute();
$wedding = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wedding) { $app->redirect(u('weddings')); }

if ($wedding['status'] !== 'published') {
    $stmt = $app->db->prepare("UPDATE weddings SET status = 'published', updated_at = NOW() WHERE id_wedding = ?");
    $stmt->bind_param('i', $idWedding);
    $stmt->execute();
    $stmt->close();
    $app->log('wedding.publish', $idWedding, $idAccount);
}

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
        'INSERT INTO payments (id_account, stripe_session_id, amount_cents, currency, status, created_at)
         VALUES (?, ?, ?, ?, "paid", NOW())'
    );
    $stmt->bind_param('isis', $idAccount, $sessionId, $amount, $currency);
    $stmt->execute();
    $stmt->close();

    Notifications::publishPaymentReceived($wedding, $_user['email'], $wedding['partner1_name']);
}

App::flash(t('wedding_published_flash', rtrim($_config['url'], '/') . '/invite/' . $wedding['slug'] . '/'));
$app->redirect(u('weddings'));
