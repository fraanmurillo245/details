<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$idAccount = (int)$_user['id_account'];
$idWedding = (int)$_id;

$stmt = $app->db->prepare('SELECT * FROM weddings WHERE id_wedding = ? AND id_account = ? LIMIT 1');
$stmt->bind_param('ii', $idWedding, $idAccount);
$stmt->execute();
$wedding = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wedding) { $app->redirect(u('weddings')); }

if ($wedding['status'] === 'published') {
    $app->redirect(u('weddings'));
}

$hasPaid = !Billing::isConfigured() || Billing::hasPaid($app, $idAccount);

if ($hasPaid) {
    $stmt = $app->db->prepare("UPDATE weddings SET status = 'published', updated_at = NOW() WHERE id_wedding = ?");
    $stmt->bind_param('i', $idWedding);
    $stmt->execute();
    $stmt->close();
    $app->log('wedding.publish', $idWedding, $idAccount);
    App::flash(t('wedding_published_flash', rtrim($_config['url'], '/') . '/invite/' . $wedding['slug'] . '/'));
    $app->redirect(u('weddings'));
}

// No ha pagado y Stripe está configurado: cobrar la tarifa plana antes de publicar.
$stmt = $app->db->prepare('SELECT email FROM accounts WHERE id_account = ? LIMIT 1');
$stmt->bind_param('i', $idAccount);
$stmt->execute();
$accountEmail = (string)($stmt->get_result()->fetch_assoc()['email'] ?? $_user['email']);
$stmt->close();

try {
    $session = Billing::createCheckoutSession(
        $app,
        u('weddings', 'publish_return') . '?session_id={CHECKOUT_SESSION_ID}&id=' . $idWedding,
        u('weddings'),
        $accountEmail,
        ['id_wedding' => $idWedding, 'id_account' => $idAccount]
    );
    $app->redirect($session['url']);
} catch (RuntimeException $e) {
    error_log('Billing: ' . $e->getMessage());
    App::flash(t('publish_payment_error'), 'err');
    $app->redirect(u('weddings'));
}
