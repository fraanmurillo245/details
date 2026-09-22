<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$sessionId = (string)($_GET['session_id'] ?? '');
$error = '';

if ($sessionId === '') {
    $app->redirect(u('signup'));
}

try {
    $session = Billing::retrieveSession($sessionId);
} catch (RuntimeException $e) {
    error_log('Billing: ' . $e->getMessage());
    $error = t('signup_payment_error');
    $session = null;
}

if ($session && ($session['payment_status'] ?? '') === 'paid') {
    $token = $session['metadata']['pending_token'] ?? '';
    $stmt = $app->db->prepare('SELECT * FROM pending_signups WHERE token = ? LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pending) {
        $result = Onboarding::provision(
            $app, $pending['partner1_name'], $pending['partner2_name'],
            $pending['email'], $pending['password_hash'], $pending['wedding_slug']
        );

        $stmt = $app->db->prepare('DELETE FROM pending_signups WHERE id_pending = ?');
        $stmt->bind_param('i', $pending['id_pending']);
        $stmt->execute();
        $stmt->close();

        $amount = (int)($session['amount_total'] ?? 0);
        $currency = (string)($session['currency'] ?? 'eur');
        $stmt = $app->db->prepare(
            'INSERT INTO payments (id_account, stripe_session_id, amount_cents, currency, status, created_at)
             VALUES (?, ?, ?, ?, "paid", NOW())'
        );
        $stmt->bind_param('isis', $result['id_account'], $sessionId, $amount, $currency);
        $stmt->execute();
        $stmt->close();

        Notifications::welcome($pending['email'], $pending['partner1_name'] . ' & ' . $pending['partner2_name']);
        $app->loginAs($result['id_user']);
        $app->redirect(u('dashboard'));
    }

    // Token ya consumido (recarga de la página de éxito): si el usuario ya existe, entra directo.
    $email = (string)($session['customer_details']['email'] ?? $session['customer_email'] ?? '');
    if ($email !== '') {
        $stmt = $app->db->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user) {
            $app->loginAs((int)$user['id_user']);
            $app->redirect(u('dashboard'));
        }
    }
    $error = t('signup_already_used');
} elseif (!$error) {
    $error = t('signup_payment_pending');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('signup_title')) ?> — <?= App::e(t('app_name')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="text-gray-900 flex min-h-screen items-center justify-center px-4"
      style="background: radial-gradient(circle at 20% 20%, #f1f2fb, #fbfbfd 55%);">
<div class="w-full max-w-sm card p-8 text-center space-y-3">
    <p class="text-red-700"><?= App::e($error) ?></p>
    <a href="/signup/" class="btn-brand inline-flex"><?= App::e(t('signup_cta')) ?></a>
</div>
</body>
</html>
