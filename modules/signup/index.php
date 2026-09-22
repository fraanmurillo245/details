<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }
/** @var App $app */

$error = '';
$old = ['partner1_name' => '', 'partner2_name' => '', 'email' => '', 'slug' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = trim((string)($_POST['partner1_name'] ?? ''));
    $p2 = trim((string)($_POST['partner2_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $slug = _clean_var(trim((string)($_POST['slug'] ?? '')), '');
    $old = ['partner1_name' => $p1, 'partner2_name' => $p2, 'email' => $email, 'slug' => $slug];

    if ($p1 === '' || $p2 === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $slug === '') {
        $error = t('signup_invalid');
    } else {
        $stmt = $app->db->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $emailTaken = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $app->db->prepare('SELECT id_wedding FROM weddings WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $slugTaken = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($emailTaken) {
            $error = t('signup_email_taken');
        } elseif ($slugTaken) {
            $error = t('signup_slug_taken');
        } else {
            // El alta es siempre gratuita: la tarifa plana se cobra al publicar
            // la invitación (ver modules/weddings/publish.php), no aquí.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $result = Onboarding::provision($app, $p1, $p2, $email, $hash, $slug);
            Notifications::welcome($email, $p1 . ' & ' . $p2);
            $app->loginAs($result['id_user']);
            $app->redirect(u('dashboard'));
        }
    }
}
?>
<!doctype html>
<html lang="<?= App::e($_lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('signup_title')) ?> — <?= App::e(t('app_name')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="text-gray-900 flex min-h-screen items-center justify-center px-4 py-10"
      style="background: radial-gradient(circle at 20% 20%, #f1f2fb, #fbfbfd 55%);">
<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <img src="/assets/img/logo.png" alt="<?= App::e(t('app_name')) ?>" class="h-10 mx-auto">
        <div class="flex justify-center mt-3"><?= App::langSwitcher() ?></div>
    </div>
    <form method="post" class="card p-8 space-y-4">
        <div class="text-center mb-2">
            <h1 class="heading text-xl"><?= App::e(t('signup_title')) ?></h1>
            <p class="text-sm text-gray-500 mt-1"><?= App::e(t('signup_subtitle')) ?></p>
        </div>
        <?php if ($error): ?>
        <div class="rounded-md bg-red-50 text-red-700 px-3 py-2 text-sm"><?= App::e($error) ?></div>
        <?php endif; ?>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm mb-1"><?= App::e(t('partner1_name')) ?></label>
                <input name="partner1_name" required value="<?= App::e($old['partner1_name']) ?>" class="field">
            </div>
            <div>
                <label class="block text-sm mb-1"><?= App::e(t('partner2_name')) ?></label>
                <input name="partner2_name" required value="<?= App::e($old['partner2_name']) ?>" class="field">
            </div>
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('email')) ?></label>
            <input type="email" name="email" required value="<?= App::e($old['email']) ?>" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('password')) ?></label>
            <input type="password" name="password" required minlength="8" class="field">
        </div>
        <div>
            <label class="block text-sm mb-1"><?= App::e(t('slug')) ?></label>
            <input name="slug" required pattern="[A-Za-z0-9][A-Za-z0-9._-]*" value="<?= App::e($old['slug']) ?>" placeholder="ana-y-juan" class="field">
            <p class="text-xs text-gray-400 mt-1"><?= App::e(t('signup_slug_hint')) ?></p>
        </div>
        <button type="submit" class="btn-brand w-full justify-center py-2.5"><?= App::e(t('signup_submit_free')) ?></button>
    </form>
    <p class="text-center text-sm mt-4 text-gray-500">
        <?= App::e(t('signup_have_account')) ?> <a href="/login.php" style="color: var(--brand-solid)"><?= App::e(t('login_submit')) ?></a>
    </p>
</div>
</body>
</html>
