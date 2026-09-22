<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $app->db->prepare('SELECT id_user FROM users WHERE email = ? AND active = 1 LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $stmt = $app->db->prepare('UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id_user = ?');
            $stmt->bind_param('si', $token, $user['id_user']);
            $stmt->execute();
            $stmt->close();

            $resetUrl = rtrim($_config['url'], '/') . '/forgot/reset/0/' . $token . '/';
            Notifications::passwordReset($email, $email, $resetUrl);
        }
    }
    // Mensaje siempre igual, exista o no la cuenta: evita revelar qué emails están registrados.
    $sent = true;
}
?>
<!doctype html>
<html lang="<?= App::e($_lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('forgot_title')) ?> — <?= App::e(t('app_name')) ?></title>
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
        <div class="flex justify-center mt-3"><?= App::langSwitcher() ?></div>
    </div>
    <div class="card p-8 space-y-4">
        <h1 class="heading text-xl text-center mb-2"><?= App::e(t('forgot_title')) ?></h1>
        <?php if ($sent): ?>
            <p class="text-sm text-center text-gray-600"><?= App::e(t('forgot_sent')) ?></p>
        <?php else: ?>
            <p class="text-sm text-center text-gray-500"><?= App::e(t('forgot_hint')) ?></p>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
                    <input type="email" name="email" required class="field">
                </div>
                <button type="submit" class="btn-brand w-full justify-center py-2.5"><?= App::e(t('forgot_submit')) ?></button>
            </form>
        <?php endif; ?>
    </div>
    <p class="text-center text-sm mt-4 text-gray-500">
        <a href="/login.php" style="color: var(--brand-solid)"><?= App::e(t('login_submit')) ?></a>
    </p>
</div>
</body>
</html>
