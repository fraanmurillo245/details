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
        $uid = (int)$row['id_user'];
        $sig = hash_hmac('sha256', (string)$uid, $_config['secret']);
        setcookie('auth', $uid . ':' . $sig, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
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
<title><?= App::e(t('login_title')) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 flex min-h-screen items-center justify-center">
<form method="post" class="w-full max-w-sm rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4">
    <h1 class="text-lg font-semibold"><?= App::e(t('login_title')) ?></h1>
    <?php if ($error): ?>
    <div class="rounded-md bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 px-3 py-2 text-sm"><?= App::e($error) ?></div>
    <?php endif; ?>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
        <input type="email" name="email" required class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1"><?= App::e(t('password')) ?></label>
        <input type="password" name="password" required class="w-full rounded-md border border-gray-300 dark:border-gray-700 bg-transparent px-3 py-2">
    </div>
    <button type="submit" class="btn w-full justify-center"><?= App::e(t('login_submit')) ?></button>
</form>
</body>
</html>
