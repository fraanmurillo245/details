<?php
define('NO_LOGIN', true);
include_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    $stmt = $mysqli->prepare('SELECT id_user, password FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && password_verify($pass, $row['password'])) {
        $app->loginAs((int)$row['id_user']);
        $app->redirect(u('dashboard'));
    }
    $error = t('login_error');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('login_title')) ?> — <?= App::e(t('app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="text-gray-900 dark:bg-gray-950 dark:text-gray-100 flex min-h-screen items-center justify-center px-4"
      style="background: radial-gradient(circle at 20% 20%, #f1f2fb, #fbfbfd 55%);">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <img src="/assets/img/logo.png" alt="<?= App::e(t('app_name')) ?>" class="h-10 mx-auto">
    </div>
    <form method="post" class="card p-8 space-y-4">
        <h1 class="heading text-xl text-center mb-2"><?= App::e(t('login_title')) ?></h1>
        <?php if ($error): ?>
        <div class="rounded-md bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 px-3 py-2 text-sm"><?= App::e($error) ?></div>
        <?php endif; ?>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
            <input type="email" name="email" required class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('password')) ?></label>
            <input type="password" name="password" required class="field">
        </div>
        <button type="submit" class="btn-brand w-full justify-center py-2.5"><?= App::e(t('login_submit')) ?></button>
    </form>
    <p class="text-center text-sm mt-4 text-gray-500">
        <?= App::e(t('login_no_account')) ?> <a href="/signup/" style="color: var(--brand-solid)"><?= App::e(t('signup_cta')) ?></a>
    </p>
</div>
</body>
</html>
