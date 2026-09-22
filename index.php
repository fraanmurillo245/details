<?php
include_once __DIR__ . '/config.php';

// 1) ?module=/?section= NO sirve páginas -> redirige a la amigable.
if (isset($_GET['module']) || isset($_GET['section'])) {
    $app->redirect(u(_clean_var($_GET['module'] ?? '', 'dashboard'),
                     _clean_var($_GET['section'] ?? ''),
                     _clean_var($_GET['id'] ?? '')));
}
// 2) Barra final obligatoria.
$p = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($p !== '' && substr($p, -1) !== '/') {
    $qs = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    $app->redirect(rtrim($_config['url'], '/') . $p . '/' . $qs);
}

// La invitación pública (/invite/<slug>/) tiene su propio layout, sin el panel.
// Aquí $_section es el slug de la boda, no una acción de módulo.
if ($_module === 'invite') {
    include __DIR__ . '/modules/invite/index.php';
    exit;
}

$section_path = __DIR__ . '/modules/' . $_module . '/' . $_section . '.php';

$nav = [
    'dashboard' => ['label' => t('nav_dashboard'), 'icon' => 'home'],
    'weddings'  => ['label' => t('nav_weddings'), 'icon' => 'heart'],
    'guests'    => ['label' => t('nav_guests'), 'icon' => 'users'],
    'design'    => ['label' => t('nav_design'), 'icon' => 'palette'],
];
?>
<!doctype html>
<html lang="<?= App::e($_lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= App::e(t('app_name')) ?></title>
<script>
try {
    if (document.cookie.includes('theme=dark')) document.documentElement.classList.add('dark');
} catch (e) {}
</script>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
<div class="flex min-h-screen">
    <aside class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-800 p-4">
        <div class="font-semibold mb-6"><?= App::e(t('app_name')) ?></div>
        <nav class="space-y-1">
            <?php foreach ($nav as $mod => $item): ?>
            <a href="<?= App::e(u($mod)) ?>"
               class="block rounded-md px-3 py-2 text-sm <?= $_module === $mod ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'hover:bg-gray-100 dark:hover:bg-gray-900' ?>">
                <?= App::e($item['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="flex-1">
        <header class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 px-6 py-3">
            <div></div>
            <div class="flex items-center gap-3 text-sm">
                <span><?= App::e($_user['email'] ?? '') ?></span>
                <a href="/logout.php" class="btn"><?= App::e(t('logout')) ?></a>
            </div>
        </header>
        <main class="p-6">
            <?php is_file($section_path) ? include $section_path : print '<p>404</p>'; ?>
        </main>
    </div>
</div>
<script src="/assets/js/app.js"></script>
</body>
</html>
