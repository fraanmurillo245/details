<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$token = _clean_var($_slug, '');
$error = '';
$done = false;

$stmt = $app->db->prepare('SELECT id_user, email FROM users WHERE reset_token = ? AND reset_expires > NOW() AND active = 1 LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $error = t('forgot_invalid_token');
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    if (strlen($password) < 8) {
        $error = t('forgot_password_short');
    } elseif ($password !== $password2) {
        $error = t('forgot_password_mismatch');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $app->db->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?');
        $stmt->bind_param('si', $hash, $user['id_user']);
        $stmt->execute();
        $stmt->close();
        $app->loginAs((int)$user['id_user']);
        $app->redirect(u('dashboard'));
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('forgot_reset_title')) ?> — <?= App::e(t('app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="text-gray-900 flex min-h-screen items-center justify-center px-4"
      style="background: radial-gradient(circle at 20% 20%, #f1f2fb, #fbfbfd 55%);">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <img src="/assets/img/logo.png" alt="<?= App::e(t('app_name')) ?>" class="h-10 mx-auto">
    </div>
    <div class="card p-8 space-y-4">
        <h1 class="heading text-xl text-center mb-2"><?= App::e(t('forgot_reset_title')) ?></h1>
        <?php if ($error): ?>
            <div class="rounded-md bg-red-50 text-red-700 px-3 py-2 text-sm"><?= App::e($error) ?></div>
        <?php endif; ?>
        <?php if ($user): ?>
        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm mb-1"><?= App::e(t('forgot_new_password')) ?></label>
                <input type="password" name="password" required minlength="8" class="field">
            </div>
            <div>
                <label class="block text-sm mb-1"><?= App::e(t('forgot_repeat_password')) ?></label>
                <input type="password" name="password2" required minlength="8" class="field">
            </div>
            <button type="submit" class="btn-brand w-full justify-center py-2.5"><?= App::e(t('forgot_reset_submit')) ?></button>
        </form>
        <?php else: ?>
        <a href="/forgot/" class="btn-brand w-full justify-center py-2.5 inline-flex"><?= App::e(t('forgot_submit')) ?></a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
